from __future__ import annotations

from collections import defaultdict
from dataclasses import dataclass

from models import AnalysisProfile
from search_engine import EvidenceMatch, EvidenceSearchResult


QUESTION_GROUP_LIMITS = {
    "backend": 8,
    "frontend": 8,
    "tests": 8,
    "documentation": 5,
    "historical": 3,
    "other": 2,
}


@dataclass(frozen=True)
class QuestionGroupResult:
    group: str
    matches: tuple[EvidenceMatch, ...]
    truncated: bool


@dataclass(frozen=True)
class QuestionResult:
    question: str
    groups: tuple[QuestionGroupResult, ...]

    @property
    def total_matches(self) -> int:
        return sum(len(group.matches) for group in self.groups)


@dataclass(frozen=True)
class QuestionAnalysisResult:
    questions: tuple[QuestionResult, ...]


def _question_terms(
    question: str,
    evidence_result: EvidenceSearchResult,
) -> set[str]:
    return {
        term.value
        for term in evidence_result.terms
        if term.source_question == question
    }


def build_question_analysis(
    profile: AnalysisProfile,
    evidence_result: EvidenceSearchResult,
) -> QuestionAnalysisResult:
    results: list[QuestionResult] = []

    for question in profile.analysis_questions:
        relevant_terms = _question_terms(question, evidence_result)
        grouped: dict[str, list[EvidenceMatch]] = defaultdict(list)

        for match in evidence_result.matches:
            if relevant_terms.intersection(match.terms):
                grouped[match.source_group].append(match)

        group_results: list[QuestionGroupResult] = []
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
            limit = QUESTION_GROUP_LIMITS[group]
            group_results.append(
                QuestionGroupResult(
                    group=group,
                    matches=tuple(matches[:limit]),
                    truncated=len(matches) > limit,
                )
            )

        results.append(
            QuestionResult(
                question=question,
                groups=tuple(group_results),
            )
        )

    return QuestionAnalysisResult(questions=tuple(results))
