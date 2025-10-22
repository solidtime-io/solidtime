Title: PR2 — Planner UI + API (Scaffold)

Summary
- Minimal, gated scaffolding for Planner API and Vue pages. No behavioral changes when planner is disabled.

Backend
- Models: PhaseTemplate, MilestoneTemplate, ProjectPhase, PhaseMilestone, PlannerRule, UserAlias (Eloquent shells)
- Controllers: Api/V1/PlannerController (index stub), Api/V1/MilestoneController (update stub)
- Routes: API routes under /api/v1/organizations/{organization}/planner, gated by config('planner.enabled')
- Web routes: /planner and /projects/{project}/planner gated by config('planner.enabled') rendering Inertia pages

Frontend
- Pages: resources/js/Pages/PlannerMatrix.vue and ProjectPlanner.vue (skeleton UI)

Gating
- All new routes return 404 when planner.enabled is false.

Next steps
- Materialize phases/milestones on project create (PlannerController + service)
- CRUD on milestones; compute RAG on server
- Filters (Phase/Status/RAG) and upcoming sidebar in Vue
- Keep diffs minimal and behind flag
