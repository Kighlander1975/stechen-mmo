from __future__ import annotations

import sys
import tempfile
import unittest
from pathlib import Path

TOOL_ROOT = Path(__file__).resolve().parents[1]
if str(TOOL_ROOT) not in sys.path:
    sys.path.insert(0, str(TOOL_ROOT))

from content_reader import read_text_documents
from scanner import ScannedFile


class ContentReaderTests(unittest.TestCase):
    def test_reads_supported_text_and_counts_lines(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            path = root / "example.md"
            path.write_text("eins\nzwei\n", encoding="utf-8")

            result = read_text_documents(
                root,
                (
                    ScannedFile(
                        relative_path="example.md",
                        suffix=".md",
                        size_bytes=path.stat().st_size,
                    ),
                ),
            )

            self.assertEqual(result.documents_read, 1)
            self.assertEqual(result.documents[0].line_count, 2)
            self.assertEqual(result.warnings, ())

    def test_skips_oversized_text(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            path = root / "large.txt"
            path.write_text("12345", encoding="utf-8")

            result = read_text_documents(
                root,
                (
                    ScannedFile(
                        relative_path="large.txt",
                        suffix=".txt",
                        size_bytes=path.stat().st_size,
                    ),
                ),
                max_text_bytes=4,
            )

            self.assertEqual(result.documents_read, 0)
            self.assertEqual(len(result.warnings), 1)


if __name__ == "__main__":
    unittest.main()
