from __future__ import annotations

from pathlib import Path

from architecture_objects import ArchitectureObjectResult
from content_reader import TextReadResult
from models import AnalysisProfile
from scanner import ScanResult
from search_engine import EvidenceSearchResult


def build_evidence_report(
    profile: AnalysisProfile,
    scan_result: ScanResult,
    text_result: TextReadResult,
    evidence_result: EvidenceSearchResult,
    architecture_result: ArchitectureObjectResult,
) -> str:
    lines = [
        "Stechen MMO – Project Analysis Evidence",
        f"Profil: {profile.name}",
        f"Zweck: {profile.purpose}",
        f"Gescannte Dateien: {scan_result.files_scanned}",
        f"Gelesene Textdateien: {text_result.documents_read}",
        f"Suchbegriffe: {len(evidence_result.terms)}",
        f"Fundstellen: {len(evidence_result.matches)}",
        "",
        "Dateiinventar:",
    ]
    lines.extend(
        f"- {item.relative_path} | {item.size_bytes} Bytes | "
        f"{item.suffix or '[ohne Endung]'}"
        for item in scan_result.files
    )
    lines.extend(["", "Architekturobjekte:"])
    for item in architecture_result.objects:
        lines.append(
            f"- {item.object_type} | {item.name} | "
            f"{item.relative_path}:{item.line_number} | "
            f"{item.declaration}"
        )

    lines.extend(["", "Rohfundstellen:"])
    for match in evidence_result.matches:
        line_text = match.line_text
        if len(line_text) > 240:
            line_text = line_text[:237] + "..."
        terms = ", ".join(match.terms)
        lines.append(
            f"- {match.relative_path}:{match.line_number} "
            f"| {match.source_group} | Score {match.score} "
            f"| Begriffe: {terms} | {line_text}"
        )
    lines.append("")
    return "\n".join(lines)


def write_evidence_report(
    output_root: Path,
    profile: AnalysisProfile,
    content: str,
) -> Path:
    output_root.mkdir(parents=True, exist_ok=True)
    path = output_root / f"{profile.output_name}.evidence.txt"
    path.write_text(content, encoding="utf-8", newline="\n")
    return path
