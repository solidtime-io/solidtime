import { existsSync, readFileSync } from 'node:fs';

export const PLAYWRIGHT_BASE_URL = process.env.PLAYWRIGHT_BASE_URL ?? 'http://solidtime.test';
export const MAILPIT_BASE_URL = process.env.MAILPIT_BASE_URL ?? 'http://mailpit:8025';
export const TEST_USER_PASSWORD = 'amazingpassword123';

/**
 * Extensions that are enabled in modules_statuses.json (php artisan module:enable <Name>)
 */
export function enabledExtensions(): string[] {
    const statusesFile = './modules_statuses.json';
    if (!existsSync(statusesFile)) {
        return [];
    }
    const statuses: Record<string, boolean> = JSON.parse(readFileSync(statusesFile, 'utf-8'));
    return Object.entries(statuses)
        .filter(([, enabled]) => enabled)
        .map(([name]) => name);
}

export function isExtensionEnabled(name: string): boolean {
    return enabledExtensions().includes(name);
}
