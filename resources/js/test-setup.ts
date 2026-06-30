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

// happy-dom has no layout engine, so every element reports offsetWidth/offsetHeight of 0.
// TanStack Virtual (used by the project/task dropdown) measures via those properties, so
// without a size it renders zero rows. Give elements a usable box so virtualized components
// render their rows in component tests.
Object.defineProperty(HTMLElement.prototype, 'offsetWidth', { configurable: true, get: () => 400 });
Object.defineProperty(HTMLElement.prototype, 'offsetHeight', {
    configurable: true,
    get: () => 350,
});
