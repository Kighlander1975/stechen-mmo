from __future__ import annotations

import sys
import unittest
from pathlib import Path

TOOL_ROOT = Path(__file__).resolve().parents[1]
if str(TOOL_ROOT) not in sys.path:
    sys.path.insert(0, str(TOOL_ROOT))

from models import AnalysisProfile
from question_engine import build_question_analysis
from search_engine import (
    EvidenceMatch,
    EvidenceSearchResult,
    GroupSearchStats,
    SearchTerm,
)


class QuestionEngineTests(unittest.TestCase):
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
                "Wo wird der Spielstart entschieden?",
                "Wie erfolgt die Navigation?",
            ),
            report_sections=("Abschnitt",),
            evidence_requirements=("Beleg",),
        )

    def test_assigns_matches_to_questions(self) -> None:
        evidence = EvidenceSearchResult(
            terms=(
                SearchTerm(
                    value="spielstart",
                    source_question="Wo wird der Spielstart entschieden?",
                    weight=5,
                ),
                SearchTerm(
                    value="navigation",
                    source_question="Wie erfolgt die Navigation?",
                    weight=5,
                ),
            ),
            matches=(
                EvidenceMatch(
                    relative_path="app/Start.php",
                    source_group="backend",
                    line_number=10,
                    line_text="Spielstart",
                    terms=("spielstart",),
                    source_questions=("Wo wird der Spielstart entschieden?",),
                    score=30,
                ),
                EvidenceMatch(
                    relative_path="resources/js/App.vue",
                    source_group="frontend",
                    line_number=5,
                    line_text="Navigation",
                    terms=("navigation",),
                    source_questions=("Wie erfolgt die Navigation?",),
                    score=25,
                ),
            ),
            group_stats=(
                GroupSearchStats("backend", 1, 100, False),
                GroupSearchStats("frontend", 1, 80, False),
            ),
        )

        result = build_question_analysis(self.make_profile(), evidence)

        self.assertEqual(result.questions[0].total_matches, 1)
        self.assertEqual(result.questions[1].total_matches, 1)

    def test_marks_group_as_truncated(self) -> None:
        profile = self.make_profile()
        terms = (
            SearchTerm(
                value="spielstart",
                source_question=profile.analysis_questions[0],
                weight=5,
            ),
        )
        matches = tuple(
            EvidenceMatch(
                relative_path=f"app/Start{index}.php",
                source_group="backend",
                line_number=index + 1,
                line_text="Spielstart",
                terms=("spielstart",),
                source_questions=(profile.analysis_questions[0],),
                score=100 - index,
            )
            for index in range(10)
        )
        evidence = EvidenceSearchResult(
            terms=terms,
            matches=matches,
            group_stats=(),
        )

        result = build_question_analysis(profile, evidence)
        backend = result.questions[0].groups[0]

        self.assertEqual(len(backend.matches), 8)
        self.assertTrue(backend.truncated)


if __name__ == "__main__":
    unittest.main()
