/**
 * MRL_Project_Status.md
 *
 * VERSION: v004
 * LAST MODIFIED: 9/6/2026 1:36:32 pm
 *
 * CHANGELOG:
 * v004 (9/6/2026 1:36:32 pm)
 * - Replaced June placeholder status with current September 2026 production state.
 * - Added current source-of-truth/document precedence and active-iteration rules.
 * - Documented live-only environment after retirement of testPHP8.
 * - Documented race-results master scheduler v014 and current three-task architecture.
 * - Added Pick Reminder production status, safety rules, and September 6 live configuration.
 * - Added current Team Page/pick-system baseline and mrl_segment standings direction.
 * - Added current priorities and November 9 "Start a New Year / 2027" planning.
 * - Added chat-export continuity procedure for migration from Main Chat 9.
 *
 * v003 (6/27/2026 5:19:30 pm)
 * - Updated filename/header to NY time.
 *
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

This is the authoritative living project-status and engineering-handoff document for the Manlius Racing League (MRL) NASCAR project.

Its purpose is to let a new ChatGPT conversation understand the present project state quickly without treating older design notes or roadmap documents as current reality.

Detailed prior-chat exports remain valuable for continuity and decision history, but this file should describe the current state.

---

# Instructions for ChatGPT

When this file is available in an MRL-related chat:

1. Read this document before making project-level recommendations.
2. Treat it as the current project-status baseline unless newer information is provided in the active chat.
3. During an unfinished iteration, prefer the newest version actively generated/tested in the current chat over GitHub for that specific file/subsystem.
4. Otherwise, use GitHub `stevekenney318/MRL`, branch `main`, as the stable code source of truth.
5. Treat older roadmaps, monitor-design documents, PHP-migration notes, and handoff documents as historical/reference material unless explicitly marked current.
6. Use prior-chat exports to recover detailed continuity and reasoning, not to override newer tested code or this document.
7. Favor incremental, evolutionary improvements over unnecessary rewrites.
8. Do not ask Steve to upload a file that can reasonably be fetched from the current GitHub repository when GitHub is current.
9. When updating this document, regenerate the complete file and increment the version.
10. Do not invent facts. Use `TBD` / `Needs review` when something truly remains unknown.

---

# Project Summary

## Project Name

Manlius Racing League (MRL)

## Project Type

NASCAR fantasy league website and supporting administration/automation system.

## Primary Goal

Maintain and improve the MRL website while reducing weekly manual work, preserving historical data, automating race-results/standings workflows, improving pick/team administration, and keeping the system understandable and maintainable for Steve.

## Project Style

MRL is evolutionary. Requirements change as the season exposes new cases.

The preferred approach is:
- preserve working behavior
- make narrow, testable improvements
- use complete generated files/installers
- keep rollback paths
- avoid large rewrites unless they clearly solve a real problem

---

# Project DNA

- Preserve historical data.
- Preserve useful existing behavior.
- Prefer automation over repetitive manual work.
- Prefer clear, maintainable code over clever code.
- Favor small safe improvements over broad rewrites.
- Keep future maintenance simple for Steve.
- Use complete files rather than merge-by-hand snippets.
- Keep changes traceable with versions/changelogs.
- Test the real production path whenever practical.
- Keep TEST and LIVE behavior clearly separated.
- Do not disturb established cron/scheduler architecture without a real reason.

---

# Current High-Level Status

## Environment

Current production environment:
- Main live MRL site only.
- The former `testPHP8` / `MRL_testphp8` environment has been retired/shut down.
- PHP 8.3 review/migration cleanup remains an offseason task, but it should be performed against the current live/GitHub architecture rather than assuming the old testPHP8 workflow still exists.

## Stable Code Baseline

GitHub:
- repository: `stevekenney318/MRL`
- branch: `main`

Normal rule:
- GitHub main is the stable source of truth.

Active-iteration rule:
- if a file/subsystem is being actively changed and tested in the current chat, the newest tested chat version temporarily becomes the working source even if GitHub has not yet been updated.

## Current Season State

As of September 6, 2026:
- 2026 Segment 4 is named **The Chase**.
- Current S4 pick deadline: Sunday, September 6, 2026 at 5:00 PM ET.
- The 2026 season is still active.
- Steve expects major offseason / new-year planning to begin after the season, around November 9, 2026.

## Current Risk Level

Stable production with active incremental enhancements.

Race-results monitoring/scheduling is established and in production.
Pick Reminder is newly deployed and has passed end-to-end TEST automation; its first real LIVE automatic reminder is scheduled for the current S4 deadline cycle.

---

# Current Architecture

## Hostinger Cron / Master Scheduler

The long-standing Hostinger cron command remains unchanged and runs every minute:

`/usr/bin/php /home/u809830586/domains/manliusracingleague.com/public_html/race_results/cron_master_scheduler.php`

Current master scheduler:
- `/public_html/race_results/cron_master_scheduler.php`
- VERSION: v014

Current schedule file:
- `/public_html/race_results/_scheduler/schedule.json`

Current master schedule has three tasks:

1. `race_results_monitor`
2. `race_results_revision_monitor`
3. `pick_reminder`

Important rule:
- Do not change the Hostinger cron just because a new scheduled feature is added.
- Prefer adding/integrating a task through the proven master scheduler architecture.
- Avoid replacing the established master architecture during the active season unless required.

## Race Results Monitoring

Race Results Monitor:
- established and production-active

Race Results Revision Monitor:
- established and production-active
- older project-source notes that describe this as “planned” or “unfinished” are historical and should not be treated as current status

Dashboard / at-a-glance tools:
- active and mature enough for production use
- additional cleanup/toggle improvements remain on the roadmap

## Pick Reminder

Current production components:
- `/public_html/pick_reminder_dashboard.php` — v005
- `/public_html/pick_reminder_helper.php` — v003
- `/public_html/pick_reminder_scheduler.php` — v002
- `/public_html/race_results/pick_reminder_task.php` — v001 bridge into master scheduler

Master scheduler integration:
- Pick Reminder is task #3
- interval: every 1 minute
- execution method: URL/web path
- Hostinger cron and master scheduler code were not changed to support it

End-to-end TEST status:
- successful
- AUTO + TEST was scheduled through the real master scheduler path
- ID 999 received the email through the normal MRL Gmail/PHPMailer path
- dashboard history recorded `AUTO_TEST`
- scheduler state/status updates are working

Current LIVE design:
- Mode options: AUTO / MANUAL / OFF
- Scope options: TEST / LIVE
- TEST = ID 999 only
- LIVE excludes IDs 0 and 999
- LIVE rechecks missing picks immediately before sending
- one personalized email per missing team
- visible To: `manliusracingleague@gmail.com`
- team email address(es): BCC
- email uses the existing MRL Gmail/PHPMailer delivery path
- duplicate prevention is built into automatic sending
- LIVE AUTO requires explicit confirmation text before saving
- LIVE manual send requires explicit confirmation text

Current September 6 LIVE configuration:
- Mode: AUTO
- Scope: LIVE
- one active reminder offset: 2:00 before deadline
- blank reminder fields are ignored
- with 5:00 PM ET deadline, intended send opportunity is approximately 3:00 PM ET
- automatic scheduler remains active and checks every minute

Current message text begins with literal asterisks:
`** This is an auto-generated notification **`

Current wording:
`Just a reminder that {team_name} has not yet submitted picks for {year} {segment_name}.`

Current S4 status observed September 6:
- JPS5 submitted at 10:09:34 AM
- as of approximately 11:21 AM, Over The Edge was the only remaining missing team

Future Pick Reminder polish:
- optional checkboxes beside reminder slots so enabled/disabled state is more visually explicit than “blank means disabled”

---

# Team / Pick System

## Team Page

Current production baseline:
- `team.php` v046

Smart Pick Review:
- certified/working

Team View As:
- `team_view_as.php` v003
- ID 999 / MRL appears first/default where appropriate
- alternate team selection is preserved until reset
- reset restores admin/default behavior

## Pick Submission

Current production baseline:
- `submit-team-picks.php` v012

MRL Test Team:
- ID 999
- noncompetitive
- excluded from official scoring/standings
- used for controlled testing

Admin test reset utility:
- `admin_mrl_test_team_reset.php` v002
- can clear ID 999 pick/history data for a selected year when test reset is needed

## Submitted Teams / Missing Picks

Current `submitted_teams.php` S1 behavior:
- submitted teams can still be displayed
- missing-team calculation is intentionally skipped for S1
- the code only calculates missing teams when `$segment != 'S1'`

Reason:
- non-S1 missing-team logic derives a comparison roster from the previous segment’s `user_picks`
- S1 has no same-year prior segment

This becomes an important 2027/new-year planning item.

---

# Standings Direction

A major 2026 architecture decision:
- weekly standings should be driven by `mrl_segment` snapshots rather than relying only on older direct/legacy flow

This direction should be preserved when future standings work is resumed.

Current future standings work includes:
- scoring / `MRL_segment` work
- continued verification of weekly/segment/overall outputs
- existing comparison workflow against Jeff’s weekly report when needed

---

# Current Data / Important Sources

## Race Schedule JSON

`/race_results/_race_results_schedule.json`

Used by race/deadline tooling so schedule changes can propagate without manually hard-coding race times.

## Current Pick/Team Database Concepts

Important tables used by active systems include:
- `users`
- `user_teams`
- `user_picks`

Pick Reminder LIVE recipient logic depends on active current-year teams and absence of current-year/current-segment picks.

## Segment Snapshot Direction

`mrl_segment` is the intended snapshot basis for current/future weekly standings architecture.

---

# Deployment / Development Workflow

Steve’s normal workflow:
- ChatGPT generates complete files/installers.
- Steve downloads files.
- Steve uploads/runs them manually using WinSCP/browser as appropriate.
- GitHub Desktop + VS Code are used locally.
- Git CLI is not the preferred workflow.

Installer practice:
- use detailed preflight
- back up targets first
- make the narrow intended change
- validate/postflight
- auto-rollback on critical syntax/postflight failure when practical
- provide explicit rollback
- green Apply / blue neutral-save-info / red Rollback
- installer itself should not unexpectedly send mail or run a production job

Detailed rules are in:
`MRL-file-generation-and-versioning-standard.txt`

---

# Current Priorities

After the current Pick Reminder cycle is proven in LIVE use, likely next work areas are:

1. Team mobile sizing + footer improvements.
2. Race dashboard Race/Revision toggles + at-a-glance cleanup.
3. Backup Manager enhancement bundle.
4. Theme preview enhancements.
5. Scoring / `MRL_segment` work.
6. 2027 AUTO / S1 / S2 / S3 / S4 / OFF deadline system.
7. Offseason PHP 8.3 review / file-location cleanup as needed.

Steve may reorder these priorities.

---

# 2027 / “Start a New Year” Planning

This should be treated as a dedicated offseason project, likely beginning after the 2026 season ends (around November 9).

Topics to define deliberately:

- how/when the new-year `user_teams` roster is created
- when that roster becomes authoritative
- S1 submitted/missing-team behavior
- whether Pick Reminder should remain disabled for S1 until a valid 2027 roster exists
- whether a deliberate provisional-roster mode is useful
- transition from OFF → S1 → S2 → S3 → S4 → OFF
- new-year deadline configuration
- what gets reset
- what carries forward
- what gets archived
- how prior-year teams/users are handled
- how standings snapshots start for the new year
- season/year rollover checks
- any database/data cleanup required before R01

Do not automatically fall back to the prior year’s roster for S1 unless Steve explicitly decides that is the desired rule.

---

# Important Design Decisions

| Decision | Reason | Status |
|---|---|---|
| GitHub `MRL/main` is stable code baseline | Keeps one clear stable source | Active |
| Active tested chat version temporarily overrides GitHub during iteration | GitHub is often updated only after the current task stabilizes | Active |
| Keep one authoritative current project-status document | Prevents old planning notes from competing with current reality | Active |
| Use prior-chat exports for continuity/history | Preserves detailed reasoning across maxed/sluggish chats | Active |
| Preserve established Hostinger cron/master scheduler | Proven production architecture; reduce risk | Active |
| Add scheduled features through master scheduler when practical | Avoid parallel cron complexity | Active |
| Use TEST ID 999 for safe pick/reminder testing | Isolates production teams | Active |
| LIVE reminder rechecks missing picks before send | Prevents stale recipients | Active |
| `mrl_segment` snapshots drive standings direction | Preserves a stable weekly snapshot architecture | Active |
| S1 missing-team behavior requires explicit new-year design | No same-year prior segment exists | Active |

---

# Known Rejected / Avoided Approaches

| Approach | Why Avoided |
|---|---|
| Large rewrites without a demonstrated need | Higher risk to working production behavior |
| Replacing the long-standing Hostinger cron during active season | Unnecessary risk |
| Creating competing top-level scheduler architectures during active season | Master scheduler already solves the problem |
| Treating old project notes as current merely because they are project sources | Several April/June notes now describe completed systems as unfinished |
| Automatically assuming prior-year roster for S1 | Returning teams are not guaranteed |
| Asking Steve to merge partial code fragments manually | Complete files/installers are the normal workflow |

---

# Project Documentation Hierarchy

Use this order when multiple sources disagree:

1. Newest tested active-chat work for an unfinished iteration.
2. Stable GitHub `stevekenney318/MRL` main.
3. Latest `MRL_Project_Status.md`.
4. Current process standards, especially `MRL-file-generation-and-versioning-standard.txt`.
5. Older MRL roadmap/design/migration/handoff notes as historical/reference.
6. Prior-chat exports for detailed continuity/history.

Examples of older source documents that may contain valuable history but can be stale:
- `MRL-Roadmap.txt`
- `MRL-race_results_revision_monitor.txt`
- `MRL-mrl_impact_revision_classification.txt`
- `MRL_Engineering_Handoff_v001.md`
- `MRL-PHP-8-Migration.txt.txt`
- older Team/Pick reconstruction notes

Do not delete useful history merely because it is no longer current; just do not let it override this document.

---

# Chat Continuity / Migration

Very long MRL chats can become sluggish or eventually max out.

Steve uses ChatGPT Exporter to save a PDF before migration.

Current Main Chat 9 export in File Library:
`SAVE-Main Chat 9 20260825_193110699-20260906-1234.pdf`

Recommended new-chat starting instruction:

> Continue the MRL project from where Main Chat 9 left off. First read the latest MRL project-source documents, especially the current `MRL_Project_Status.md` and `MRL-file-generation-and-versioning-standard.txt`. Then read `SAVE-Main Chat 9 20260825_193110699-20260906-1234.pdf` from my File Library for detailed continuity and recent decisions. Treat newer active/tested chat versions as authoritative over GitHub during an unfinished iteration; otherwise use GitHub `MRL/main` as the stable code source of truth. Older project notes are historical unless clearly marked current.

This should be preferable to using the PDF alone as the project authority.

---

# Things the Next Chat Must Know

- MRL is an ongoing production NASCAR league system, not a one-time coding exercise.
- Steve is comfortable testing/administering the site but prefers complete generated files and installers rather than hand-merging code.
- GitHub main is stable truth except during an unfinished active chat iteration.
- Live production only; old testPHP8 environment is retired.
- Hostinger cron/master scheduler architecture is established and should be preserved.
- Race Monitor and Revision Monitor are production systems, not unfinished plans.
- Pick Reminder is integrated into the master scheduler and has passed end-to-end TEST automation.
- ID 999 is the safe MRL test team and is excluded from official competition.
- 2026 S4 is “The Chase.”
- S1 new-year roster/missing-team behavior remains a deliberate 2027 planning topic.
- Standings direction uses `mrl_segment` snapshots.
- Older project-source notes may be historical even if still present in the Project Sources list.

---

# Testing Checklist

## General

- [ ] Confirm target file/version/baseline.
- [ ] Confirm complete-file generation.
- [ ] Confirm syntax where applicable.
- [ ] Confirm expected UI behavior.
- [ ] Confirm old behavior still works.
- [ ] Confirm new behavior works.
- [ ] Confirm naming/version/header.
- [ ] Confirm no unintended production side effect.

## Installer

- [ ] Preflight expected baseline.
- [ ] Backup all targets.
- [ ] Apply only intended delta.
- [ ] Validate PHP/JSON/config.
- [ ] Postflight expected result.
- [ ] Preserve unrelated tasks/files.
- [ ] Roll back automatically on critical failure where practical.
- [ ] Provide explicit rollback.

## Scheduler / Email

- [ ] Confirm real scheduler path.
- [ ] Confirm TEST scope first where appropriate.
- [ ] Confirm LIVE exclusion rules.
- [ ] Confirm duplicate prevention.
- [ ] Recheck current DB state immediately before send/action.
- [ ] Confirm installer itself did not trigger the job unless intentionally designed to.

---

# Deployment Checklist

- [ ] Determine whether GitHub or current active chat version is the correct baseline.
- [ ] Back up current production targets.
- [ ] Upload only intended files.
- [ ] Run preflight.
- [ ] Apply.
- [ ] Verify live behavior.
- [ ] Roll back if required.
- [ ] Update GitHub after the active iteration stabilizes.
- [ ] Update this status document when a project-level state materially changes.

---

# Parking Lot / Future Ideas

- Pick Reminder enable checkbox beside each reminder slot.
- Team mobile sizing + footer.
- Race dashboard toggle/at-a-glance cleanup.
- Backup Manager enhancements.
- Theme preview enhancements.
- Standalone Driver Tracker / HTML-CSS presentation ideas using lite snapshot/JSON.
- Offseason PHP 8.3 review.
- File relocation/dependency cleanup for historical team charts.
- Formal 2027 “Start a New Year” workflow.

---

# Historical Notes

The April/June project-source documents remain useful as design history, but several describe systems that are now complete.

Examples:
- revision monitoring was once a planned top priority; it is now production-active
- testPHP8 was once the development/migration environment; it is now retired
- June handoff/status documents were created before many later scheduler/team/pick changes

Treat those files as historical context unless updated.

---

# Maintenance Instructions

When updating this file:

1. Start from the latest version.
2. Increment VERSION.
3. Update LAST MODIFIED using America/New_York.
4. Add a new changelog entry at the top.
5. Preserve useful older changelog entries.
6. Update the actual current state, not merely the latest planned state.
7. Mark obsolete items historical rather than silently deleting useful context.
8. Keep this document authoritative and readable; do not turn it into a transcript.
9. Store detailed history in chat exports and dedicated historical notes.
10. Regenerate the complete file.
