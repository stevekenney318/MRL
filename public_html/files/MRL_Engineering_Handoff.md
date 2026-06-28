# MRL Engineering Handoff

> Generated from the current chat and uploaded project notes only.
> Unknown items are marked TBD rather than inferred.

## Project State

The project is stable and has transitioned from foundational
race-results infrastructure to refinement and integration work.

Working systems confirmed during this chat: - Adaptive cron master
scheduler - Race Results Monitor - Race Results Revision Monitor -
Release-history metadata generation - Weekly Standings revision/version
UI - Standings Timeline Lite proof of concept

## Current Objective

Integrate the Standings Timeline Lite concept directly into Weekly
Standings so the user experiences one presentation layer with two data
modes: - Current standings - Snapshot (as-of) standings

## Completed

-   Adaptive scheduler architecture deployed and healthy.
-   Revision monitor detects revisions, classifies MRL impact, maintains
    release metadata.
-   Weekly Standings supports release/version selection.
-   Standings Timeline Lite successfully demonstrates locked "as-of"
    browsing.
-   Weekly Winners segment-color concept validated.

## Active Work

-   Integrate Timeline Lite into Weekly Standings.
-   Continue Timeline UI polish.
-   Roll segment-colored Weekly Winners into Weekly Standings.
-   Improve dashboard NASCAR At a Glance widget.

## Important Design Decisions

-   Weekly Standings is the long-term presentation layer.
-   Timeline Lite is intended to become a mode of Weekly Standings
    rather than remain a separate page.
-   Full Standings Timeline remains the historical audit/browser tool.
-   Preserve snapshot history and auditability.
-   Favor installer-based incremental deployment before GitHub commits.

## Architecture

Race Results Monitor → captures live race snapshots

Revision Monitor → detects later ESPN revisions → updates release
history → classifies MRL impact

Weekly Standings → primary presentation layer

Standings Timeline Lite → snapshot/as-of presentation prototype

Standings Timeline → audit/history browser

## Automation Workflow

Hostinger cron calls a master scheduler every minute.

The scheduler determines when to invoke: - race_results_monitor -
race_results_revision_monitor

Cadence automatically changes based on race timing and post-race state.

## Deployment Workflow

Develop locally. Generate complete versioned files. Deploy using
installers. Validate. Commit to GitHub after stabilization.

## Known Issues

-   Timeline visual polish remains.
-   NASCAR At a Glance should ignore Truck/Xfinity stale data and focus
    on Cup.
-   Naming around Timeline vs Snapshot remains under evaluation.

## Technical Debt

-   Timeline UI and Weekly Standings should converge into a single
    renderer.
-   Some terminology predates the current snapshot architecture.

## Open Questions

-   Final naming:
    -   Snapshot Standings?
    -   Snapshot Browser?
-   Optional comparison mode:
    -   Snapshot panels
    -   Current panels

## Things That Must Not Change

-   Preserve historical snapshots.
-   Preserve audit trail.
-   Preserve installer-first workflow.
-   Preserve versioned file generation.
-   Do not guess unknown project facts.

## Important Assumptions

-   Weekly Standings remains the user-facing home.
-   Timeline Lite evolves into integrated mode.
-   Full Timeline remains a power-user audit tool.

## Tried and Rejected

-   Frequent large rewrites.
-   Multiple independent presentation layers for similar data.

## Files Being Modified

-   weekly_standings.php
-   standings_timeline.php
-   standings_timeline_lite.php
-   release-history helper
-   revision monitor

## Current Priorities

1.  Timeline Lite integration.
2.  Weekly Winners segment colors.
3.  Timeline polish.
4.  Dashboard Cup-only At-a-Glance.
5.  Continue engineering notebook updates.

## Handoff

If another engineer takes over: - Preserve architecture before polishing
UI. - Prefer incremental evolution over redesign. - Treat project
documents as authoritative. - Fill unknowns with TBD rather than
assumptions.
