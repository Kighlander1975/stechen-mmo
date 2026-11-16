from __future__ import annotations

from dataclasses import dataclass
from pathlib import Path


@dataclass(frozen=True)
class AnalysisProfile:
    source_path: Path
    name: str
    purpose: str
    output_name: str
    project_root: str
    mode: str
    scope: tuple[str, ...]
    exclude: tuple[str, ...]
    analysis_questions: tuple[str, ...]
    report_sections: tuple[str, ...]
    evidence_requirements: tuple[str, ...]

    @property
    def slug(self) -> str:
        value = self.source_path.name
        suffix = ".analysis.txt"
        if value.endswith(suffix):
            value = value[:-len(suffix)]
        return value
