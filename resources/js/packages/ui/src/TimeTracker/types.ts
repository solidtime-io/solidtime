/**
 * How the time tracker presents its controls. `project` shows the full
 * project/task/tag/billable controls; `simple` hides them for plain
 * description-only tracking. Persisted client-side as a UI preference.
 */
export type TimeTrackerMode = 'project' | 'simple';
