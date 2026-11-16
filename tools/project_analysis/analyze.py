#!/usr/bin/env python3
from __future__ import annotations

import sys
from pathlib import Path

from profile_parser import ProfileError, parse_profile
from architecture_objects import extract_architecture_objects
from content_reader import read_text_documents
from search_engine import search_documents
from question_engine import build_question_analysis
from evidence_writer import build_evidence_report, write_evidence_report
from reporter import build_report, write_report
from scanner import scan_project

TOOL_ROOT = Path(__file__).resolve().parent
PROJECT_ROOT = TOOL_ROOT.parent.parent
OUTPUT_ROOT = PROJECT_ROOT / "_docs" / "analysis_output"


def print_usage() -> None:
    print("Aufruf:")
    print(
        "python .\\tools\\project_analysis\\analyze.py "
        ".\\tools\\project_analysis\\profiles\\<name>.analysis.txt"
    )


def main(arguments: list[str]) -> int:
    if len(arguments) != 1:
        print_usage()
        return 2

    try:
        profile = parse_profile(Path(arguments[0]))
    except ProfileError as exc:
        print(f"Profilfehler: {exc}")
        return 3

    result = scan_project(PROJECT_ROOT, profile)
    text_result = read_text_documents(PROJECT_ROOT, result.files)
    architecture_result = extract_architecture_objects(text_result.documents)
    evidence_result = search_documents(profile, text_result.documents)
    question_result = build_question_analysis(profile, evidence_result)
    report = build_report(profile, result, text_result, evidence_result, question_result, architecture_result)
    report_path = write_report(OUTPUT_ROOT, profile, report)
    evidence_report = build_evidence_report(
        profile, result, text_result, evidence_result, architecture_result
    )
    evidence_path = write_evidence_report(
        OUTPUT_ROOT, profile, evidence_report
    )

    print("=" * 70)
    print("Stechen MMO - Project Analysis")
    print("=" * 70)
    print(f"Profil: {profile.name}")
    print(f"Zweck : {profile.purpose}")
    print(f"Bericht: {report_path}")
    print(f"Evidenz: {evidence_path}")
    print()
    print("Analyse-Grundgerüst erfolgreich ausgeführt.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main(sys.argv[1:]))
