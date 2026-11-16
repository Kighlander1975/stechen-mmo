from __future__ import annotations

import re
from dataclasses import dataclass
from pathlib import Path

from content_reader import TextDocument


@dataclass(frozen=True)
class ArchitectureObject:
    relative_path: str
    name: str
    object_type: str
    line_number: int
    declaration: str


@dataclass(frozen=True)
class ArchitectureObjectResult:
    objects: tuple[ArchitectureObject, ...]

    @property
    def total_objects(self) -> int:
        return len(self.objects)

    @property
    def types(self) -> tuple[str, ...]:
        return tuple(sorted({item.object_type for item in self.objects}))


_PHP_CLASS_PATTERN = re.compile(
    r"^\s*(?:final\s+|abstract\s+)?class\s+([A-Za-z_][A-Za-z0-9_]*)"
)
_PHP_INTERFACE_PATTERN = re.compile(
    r"^\s*interface\s+([A-Za-z_][A-Za-z0-9_]*)"
)
_PHP_TRAIT_PATTERN = re.compile(
    r"^\s*trait\s+([A-Za-z_][A-Za-z0-9_]*)"
)
_JS_COMPONENT_PATTERN = re.compile(
    r"^\s*(?:export\s+default\s+)?(?:defineComponent\s*\(|const\s+([A-Za-z_][A-Za-z0-9_]*)\s*=)"
)


def _classify_php(path: str, name: str) -> str:
    lowered = path.casefold()
    lowered_name = name.casefold()

    if "/tests/" in lowered or lowered.startswith("tests/"):
        return "test"
    if "/http/controllers/" in lowered or lowered_name.endswith("controller"):
        return "controller"
    if "/http/middleware/" in lowered or lowered_name.endswith("middleware"):
        return "middleware"
    if "/http/requests/" in lowered or lowered_name.endswith("request"):
        return "request"
    if "/models/" in lowered:
        return "model"
    if "/services/" in lowered or lowered_name.endswith("service"):
        return "service"
    if "/jobs/" in lowered or lowered_name.endswith("job"):
        return "job"
    if "/events/" in lowered or lowered_name.endswith("event"):
        return "event"
    if "/listeners/" in lowered or lowered_name.endswith("listener"):
        return "listener"
    if "/console/commands/" in lowered or lowered_name.endswith("command"):
        return "command"
    if "/policies/" in lowered or lowered_name.endswith("policy"):
        return "policy"
    return "php_class"


def _path_based_object(document: TextDocument) -> ArchitectureObject | None:
    path = Path(document.relative_path)
    lowered = document.relative_path.casefold()

    if lowered.endswith(".vue"):
        return ArchitectureObject(
            relative_path=document.relative_path,
            name=path.stem,
            object_type="vue_component",
            line_number=1,
            declaration=path.name,
        )

    if "/resources/views/" in lowered and (
        lowered.endswith(".blade.php") or lowered.endswith(".php")
    ):
        name = path.name
        if name.endswith(".blade.php"):
            name = name[: -len(".blade.php")]
        else:
            name = path.stem
        return ArchitectureObject(
            relative_path=document.relative_path,
            name=name,
            object_type="blade_view",
            line_number=1,
            declaration=path.name,
        )

    if "/config/" in lowered and lowered.endswith(".php"):
        return ArchitectureObject(
            relative_path=document.relative_path,
            name=path.stem,
            object_type="config",
            line_number=1,
            declaration=path.name,
        )

    if "/routes/" in lowered and lowered.endswith(".php"):
        return ArchitectureObject(
            relative_path=document.relative_path,
            name=path.stem,
            object_type="route_file",
            line_number=1,
            declaration=path.name,
        )

    if lowered.endswith(".md"):
        return ArchitectureObject(
            relative_path=document.relative_path,
            name=path.stem,
            object_type="documentation",
            line_number=1,
            declaration=path.name,
        )

    return None


def extract_architecture_objects(
    documents: tuple[TextDocument, ...],
) -> ArchitectureObjectResult:
    objects: list[ArchitectureObject] = []
    seen: set[tuple[str, str, str]] = set()

    for document in documents:
        normalised = document.relative_path.replace("\\", "/")

        if normalised.startswith("tools/project_analysis/"):
            continue

        path_object = _path_based_object(document)
        if path_object is not None:
            key = (
                path_object.relative_path,
                path_object.name,
                path_object.object_type,
            )
            if key not in seen:
                objects.append(path_object)
                seen.add(key)

        if not normalised.casefold().endswith(".php"):
            continue

        for line_number, line in enumerate(document.text.splitlines(), start=1):
            patterns = (
                (_PHP_CLASS_PATTERN, None),
                (_PHP_INTERFACE_PATTERN, "interface"),
                (_PHP_TRAIT_PATTERN, "trait"),
            )

            for pattern, fixed_type in patterns:
                match = pattern.match(line)
                if not match:
                    continue

                name = match.group(1)
                object_type = fixed_type or _classify_php(normalised, name)
                key = (normalised, name, object_type)
                if key in seen:
                    break

                objects.append(
                    ArchitectureObject(
                        relative_path=normalised,
                        name=name,
                        object_type=object_type,
                        line_number=line_number,
                        declaration=line.strip(),
                    )
                )
                seen.add(key)
                break

    objects.sort(
        key=lambda item: (
            item.object_type,
            item.name.casefold(),
            item.relative_path.casefold(),
            item.line_number,
        )
    )

    return ArchitectureObjectResult(objects=tuple(objects))
