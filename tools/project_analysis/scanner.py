from __future__ import annotations

from dataclasses import dataclass
from pathlib import Path

from models import AnalysisProfile


@dataclass(frozen=True)
class ScannedFile:
    relative_path: str
    suffix: str
    size_bytes: int


@dataclass(frozen=True)
class ScanWarning:
    relative_path: str
    message: str


@dataclass(frozen=True)
class ScanResult:
    files: tuple[ScannedFile, ...]
    warnings: tuple[ScanWarning, ...]

    @property
    def files_scanned(self) -> int:
        return len(self.files)


def _normalise_relative_path(path: Path) -> str:
    return path.as_posix()


def _is_excluded(relative_path: Path, excluded_paths: tuple[Path, ...]) -> bool:
    relative_parts = relative_path.parts

    for excluded in excluded_paths:
        excluded_parts = excluded.parts

        if len(excluded_parts) == 1:
            if excluded_parts[0] in relative_parts:
                return True
            continue

        window_size = len(excluded_parts)
        for index in range(len(relative_parts) - window_size + 1):
            if relative_parts[index:index + window_size] == excluded_parts:
                return True

    return False


def _resolve_inside_project(project_root: Path, candidate: Path) -> Path:
    resolved_root = project_root.resolve()
    resolved_candidate = candidate.resolve()

    try:
        resolved_candidate.relative_to(resolved_root)
    except ValueError as exc:
        raise ValueError(
            f"Pfad liegt außerhalb des Projektroots: {candidate}"
        ) from exc

    return resolved_candidate


def scan_project(project_root: Path, profile: AnalysisProfile) -> ScanResult:
    resolved_root = project_root.resolve()
    excluded_paths = tuple(Path(value) for value in profile.exclude)

    files: list[ScannedFile] = []
    warnings: list[ScanWarning] = []
    seen_paths: set[str] = set()

    for scope_value in profile.scope:
        scope_relative = Path(scope_value)

        if scope_relative.is_absolute() or ".." in scope_relative.parts:
            warnings.append(
                ScanWarning(
                    relative_path=_normalise_relative_path(scope_relative),
                    message="Ungültiger Scope-Pfad; außerhalb des Projektroots.",
                )
            )
            continue

        scope_path = resolved_root / scope_relative

        if not scope_path.exists():
            warnings.append(
                ScanWarning(
                    relative_path=_normalise_relative_path(scope_relative),
                    message="Scope-Pfad existiert nicht.",
                )
            )
            continue

        try:
            resolved_scope = _resolve_inside_project(resolved_root, scope_path)
        except (OSError, ValueError) as exc:
            warnings.append(
                ScanWarning(
                    relative_path=_normalise_relative_path(scope_relative),
                    message=str(exc),
                )
            )
            continue

        candidates = (
            (resolved_scope,)
            if resolved_scope.is_file()
            else resolved_scope.rglob("*")
        )

        for candidate in candidates:
            try:
                if candidate.is_symlink() or not candidate.is_file():
                    continue

                resolved_candidate = _resolve_inside_project(
                    resolved_root,
                    candidate,
                )
                relative_path = resolved_candidate.relative_to(resolved_root)

                if _is_excluded(relative_path, excluded_paths):
                    continue

                relative_text = _normalise_relative_path(relative_path)
                if relative_text in seen_paths:
                    continue

                stat = resolved_candidate.stat()
                files.append(
                    ScannedFile(
                        relative_path=relative_text,
                        suffix=resolved_candidate.suffix.lower(),
                        size_bytes=stat.st_size,
                    )
                )
                seen_paths.add(relative_text)
            except OSError as exc:
                try:
                    relative_text = _normalise_relative_path(
                        candidate.relative_to(resolved_root)
                    )
                except ValueError:
                    relative_text = str(candidate)

                warnings.append(
                    ScanWarning(
                        relative_path=relative_text,
                        message=f"Datei konnte nicht geprüft werden: {exc}",
                    )
                )

    files.sort(key=lambda item: item.relative_path.casefold())
    warnings.sort(key=lambda item: item.relative_path.casefold())

    return ScanResult(files=tuple(files), warnings=tuple(warnings))
