from __future__ import annotations

from pathlib import Path

from models import AnalysisProfile


class ProfileError(ValueError):
    pass


_SCALAR_FIELDS = ("Name", "Purpose", "Output", "ProjectRoot", "Mode")
_LIST_FIELDS = (
    "Scope",
    "Exclude",
    "AnalysisQuestions",
    "ReportSections",
    "EvidenceRequirements",
)
_REQUIRED_FIELDS = _SCALAR_FIELDS + _LIST_FIELDS
_ALLOWED_MODES = {"read-only"}
_OUTPUT_ROOT = Path("_docs") / "analysis_output"


def _normalise_output_name(raw_value: str) -> str:
    output_path = Path(raw_value.replace("\\", "/"))

    if output_path.is_absolute():
        raise ProfileError("Output muss ein relativer Pfad sein.")

    if ".." in output_path.parts:
        raise ProfileError("Output darf das Projektverzeichnis nicht verlassen.")

    if len(output_path.parts) < 3 or Path(*output_path.parts[:2]) != _OUTPUT_ROOT:
        raise ProfileError("Output muss unter '_docs/analysis_output/' liegen.")

    filename = output_path.name
    if not filename:
        raise ProfileError("Output muss einen Dateinamen enthalten.")

    if filename.endswith(".analysis-result.txt"):
        filename = filename[: -len(".analysis-result.txt")]
    elif filename.endswith(".txt"):
        filename = filename[:-4]

    if not filename:
        raise ProfileError("Output enthält keinen gültigen Basisnamen.")

    return filename


def parse_profile(profile_path: Path) -> AnalysisProfile:
    resolved_path = profile_path.resolve()

    if not resolved_path.is_file():
        raise ProfileError(f"Profil nicht gefunden: {profile_path}")

    try:
        lines = resolved_path.read_text(encoding="utf-8-sig").splitlines()
    except OSError as exc:
        raise ProfileError(f"Profil konnte nicht gelesen werden: {exc}") from exc

    scalar_values: dict[str, str] = {}
    list_values: dict[str, list[str]] = {field: [] for field in _LIST_FIELDS}
    seen_list_fields: set[str] = set()
    pending_scalar_field: str | None = None
    current_list_field: str | None = None

    for line_number, raw_line in enumerate(lines, start=1):
        stripped = raw_line.strip()

        if not stripped or stripped.startswith("#"):
            continue

        if pending_scalar_field is not None:
            if stripped.startswith("- ") or ":" in stripped:
                raise ProfileError(
                    f"Ungültige Profilzeile {line_number}: "
                    f"Wert für '{pending_scalar_field}' fehlt."
                )
            scalar_values[pending_scalar_field] = stripped
            pending_scalar_field = None
            continue

        if stripped.startswith("- "):
            if current_list_field is None:
                raise ProfileError(
                    f"Ungültige Listenzeile {line_number}: "
                    "Es wurde zuvor kein Listenfeld geöffnet."
                )

            item = stripped[2:].strip()
            if not item:
                raise ProfileError(
                    f"Ungültige Listenzeile {line_number}: Listeneintrag ist leer."
                )

            list_values[current_list_field].append(item)
            continue

        if ":" not in stripped:
            raise ProfileError(
                f"Ungültige Profilzeile {line_number}: "
                "Erwartet wird 'Schlüssel: Wert' oder '- Listeneintrag'."
            )

        key, value = stripped.split(":", 1)
        key = key.strip()
        value = value.strip()

        if key not in _REQUIRED_FIELDS:
            raise ProfileError(
                f"Unbekannter Profilschlüssel in Zeile {line_number}: {key}"
            )

        if key in _LIST_FIELDS:
            if value:
                raise ProfileError(
                    f"Listenfeld '{key}' in Zeile {line_number} "
                    "darf keinen Wert in derselben Zeile enthalten."
                )
            if key in seen_list_fields:
                raise ProfileError(
                    f"Doppelter Profilschlüssel in Zeile {line_number}: {key}"
                )
            seen_list_fields.add(key)
            current_list_field = key
            continue

        current_list_field = None

        if key in scalar_values or pending_scalar_field == key:
            raise ProfileError(
                f"Doppelter Profilschlüssel in Zeile {line_number}: {key}"
            )

        if value:
            scalar_values[key] = value
        else:
            pending_scalar_field = key

    if pending_scalar_field is not None:
        raise ProfileError(
            f"Wert für '{pending_scalar_field}' fehlt am Dateiende."
        )

    missing_scalar_fields = [
        field for field in _SCALAR_FIELDS if field not in scalar_values
    ]
    missing_list_fields = [
        field for field in _LIST_FIELDS if not list_values[field]
    ]
    missing_fields = missing_scalar_fields + missing_list_fields

    if missing_fields:
        raise ProfileError(
            "Fehlende oder leere Profilschlüssel: " + ", ".join(missing_fields)
        )

    mode = scalar_values["Mode"]
    if mode not in _ALLOWED_MODES:
        raise ProfileError(
            "Ungültiger Mode. Erlaubt ist aktuell ausschließlich: read-only"
        )

    project_root = scalar_values["ProjectRoot"]
    if Path(project_root).is_absolute() or project_root != ".":
        raise ProfileError("ProjectRoot muss aktuell exakt '.' sein.")

    return AnalysisProfile(
        source_path=resolved_path,
        name=scalar_values["Name"],
        purpose=scalar_values["Purpose"],
        output_name=_normalise_output_name(scalar_values["Output"]),
        project_root=project_root,
        mode=mode,
        scope=tuple(list_values["Scope"]),
        exclude=tuple(list_values["Exclude"]),
        analysis_questions=tuple(list_values["AnalysisQuestions"]),
        report_sections=tuple(list_values["ReportSections"]),
        evidence_requirements=tuple(list_values["EvidenceRequirements"]),
    )
