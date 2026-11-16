from __future__ import annotations

import sys
import unittest
from pathlib import Path

TOOL_ROOT = Path(__file__).resolve().parents[1]
if str(TOOL_ROOT) not in sys.path:
    sys.path.insert(0, str(TOOL_ROOT))

from architecture_objects import ArchitectureObjectResult
from content_reader import TextReadResult
from evidence_writer import build_evidence_report
from models import AnalysisProfile
from scanner import ScanResult
from search_engine import EvidenceSearchResult


class EvidenceWriterTests(unittest.TestCase):
    def test_builds_separate_evidence_report(self) -> None:
        profile = AnalysisProfile(
            source_path=Path("test.analysis.txt"),
            name="Test",
            purpose="Test",
            output_name="test",
            project_root=".",
            mode="read-only",
            scope=("app",),
            exclude=("vendor",),
            analysis_questions=("Frage",),
            report_sections=("Abschnitt",),
            evidence_requirements=("Beleg",),
        )
        report = build_evidence_report(
            profile,
            ScanResult(files=(), warnings=()),
            TextReadResult(documents=(), warnings=()),
            EvidenceSearchResult(terms=(), matches=(), group_stats=()),
            ArchitectureObjectResult(objects=()),
        )
        self.assertIn("Project Analysis Evidence", report)
        self.assertIn("Dateiinventar:", report)
        self.assertIn("Rohfundstellen:", report)


if __name__ == "__main__":
    unittest.main()
