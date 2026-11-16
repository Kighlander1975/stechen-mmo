from __future__ import annotations

import sys
import unittest
from pathlib import Path

TOOL_ROOT = Path(__file__).resolve().parents[1]
if str(TOOL_ROOT) not in sys.path:
    sys.path.insert(0, str(TOOL_ROOT))

from architecture_objects import extract_architecture_objects
from content_reader import TextDocument


class ArchitectureObjectTests(unittest.TestCase):
    def test_extracts_php_service_and_controller(self) -> None:
        documents = (
            TextDocument(
                relative_path="laravel-app/app/Services/TestService.php",
                text="<?php\nclass TestService {}\n",
                line_count=2,
                encoding="utf-8",
            ),
            TextDocument(
                relative_path="laravel-app/app/Http/Controllers/LobbyController.php",
                text="<?php\nfinal class LobbyController {}\n",
                line_count=2,
                encoding="utf-8",
            ),
        )

        result = extract_architecture_objects(documents)
        types = {item.object_type for item in result.objects}

        self.assertIn("service", types)
        self.assertIn("controller", types)

    def test_extracts_vue_view_config_and_route_file(self) -> None:
        documents = (
            TextDocument(
                relative_path="laravel-app/resources/js/apps/game/GameApp.vue",
                text="<template></template>",
                line_count=1,
                encoding="utf-8",
            ),
            TextDocument(
                relative_path="laravel-app/resources/views/game/play.blade.php",
                text="<div></div>",
                line_count=1,
                encoding="utf-8",
            ),
            TextDocument(
                relative_path="laravel-app/config/game_rooms.php",
                text="<?php return [];",
                line_count=1,
                encoding="utf-8",
            ),
            TextDocument(
                relative_path="laravel-app/routes/web.php",
                text="<?php",
                line_count=1,
                encoding="utf-8",
            ),
        )

        result = extract_architecture_objects(documents)
        types = {item.object_type for item in result.objects}

        self.assertIn("vue_component", types)
        self.assertIn("blade_view", types)
        self.assertIn("config", types)
        self.assertIn("route_file", types)

    def test_ignores_analysis_tool_itself(self) -> None:
        documents = (
            TextDocument(
                relative_path="tools/project_analysis/search_engine.py",
                text="class SearchEngine: pass",
                line_count=1,
                encoding="utf-8",
            ),
        )

        result = extract_architecture_objects(documents)

        self.assertEqual(result.objects, ())


if __name__ == "__main__":
    unittest.main()
