# Pia Design – Solidtime Adaptation Plan (pia-time)

Goal: Fork Solidtime with minimal diffs to support PiaDesign’s Project Coordination Planner so the team can:
- Plan across multiple projects
- Track milestones by phase
- Receive weekly and deadline alerts
- Keep diffs small for easy rebases

Core approach (minimal-diff):
- Reuse Solidtime Projects as “Projects/Programs” and Tasks as “Milestones/Activities”.
- Add only two backend fields to preserve compatibility:
  - `tasks.due_at` (datetime, nullable) for milestone/activity deadlines
  - `tasks.is_milestone` (boolean, default false) to mark a task as a milestone (UI can later style these)
- Use existing `tags` to represent “phases” (e.g., Discovery, Design, Build, Launch) to avoid schema changes.
- Weekly/Deadline alerts: add a feature-flagged console command to email a digest of upcoming/overdue tasks (by due_at) to project members.

Mapping from Project Coordination Planner template:
- Project sheet/rows → Solidtime `projects`
- Phases (Discovery/Design/Build/etc.) → `tags` attached to `tasks`
- Milestone rows → `tasks` with `is_milestone = true`
- Target dates → `tasks.due_at`
- Status/Done → `tasks.done_at` (already exists)
- Estimates/budgets → `estimated_time` (already exists on tasks/projects). If budget tracking is needed, use tags or a custom reporting view initially.

Feature flags and configuration:
- Add `config/pia.php`:
  - `enabled` (default false) to keep behavior identical unless turned on.
  - `alerts.enabled` (default false)
  - `alerts.reminder_days` (array) e.g., [1, 3, 7] before `due_at`
  - `alerts.weekly_digest_weekday` (string) e.g., 'monday'
- Scheduling: when enabled, register a cron/scheduler entry that dispatches reminder/weekly jobs.

Frontend (phase 1 – minimal):
- No UI changes required to start. `due_at` and `is_milestone` remain backend-only until we expose them.
- We can use the existing Tasks views and API, extending later to show due dates, phase tags, and milestone badges.

API
- Extend Task DTO to include `due_at` and `is_milestone` when feature flag is on (follow-up PR).
- Filtering helpers (follow-up): `?due_before`, `?due_after`, `?is_milestone=1`, `?tag=PhaseName`.

Notifications (phase 2):
- Implement console command and mail template for reminders and weekly digests.
- Target recipients: project members (owners, managers by role) – confirm desired policy.

Rebase strategy:
- Keep changes confined to: one migration, one config file, optional console command, optional mail template. Avoid core logic changes.
- Guard nonessential behavior behind `config('pia.enabled')` flags.

Open questions to confirm:
1) Exact phase list from the current Planner template? (We’ll seed tags.)
2) Who should receive reminders (owners only, project members, or specific roles)?
3) Reminder cadence (1/3/7 days? custom?) and time of day/timezone policy.
4) Do we need project-level `due_at` as well? (For now: task-level only.)

Next steps (proposed):
- [This PR] Add config flag and migration only (no behavior change).
- [Follow-up] Seed phase tags based on your template (Discovery, Design, Build, etc.).
- [Follow-up] Implement alerts behind feature flag.
- [Follow-up] Expose `due_at` and `is_milestone` in API + minimal UI badges.
