from __future__ import annotations

import sys
import tempfile
import unittest
from pathlib import Path

TOOL_ROOT = Path(__file__).resolve().parents[1]
if str(TOOL_ROOT) not in sys.path:
    sys.path.insert(0, str(TOOL_ROOT))

from models import AnalysisProfile
from scanner import scan_project


class ScannerTests(unittest.TestCase):
    def make_profile(self, root: Path) -> AnalysisProfile:
        return AnalysisProfile(
            source_path=root / "test.analysis.txt",
            name="Test",
            purpose="Test",
            output_name="test",
            project_root=".",
            mode="read-only",
            scope=("src", "missing"),
            exclude=("src/excluded",),
            analysis_questions=("Frage",),
            report_sections=("Abschnitt",),
            evidence_requirements=("Beleg",),
        )

    def test_scans_scope_and_applies_excludes(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            (root / "src" / "nested").mkdir(parents=True)
            (root / "src" / "excluded").mkdir(parents=True)
            (root / "src" / "a.py").write_text("a", encoding="utf-8")
            (root / "src" / "nested" / "b.txt").write_text(
                "bb", encoding="utf-8"
            )
            (root / "src" / "excluded" / "c.py").write_text(
                "ccc", encoding="utf-8"
            )

            result = scan_project(root, self.make_profile(root))

            self.assertEqual(
                [item.relative_path for item in result.files],
                ["src/a.py", "src/nested/b.txt"],
            )
            self.assertEqual(len(result.warnings), 1)
            self.assertEqual(result.warnings[0].relative_path, "missing")

    def test_excludes_names_and_nested_path_sequences(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            (root / "app" / "node_modules").mkdir(parents=True)
            (root / "app" / "bootstrap" / "cache").mkdir(parents=True)
            (root / "app" / "src").mkdir(parents=True)

            (root / "app" / "node_modules" / "package.js").write_text(
                "x", encoding="utf-8"
            )
            (root / "app" / "bootstrap" / "cache" / "services.php").write_text(
                "x", encoding="utf-8"
            )
            (root / "app" / "src" / "keep.php").write_text(
                "x", encoding="utf-8"
            )

            profile = AnalysisProfile(
                source_path=root / "test.analysis.txt",
                name="Test",
                purpose="Test",
                output_name="test",
                project_root=".",
                mode="read-only",
                scope=("app",),
                exclude=("node_modules", "bootstrap/cache"),
                analysis_questions=("Frage",),
                report_sections=("Abschnitt",),
                evidence_requirements=("Beleg",),
            )

            result = scan_project(root, profile)

            self.assertEqual(
                [item.relative_path for item in result.files],
                ["app/src/keep.php"],
            )


if __name__ == "__main__":
    unittest.main()
