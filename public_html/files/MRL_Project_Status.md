/**
 * MRL_Project_Status.md
 *
 * VERSION: v003
 * LAST MODIFIED: 6/27/2026 5:19:30 pm
 * 
 * CHANGELOG:
 * v003 (6/27/2026 5:19:30 pm)
 * - Updated filename/header to ny time. Removed version/timestamp from internsl filename.
 * 
 * CHANGELOG:
 * v002 (6/27/2026 2:32:01 am)
 * - Rebuilt file as clean human-readable Markdown.
 * - Updated filename/header format to match MRL code-file naming convention.
 * - Removed escaped Markdown/comment formatting issue from v001.
 * - Added fuller project handoff sections and maintenance instructions.
 *
 * v001 (6/27/2026)
 * - Initial project status / handoff template created.
 */

# MRL Project Status

## Document Purpose

This file is the living engineering status document for the MRL NASCAR project.

It is intended to reduce context loss between long-running ChatGPT conversations by preserving the current state of the project, important design decisions, active work, known issues, and handoff notes.

This is not just a summary. It should function as a project notebook, handoff package, and continuity document.

---

# Instructions for ChatGPT

When this file is provided in an MRL-related chat:

1. Read this document before making recommendations.
2. Treat it as the current project baseline unless newer information is provided.
3. Preserve the project direction and design intent.
4. Favor incremental, evolutionary improvements over unnecessary rewrites.
5. Do not remove historical decisions just because they are no longer active.
6. When updating this document, regenerate the complete file from top to bottom.
7. Keep the same filename/header pattern used by MRL code files.
8. Increment the version number on each update.
9. Update the filename timestamp and `LAST MODIFIED` timestamp.
10. Add a new changelog entry for every version.
11. Keep placeholders if information is not yet known.
12. Do not invent facts. Use `TBD` or `Needs review` when unsure.

---

# Naming and Version Pattern

Use this pattern for future file copies:

```text
MRL_Project_Status_v###_YYYYMMDD_HHMMSSam.md
MRL_Project_Status_v###_YYYYMMDD_HHMMSSpm.md
```

Example:

```text
MRL_Project_Status_v002_20260627_023201am.md
```

The internal header should match the filename and use the same version and timestamp.

---

# Project Summary

## Project Name

MRL NASCAR

## Project Type

NASCAR race-results, standings, schedule, revision-monitoring, and league-support website tools.

## Primary Goal

Maintain and improve the MRL website and supporting utilities while preserving historical race data, reducing manual maintenance, and making race/standings information easier to update, verify, and display.

## Project Style

This project is evolutionary. It is not a single fixed task. Features, requirements, and priorities change over time as issues are discovered, better ideas emerge, and real race data exposes new needs.

---

# Project DNA

These are the guiding principles for MRL work:

- Preserve historical data.
- Do not lose or overwrite useful project knowledge.
- Prefer automation over manual work.
- Prefer clear, maintainable code over clever code.
- Favor small, safe improvements over large rewrites.
- Keep versioned files when changes matter.
- Maintain readable headers and changelogs.
- Explain the reasoning behind design decisions.
- Avoid breaking working behavior while improving new behavior.
- Preserve the ability to compare old vs new behavior.
- Respect the existing website structure unless there is a clear reason to change it.
- Keep future maintenance simple for Steve.

---

# Current High-Level Status

## Current Status

TBD — populate from the active MRL chat.

## Current Active Chat / Workstream

TBD — identify the current MRL chat topic, milestone, or work area.

## Current Main Objective

TBD — describe the specific thing currently being built, tested, fixed, or reviewed.

## Last Known Good State

TBD — describe the last confirmed working version, file, feature, or deployment state.

## Current Risk Level

TBD — describe whether the project is stable, experimental, mid-refactor, or needs caution.

---

# Active Development Areas

Use this section to list current areas of active work.

## Race Schedule

Status: TBD

Notes:
- TBD

## Race Results

Status: TBD

Notes:
- TBD

## Weekly Standings

Status: TBD

Notes:
- TBD

## Standings Timeline

Status: TBD

Notes:
- Recent file names shown in working folder include multiple `standings_timeline` versions and installers.
- Current active version needs to be confirmed from the active MRL chat.

## Revision Monitor

Status: TBD

Notes:
- Revision-monitor work exists in prior MRL files and chats.
- Current active behavior and latest installer/version need to be confirmed.

## Segment / Pick Deadline Tools

Status: TBD

Notes:
- `S3 deadline.html` and `S3 deadline v002.html` exist in the working folder.
- Purpose appears related to Segment 3 pick deadline countdown.
- Current deployed/approved version needs confirmation.

## Admin / Install Utilities

Status: TBD

Notes:
- Several `install_*.php` files exist.
- Current installer usage and safe deployment process should be documented.

---

# File Map

Populate and maintain this table as the active chat identifies current files.

| Area | Current File(s) | Latest Known Version | Status | Notes |
|---|---|---:|---|---|
| Weekly Standings | TBD | TBD | TBD | TBD |
| Segment Winner Colors Test | `install_weekly_standings_segment_winner_colors_test_20260627_022801pm.php` | TBD | Needs review | Seen in folder screenshot |
| Standings Timeline | `install_standings_timeline_v006_20260627_011211am.php` | v006 | Needs confirmation | Seen in folder screenshot |
| Standings Timeline Package | `standings_timeline_v006_package_20260627_011211am.zip` | v006 | Needs confirmation | Seen in folder screenshot |
| Standings Timeline Lite | `install_standings_timeline_lite_v002_20260625_042000pm.php` | v002 | Needs confirmation | Seen in folder screenshot |
| Weekly Revision UI | `install_weekly_revision_version_ui_v001_20260625_023445pm.php` | v001 | Needs confirmation | Seen in folder screenshot |
| S3 Deadline | `S3 deadline v002.html` | v002 | Needs confirmation | Seen in folder screenshot |
| File Comparison Report | `mrl_live_vs_test_file_comparison_report_20260623.md` | TBD | Needs review | Seen in folder screenshot |
| File Comparison CSV | `mrl_live_vs_test_file_comparison_20260623.csv` | TBD | Needs review | Seen in folder screenshot |

---

# Data Sources

Document all important data files, generated files, APIs, and external sources.

## JSON Files

| File | Purpose | Status | Notes |
|---|---|---|---|
| `/race_results/_race_results_schedule.json` | Race schedule data source | Known / active | Used by S3 deadline countdown so start time updates if schedule changes |
| TBD | TBD | TBD | TBD |

## Database Tables

| Table | Purpose | Status | Notes |
|---|---|---|---|
| TBD | TBD | TBD | TBD |

## External Sources / APIs

| Source | Purpose | Status | Notes |
|---|---|---|---|
| ESPN / race results source | Race result data | TBD | Exact current implementation needs confirmation |
| TBD | TBD | TBD | TBD |

---

# Current Architecture

Populate this as the active chat reviews the current system.

## Website Structure

TBD

## Local / Remote Workflow

Known baseline from prior MRL workflow:

- Work is maintained locally and uploaded/synced to the website.
- WinSCP is used for website synchronization.
- Important paths include `public_html` and `race_results`.
- Avoid unnecessary syncing/scanning of historical year folders when not needed.

Confirm current details before making deployment recommendations.

## Deployment / Install Pattern

TBD

Document:

- Which files are uploaded manually.
- Which installers are run.
- Whether installers are one-time or reusable.
- Which files are safe to delete after install.
- Which files should be archived.

---

# Important Design Decisions

Use this table to preserve why decisions were made.

| Decision | Reason | Status | Date / Version |
|---|---|---|---|
| Use versioned generated files | Makes rollback and comparison easier | Active | Ongoing |
| Keep project status as Markdown | Easier to edit in VS Code and share with ChatGPT | Active | v002 |
| Maintain chat handoff document | Reduces context loss when moving between large chats | Active | v002 |
| Favor project continuity | MRL development spans many long-running chats and evolving requirements | Active | v002 |
| TBD | TBD | TBD | TBD |

---

# Known Rejected or Avoided Approaches

This section is important. Future chats should check here before suggesting old ideas again.

| Approach | Why It Was Rejected / Avoided | Date / Version |
|---|---|---|
| Starting new chats too frequently | Causes repeated context rebuilds and wasted energy | v002 |
| Large rewrites without need | Risk of breaking working behavior and losing design intent | v002 |
| TBD | TBD | TBD |

---

# Current Open Issues

- TBD

---

# Current Priorities

1. TBD
2. TBD
3. TBD

---

# Next Session Starting Point

When a new ChatGPT conversation starts, begin here:

1. Read this file completely.
2. Ask Steve what changed since this version.
3. Confirm the active workstream.
4. Confirm the latest relevant files.
5. Ask for uploads only when needed.
6. Do not assume older project status is current if newer files or screenshots are provided.

Current next step: TBD

---

# Things the Next Chat Must Know

Populate this section carefully. It should be short enough to read quickly but complete enough to prevent bad assumptions.

- MRL is an ongoing evolutionary project, not a one-time task.
- Many project decisions are spread across numerous long ChatGPT conversations.
- Context continuity is critical.
- Steve prefers complete files, not placeholder snippets.
- Steve uses versioned filenames and header changelogs.
- Steve prefers Markdown for project documentation.
- Steve uses VS Code.
- Steve values readable, maintainable code and clear reasoning.
- Do not suggest unnecessary rewrites when an incremental fix is safer.
- TBD — add current active project-specific facts.

---

# Coding / Documentation Standards Reference

Known general preferences:

- Generate complete files when providing code.
- Avoid placeholder comments such as "put existing code here."
- Use clear file headers with version, timestamp, and changelog.
- Keep changes traceable.
- Match the existing project naming convention.
- Prefer readable PHP/HTML/JS/CSS over overly clever code.
- Maintain backward compatibility when practical.

Reference document:

- `Steve_Project_Standards.md` or current equivalent, if available.

---

# Testing Checklist

Use this checklist before considering a feature complete.

## General

- [ ] Confirm file opens without syntax errors.
- [ ] Confirm expected UI behavior.
- [ ] Confirm no obvious console errors.
- [ ] Confirm old behavior still works.
- [ ] Confirm new behavior works.
- [ ] Confirm naming/version/header are correct.
- [ ] Confirm deployment steps are documented.

## PHP

- [ ] Confirm PHP syntax.
- [ ] Confirm paths are correct on server.
- [ ] Confirm includes/requires work.
- [ ] Confirm permissions are not causing silent failures.
- [ ] Confirm errors are logged or visible during test.

## JavaScript / Browser

- [ ] Confirm no console errors.
- [ ] Confirm behavior works after refresh.
- [ ] Confirm behavior works in expected browser.
- [ ] Confirm mobile/responsive impact if relevant.

## Data

- [ ] Confirm JSON is valid.
- [ ] Confirm expected fields exist.
- [ ] Confirm fallback behavior exists if data is missing.
- [ ] Confirm historical data is preserved.

---

# Deployment Checklist

Before uploading/running on live site:

- [ ] Identify target environment: test or live.
- [ ] Confirm latest local file.
- [ ] Confirm latest remote file.
- [ ] Backup or preserve current working version.
- [ ] Upload only intended files.
- [ ] Run installer only if required.
- [ ] Verify live behavior.
- [ ] Record what changed in this document.

---

# Performance / Browser Notes

LightSession is used to keep very large ChatGPT conversations usable by trimming the browser-rendered DOM to the most recent messages.

Important:

- This improves browser usability.
- It does not delete server-side chat history.
- It does not solve cross-chat continuity by itself.
- This project status file exists partly because forced chat migrations cause major context-loss frustration.

Known observed node counts from LightSession:

| Chat | Node Count | Notes |
|---|---:|---|
| Recent MRL chat | 682 | Small / comfortable |
| Current latest MRL chat | 1,152 | Reasonable size |
| Old maxed-out MRL chat | 5,196 | Became unusable / required migration |

Suggested rough handoff gauge:

| LightSession Node Count | Suggested Action |
|---:|---|
| Under 1,500 | Normal use |
| 1,500 - 2,500 | Start or refresh handoff document |
| 2,500 - 3,500 | Keep handoff current |
| 3,500 - 4,500 | Prepare migration when convenient |
| 4,500+ | Assume migration may be needed soon |

This is a practical gauge, not a hard technical limit.

---

# Chat History Index

Use this section to map MRL chats to topics.

| Chat / Date / Title | Main Topic | Important Output | Status |
|---|---|---|---|
| TBD | TBD | TBD | TBD |

---

# Parking Lot / Future Ideas

Use this section for ideas that should not interrupt current work.

- TBD

---

# Technical Debt

Document known imperfections that are accepted for now.

- TBD

---

# Historical Decisions

Move completed, outdated, or inactive decisions here instead of deleting them.

| Item | Summary | Date / Version |
|---|---|---|
| TBD | TBD | TBD |

---

# Maintenance Instructions for Updating This File

When updating:

1. Start from the latest version of this file.
2. Update filename to the next version and current timestamp.
3. Update the header filename.
4. Increment VERSION.
5. Update LAST MODIFIED.
6. Add a new CHANGELOG entry at the top.
7. Preserve previous changelog entries.
8. Update only sections affected by the current work.
9. Do not delete useful history.
10. Mark unknowns as TBD rather than guessing.
11. Regenerate the complete file from top to bottom.

---

# Changelog

## v002 (6/27/2026 2:32:01 am)

- Rebuilt file as clean human-readable Markdown.
- Updated filename/header format to match MRL code-file naming convention.
- Removed escaped Markdown/comment formatting issue from v001.
- Added fuller project handoff sections and maintenance instructions.
- Added LightSession node-count gauge and chat migration guidance.
- Added file map based on visible recent MRL working-folder filenames.

## v001 (6/27/2026)

- Initial project status / handoff template created.
