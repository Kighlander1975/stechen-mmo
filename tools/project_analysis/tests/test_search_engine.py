from __future__ import annotations

import sys
import unittest
from pathlib import Path

TOOL_ROOT = Path(__file__).resolve().parents[1]
if str(TOOL_ROOT) not in sys.path:
    sys.path.insert(0, str(TOOL_ROOT))

from content_reader import TextDocument
from models import AnalysisProfile
from search_engine import build_search_terms, search_documents


class SearchEngineTests(unittest.TestCase):
    def make_profile(self) -> AnalysisProfile:
        return AnalysisProfile(
            source_path=Path("test.analysis.txt"),
            name="Test",
            purpose="Test",
            output_name="test",
            project_root=".",
            mode="read-only",
            scope=("app",),
            exclude=("vendor",),
            analysis_questions=(
                "Wie erfolgt Navigation von der Lobby zum Spielfeld?",
                "Wo wird der Spielstart serverseitig entschieden?",
            ),
            report_sections=("Abschnitt",),
            evidence_requirements=("Beleg",),
        )

    def test_builds_reproducible_weighted_terms(self) -> None:
        terms = build_search_terms(self.make_profile())
        values = [term.value for term in terms]

        self.assertIn("navigation", values)
        self.assertIn("lobby", values)
        self.assertIn("spielfeld", values)
        self.assertNotIn("spiel", values)

    def test_deduplicates_matches_per_line(self) -> None:
        documents = (
            TextDocument(
                relative_path="laravel-app/app/Services/Lobby/Test.php",
                text="Lobby Navigation zum Spielfeld\n",
                line_count=1,
                encoding="utf-8",
            ),
        )

        result = search_documents(self.make_profile(), documents)

        self.assertEqual(len(result.matches), 1)
        self.assertEqual(result.matches[0].source_group, "backend")
        self.assertGreaterEqual(len(result.matches[0].terms), 2)

    def test_historical_documents_are_separate(self) -> None:
        documents = (
            TextDocument(
                relative_path="docs/archiv/old.md",
                text="Lobby Navigation\n",
                line_count=1,
                encoding="utf-8",
            ),
        )

        result = search_documents(self.make_profile(), documents)

        self.assertEqual(result.matches[0].source_group, "historical")

    def test_backend_is_not_starved_by_documentation(self) -> None:
        documents = [
            TextDocument(
                relative_path=f"docs/doc-{index}.md",
                text="Lobby Navigation\n",
                line_count=1,
                encoding="utf-8",
            )
            for index in range(100)
        ]
        documents.append(
            TextDocument(
                relative_path="laravel-app/app/Services/GameRooms/Start.php",
                text="Lobby Spielstart Navigation\n",
                line_count=1,
                encoding="utf-8",
            )
        )

        result = search_documents(self.make_profile(), tuple(documents))

        self.assertTrue(
            any(match.source_group == "backend" for match in result.matches)
        )
        stats = next(
            item for item in result.group_stats
            if item.group == "documentation"
        )
        self.assertTrue(stats.truncated)


if __name__ == "__main__":
    unittest.main()
