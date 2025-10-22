Title: PR1 — Planner Schema + Seeds

Summary
- Introduces planner schema and seed data, gated by config('planner.enabled') via PLANNER_ENABLED (fallback to PIA_ENABLED).
- Adds canonical planner templates and demo projects as per spec.

Migrations (added)
- 2025_10_21_120000_add_due_at_and_is_milestone_to_tasks_table.php
- 2025_10_21_121000_create_project_phase_templates_table.php
- 2025_10_21_121100_create_project_milestone_templates_table.php
- 2025_10_22_000050_add_milestone_id_to_time_entries_table.php
- 2025_10_23_000001_create_phase_templates_table.php
- 2025_10_23_000002_create_milestone_templates_table.php
- 2025_10_23_000003_create_project_phases_table.php
- 2025_10_23_000004_create_phase_milestones_table.php
- 2025_10_23_000005_create_planner_rules_table.php
- 2025_10_23_000006_create_user_aliases_table.php

Seeds
- PlannerTemplateSeeder: seeds canonical phases/milestones exactly per spec
  • Phase 1 – Concept Design
  • Phase 2 – Design Development
  • Phase 3 – Specification
  • Phase 4 – Implementation (key coordination checkpoints)
  • Implementation (site and supplier schedule checkpoints)
- DemoProjectsSeeder: Hendrick Avenue; Hill Rise; Shaftesbury Villas 3; Shaftesbury Villas 4; Hotham Road; Manor Road; Holly House
- SeedProjectPlannerForAllProjectsSeeder: associates seeded templates to existing projects where applicable

Feature gating
- config/planner.php contains:
  • enabled: env('PLANNER_ENABLED', env('PIA_ENABLED', false))
  • default_leadtime_days and alert_window_days with env overrides
- DatabaseSeeder triggers planner template seeding only when config('pia.enabled') and config('pia.templates.auto_seed') to preserve defaults when disabled.

Acceptance
- Default Solidtime behavior unchanged when disabled.
- New tables are additive; existing endpoints unchanged.

How to test
- Set PIA_ENABLED=true and pia.templates.auto_seed=true then run: php artisan migrate --seed
- Verify demo projects exist and the canonical templates are populated.
- Set PLANNER_ENABLED=false and verify no planner UI/API is exposed.
