---
name: coursework-handoff
description: Produces concise handoff-ready updates for this plain PHP coursework project, including docs updates, security checks, and phase tracking. Use when planning next phases, preparing teammate notes, or aligning implementation to course concepts.
---

# Coursework Handoff

## Purpose

Keep project changes easy for teammates to continue and easy to explain against course concepts.

## Workflow

1. Read current status docs:
   - `docs/implementation-status.md`
   - `docs/roadmap.md`
   - `docs/course-alignment.md`
2. If code behavior changes, update:
   - the relevant feature doc under `docs/`
   - `docs/implementation-status.md`
3. For security-sensitive changes, include:
   - risk addressed
   - affected files
   - short manual test checklist
4. Keep outputs concise and practical.

## Project constraints

- Plain PHP first (no framework migration).
- Local-run workflow is primary.
- Prefer minimal dependencies unless explicitly approved.

## Output template

Use this structure for teammate updates:

```md
## Change
- What was done

## Why
- Risk/problem solved

## Files
- file paths

## How to test
- short checklist

## Next
- immediate follow-up tasks
```
