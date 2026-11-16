from __future__ import annotations

from dataclasses import dataclass
from pathlib import Path

from scanner import ScannedFile


DEFAULT_MAX_TEXT_BYTES = 1_000_000
_TEXT_SUFFIXES = {
    ".blade.php",
    ".css",
    ".env.example",
    ".example",
    ".html",
    ".js",
    ".json",
    ".lock",
    ".md",
    ".mjs",
    ".php",
    ".ps1",
    ".py",
    ".txt",
    ".ts",
    ".vue",
    ".xml",
    ".yaml",
    ".yml",
}


@dataclass(frozen=True)
class TextDocument:
    relative_path: str
    text: str
    line_count: int
    encoding: str


@dataclass(frozen=True)
class TextReadWarning:
    relative_path: str
    message: str


@dataclass(frozen=True)
class TextReadResult:
    documents: tuple[TextDocument, ...]
    warnings: tuple[TextReadWarning, ...]

    @property
    def documents_read(self) -> int:
        return len(self.documents)


def _effective_suffix(relative_path: str) -> str:
    lowered = relative_path.lower()
    if lowered.endswith(".blade.php"):
        return ".blade.php"
    if lowered.endswith(".env.example"):
        return ".env.example"
    return Path(relative_path).suffix.lower()


def _looks_binary(sample: bytes) -> bool:
    return b"\x00" in sample


def read_text_documents(
    project_root: Path,
    files: tuple[ScannedFile, ...],
    max_text_bytes: int = DEFAULT_MAX_TEXT_BYTES,
) -> TextReadResult:
    root = project_root.resolve()
    documents: list[TextDocument] = []
    warnings: list[TextReadWarning] = []

    for item in files:
        suffix = _effective_suffix(item.relative_path)
        if suffix not in _TEXT_SUFFIXES:
            continue

        if item.size_bytes > max_text_bytes:
            warnings.append(
                TextReadWarning(
                    relative_path=item.relative_path,
                    message=(
                        f"Textdatei überschreitet Größenlimit "
                        f"({item.size_bytes} > {max_text_bytes} Bytes)."
                    ),
                )
            )
            continue

        path = (root / item.relative_path).resolve()
        try:
            path.relative_to(root)
        except ValueError:
            warnings.append(
                TextReadWarning(
                    relative_path=item.relative_path,
                    message="Dateipfad liegt außerhalb des Projektroots.",
                )
            )
            continue

        try:
            raw = path.read_bytes()
        except OSError as exc:
            warnings.append(
                TextReadWarning(
                    relative_path=item.relative_path,
                    message=f"Datei konnte nicht gelesen werden: {exc}",
                )
            )
            continue

        if _looks_binary(raw[:8192]):
            warnings.append(
                TextReadWarning(
                    relative_path=item.relative_path,
                    message="Datei wirkt binär und wurde nicht gelesen.",
                )
            )
            continue

        decoded = None
        used_encoding = ""
        for encoding in ("utf-8-sig", "utf-8", "cp1252"):
            try:
                decoded = raw.decode(encoding)
                used_encoding = encoding
                break
            except UnicodeDecodeError:
                continue

        if decoded is None:
            warnings.append(
                TextReadWarning(
                    relative_path=item.relative_path,
                    message="Keine unterstützte Textkodierung erkannt.",
                )
            )
            continue

        documents.append(
            TextDocument(
                relative_path=item.relative_path,
                text=decoded,
                line_count=len(decoded.splitlines()),
                encoding=used_encoding,
            )
        )

    documents.sort(key=lambda item: item.relative_path.casefold())
    warnings.sort(key=lambda item: item.relative_path.casefold())

    return TextReadResult(
        documents=tuple(documents),
        warnings=tuple(warnings),
    )
