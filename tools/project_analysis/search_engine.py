from __future__ import annotations

import re
from collections import defaultdict
from dataclasses import dataclass
from pathlib import Path

from content_reader import TextDocument
from models import AnalysisProfile


GROUP_BUDGETS = {
    "backend": 100,
    "frontend": 80,
    "tests": 80,
    "documentation": 60,
    "historical": 20,
    "other": 10,
}

_STOP_WORDS = {
    "aber", "als", "auch", "auf", "aus", "bei", "beim", "bereits",
    "das", "dass", "dem", "den", "der", "des", "die", "dies", "diese",
    "durch", "ein", "eine", "einer", "eines", "einem", "einen", "er",
    "es", "für", "hat", "haben", "im", "in", "ist", "kann", "mit",
    "nach", "nicht", "oder", "sich", "sind", "soll", "und", "unter",
    "von", "vor", "wann", "warum", "was", "welche", "welcher", "welches",
    "wie", "wird", "wo", "zu", "zum", "zur",
    "spiel", "spieler", "spiele", "code", "tests", "dokumentation",
    "bestehen", "eigenen", "mehreren", "anwendung",
}

_HIGH_VALUE_TERMS = {
    "lobby", "spielstart", "countdown", "navigation", "routing",
    "realtime", "polling", "broadcast", "event", "events",
    "reconnect", "disconnect", "spielfeld", "laufende partie",
    "laufendes spiel", "eigener zug", "startphase",
}

SYNONYM_MAP = {'lobbyzustaende': ('status', 'state', 'waiting', 'starting', 'running', 'finished', 'cancelled', 'start_phase', 'play_state'), 'spielstart': ('start', 'starting', 'started', 'start_phase', 'starts_at', 'started_at', 'gameroomstart', 'startcoordinator'), 'countdown': ('countdown', 'starts_in', 'remaining_seconds', 'start_phase'), 'navigation': ('route', 'router', 'redirect', 'window.location', 'href', 'visit', 'navigate'), 'reconnect': ('reconnect', 'disconnect', 'connection', 'presence')}

_SOURCE_PRIORITY = {
    "backend": 0,
    "frontend": 1,
    "tests": 2,
    "documentation": 3,
    "historical": 4,
    "other": 5,
}


@dataclass(frozen=True)
class SearchTerm:
    value: str
    source_question: str
    weight: int


@dataclass(frozen=True)
class EvidenceMatch:
    relative_path: str
    source_group: str
    line_number: int
    line_text: str
    terms: tuple[str, ...]
    source_questions: tuple[str, ...]
    score: int


@dataclass(frozen=True)
class GroupSearchStats:
    group: str
    matches: int
    budget: int
    truncated: bool


@dataclass(frozen=True)
class EvidenceSearchResult:
    terms: tuple[SearchTerm, ...]
    matches: tuple[EvidenceMatch, ...]
    group_stats: tuple[GroupSearchStats, ...]

    @property
    def files_with_matches(self) -> int:
        return len({match.relative_path for match in self.matches})

    @property
    def truncated(self) -> bool:
        return any(item.truncated for item in self.group_stats)


def _normalise_token(value: str) -> str:
    return value.strip(".,;:!?()[]{}<>\"'").casefold()


def _derive_terms(question: str) -> list[str]:
    raw_tokens = re.findall(r"[A-Za-zÄÖÜäöüß0-9_-]+", question)
    tokens = [
        _normalise_token(token)
        for token in raw_tokens
        if len(_normalise_token(token)) >= 4
        and _normalise_token(token) not in _STOP_WORDS
    ]

    terms: list[str] = []
    seen: set[str] = set()

    for token in tokens:
        if token not in seen:
            terms.append(token)
            seen.add(token)

    for index in range(len(tokens) - 1):
        phrase = f"{tokens[index]} {tokens[index + 1]}"
        if phrase not in seen:
            terms.append(phrase)
            seen.add(phrase)

    return terms


def _term_weight(value: str) -> int:
    if value in _HIGH_VALUE_TERMS:
        return 5
    if " " in value:
        return 4
    if len(value) >= 10:
        return 3
    return 2


def build_search_terms(profile: AnalysisProfile) -> tuple[SearchTerm, ...]:
    terms: list[SearchTerm] = []
    seen: set[str] = set()

    for question in profile.analysis_questions:
        for value in _derive_terms(question):
            if value in seen:
                continue
            terms.append(
                SearchTerm(
                    value=value,
                    source_question=question,
                    weight=_term_weight(value),
                )
            )
            seen.add(value)
            for synonym in SYNONYM_MAP.get(value, ()):
                normalised = synonym.casefold()
                if normalised in seen:
                    continue
                terms.append(SearchTerm(
                    value=normalised,
                    source_question=question,
                    weight=_term_weight(normalised),
                ))
                seen.add(normalised)

    terms.sort(key=lambda item: (-item.weight, item.value))
    return tuple(terms)


def _source_group(relative_path: str) -> str:
    normalised = relative_path.replace("\\", "/").casefold()
    path = Path(normalised)
    parts = path.parts
    filename = path.name

    if "archiv" in parts:
        return "historical"

    if "tests" in parts or filename.startswith("test_"):
        return "tests"

    if path.suffix == ".md" or any(
        marker in normalised for marker in ("docs/", "_docs/")
    ):
        return "documentation"

    if any(
        marker in normalised
        for marker in ("resources/js/", "resources/views/")
    ) or path.suffix in {".vue", ".js", ".css"}:
        return "frontend"

    if any(
        marker in normalised
        for marker in ("app/", "routes/", "config/")
    ) or path.suffix == ".php":
        return "backend"

    return "other"


def _path_bonus(group: str, relative_path: str) -> int:
    lowered = relative_path.casefold()

    bonus = {
        "backend": 20,
        "frontend": 18,
        "tests": 16,
        "documentation": 8,
        "historical": 2,
        "other": 0,
    }[group]

    if "lobby" in lowered:
        bonus += 10
    if any(
        marker in lowered
        for marker in (
            "gameroom",
            "game_room",
            "gameplay",
            "gameapp",
            "routes/web.php",
        )
    ):
        bonus += 8

    return bonus


def search_documents(
    profile: AnalysisProfile,
    documents: tuple[TextDocument, ...],
) -> EvidenceSearchResult:
    terms = build_search_terms(profile)
    candidate_map: dict[tuple[str, int], dict[str, object]] = {}

    for document in documents:
        normalised_document_path = document.relative_path.replace('\\', '/')
        if normalised_document_path.startswith('tools/project_analysis/'):
            continue
        group = _source_group(document.relative_path)

        for line_number, line in enumerate(document.text.splitlines(), start=1):
            haystack = line.casefold()
            matched_terms = [term for term in terms if term.value in haystack]

            if not matched_terms:
                continue

            key = (document.relative_path, line_number)
            candidate_map[key] = {
                "relative_path": document.relative_path,
                "source_group": group,
                "line_number": line_number,
                "line_text": line.strip(),
                "terms": tuple(sorted({term.value for term in matched_terms})),
                "source_questions": tuple(
                    sorted({term.source_question for term in matched_terms})
                ),
                "score": (
                    sum(term.weight for term in matched_terms)
                    + _path_bonus(group, document.relative_path)
                ),
            }

    grouped: dict[str, list[EvidenceMatch]] = defaultdict(list)
    for item in candidate_map.values():
        match = EvidenceMatch(**item)
        grouped[match.source_group].append(match)

    selected: list[EvidenceMatch] = []
    stats: list[GroupSearchStats] = []

    for group in (
        "backend",
        "frontend",
        "tests",
        "documentation",
        "historical",
        "other",
    ):
        matches = grouped.get(group, [])
        matches.sort(
            key=lambda item: (
                -item.score,
                item.relative_path.casefold(),
                item.line_number,
            )
        )

        budget = GROUP_BUDGETS[group]
        selected.extend(matches[:budget])
        stats.append(
            GroupSearchStats(
                group=group,
                matches=min(len(matches), budget),
                budget=budget,
                truncated=len(matches) > budget,
            )
        )

    selected.sort(
        key=lambda item: (
            _SOURCE_PRIORITY[item.source_group],
            -item.score,
            item.relative_path.casefold(),
            item.line_number,
        )
    )

    return EvidenceSearchResult(
        terms=terms,
        matches=tuple(selected),
        group_stats=tuple(stats),
    )
