from __future__ import annotations

from pathlib import Path

from architecture_objects import ArchitectureObjectResult
from content_reader import TextReadResult
from models import AnalysisProfile
from scanner import ScanResult
from search_engine import EvidenceSearchResult
from question_engine import QuestionAnalysisResult


def build_report(
    profile: AnalysisProfile,
    result: ScanResult,
    text_result: TextReadResult,
    evidence_result: EvidenceSearchResult,
    question_result: QuestionAnalysisResult,
    architecture_result: ArchitectureObjectResult,
) -> str:
    lines = [
        "Stechen MMO – Project Analysis",
        f"Profil: {profile.name}",
        f"Zweck: {profile.purpose}",
        f"Modus: {profile.mode}",
        f"Gescannte Dateien: {result.files_scanned}",
        f"Warnungen: {len(result.warnings)}",
        f"Gelesene Textdateien: {text_result.documents_read}",
        f"Textlese-Warnungen: {len(text_result.warnings)}",
        f"Suchbegriffe: {len(evidence_result.terms)}",
        f"Fundstellen: {len(evidence_result.matches)}",
        f"Dateien mit Fundstellen: {evidence_result.files_with_matches}",
        f"Treffer gekürzt: {"ja" if evidence_result.truncated else "nein"}",
        f"Architekturobjekte: {architecture_result.total_objects}",
        "",
        "Scopes:",
    ]
    lines.extend(f"- {value}" for value in profile.scope)

    lines.extend(["", "Ausschlüsse:"])
    lines.extend(f"- {value}" for value in profile.exclude)

    lines.extend(["", "Warnungen:"])
    if result.warnings:
        lines.extend(
            f"- {warning.relative_path}: {warning.message}"
            for warning in result.warnings
        )
    else:
        lines.append("- Keine Warnungen.")

    lines.extend(["", "Textlese-Warnungen:"])
    if text_result.warnings:
        lines.extend(
            f"- {warning.relative_path}: {warning.message}"
            for warning in text_result.warnings
        )
    else:
        lines.append("- Keine Warnungen.")

    lines.extend(["", "Trefferbudgets nach Quellgruppe:"])
    for stats in evidence_result.group_stats:
        lines.append(
            f"- {stats.group}: {stats.matches}/{stats.budget} "
            f"| gekürzt: {'ja' if stats.truncated else 'nein'}"
        )

    lines.extend(["", "Architekturobjekte nach Typ:"])
    for object_type in architecture_result.types:
        count = sum(
            1 for item in architecture_result.objects
            if item.object_type == object_type
        )
        lines.append(f"- {object_type}: {count}")

    lines.extend(["", "Fragebezogene Verdichtung:"])
    for question_index, question in enumerate(
        question_result.questions,
        start=1,
    ):
        lines.append(
            f"\n# Frage {question_index}: {question.question}"
        )
        lines.append(
            f"- Verdichtete Fundstellen: {question.total_matches}"
        )

        for group in question.groups:
            lines.append(
                f"\n## {group.group} "
                f"| Treffer: {len(group.matches)} "
                f"| gekürzt: {'ja' if group.truncated else 'nein'}"
            )

            if not group.matches:
                lines.append("- Keine Fundstellen.")
                continue

            for match in group.matches:
                line_text = match.line_text
                if len(line_text) > 240:
                    line_text = line_text[:237] + "..."
                terms = ", ".join(match.terms)
                lines.append(
                    f"- {match.relative_path}:{match.line_number} "
                    f"| Score {match.score} "
                    f"| Begriffe: {terms} "
                    f"| {line_text}"
                )

    lines.append("")
    return "\n".join(lines)


def write_report(output_root: Path, profile: AnalysisProfile, content: str) -> Path:
    output_root.mkdir(parents=True, exist_ok=True)
    path = output_root / f"{profile.output_name}.analysis-result.txt"
    path.write_text(content, encoding="utf-8", newline="\n")
    return path
