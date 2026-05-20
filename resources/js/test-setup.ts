// Vitest setup file. Wires up the globals that the production code reads
// off `window` (`getTimezoneSetting`, `getWeekStartSetting`, `getNumberFormat`,
// `getIntervalFormat`) so that helpers under test don't crash when imported
// outside the running app.

import { vi } from 'vitest';

declare global {
    interface Window {
        getTimezoneSetting: () => string;
        getWeekStartSetting: () => string;
        getNumberFormat: () => string;
        getIntervalFormat: () => string;
    }
}

window.getTimezoneSetting = vi.fn(() => 'UTC');
window.getWeekStartSetting = vi.fn(() => 'monday');
window.getNumberFormat = vi.fn(() => 'point');
window.getIntervalFormat = vi.fn(() => 'hours-minutes');
