# Agent Guidance

## Purpose
This file provides general, cross-project guidance for how to work in a repository.
Project-specific rules belong in the project’s primary documentation.

## Orientation
- Identify the project’s authoritative docs and source-of-truth files before editing.
- Re-open and review this AGENTS.md when resuming work or before any commit to avoid missing important constraints.
- Follow the project’s declared architecture, naming, and workflow conventions.
- Prefer minimal, scoped changes and avoid refactors unless requested.

## Build and Test
- Use the project’s documented commands only.
- If commands are missing, state that clearly and avoid guessing.

## Coding Practices
- Keep code changes small, readable, and purpose-driven.
- Avoid introducing new dependencies unless required.
- Use comments to clarify intent where code is not self-evident.

## Comments and File Headers
- Follow the project’s declared header conventions for file-level comments.
- Do not add version numbers, authorship, or edit history unless the project explicitly requires it.
- Keep header text stable and aligned with the file’s real role.

## Git and Commit Hygiene
- Keep commits focused on a single change set.
- Do not commit while the current issue is still being debugged or unresolved.
- Never leave uncommitted files that are not specifically gitignored.
- Commit all changed files; split into separate commits when changes are distinct.
- Avoid mixing formatting-only changes with behavior changes unless tightly coupled.

## Documentation Discipline
- Keep documentation truthful and aligned with actual behavior.
- Avoid TODO sprawl; track follow-ups in the place the project expects.
