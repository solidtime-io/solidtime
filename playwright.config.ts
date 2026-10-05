import { defineConfig, devices } from '@playwright/test';
import { existsSync, readFileSync } from 'node:fs';

/**
 * Read environment variables from file.
 * https://github.com/motdotla/dotenv
 */
// require('dotenv').config();

/**
 * Extensions ship their e2e specs in extensions/<Name>/tests/e2e. Only the specs of extensions
 * that are enabled in modules_statuses.json (php artisan module:enable <Name>) are collected,
 * a checked out but disabled extension would fail its specs otherwise.
 */
function enabledExtensions(): string[] {
    const statusesFile = './modules_statuses.json';
    if (!existsSync(statusesFile)) {
        return [];
    }
    const statuses: Record<string, boolean> = JSON.parse(readFileSync(statusesFile, 'utf-8'));
    return Object.entries(statuses)
        .filter(([, enabled]) => enabled)
        .map(([name]) => name);
}

/*
 * Every test root gets its own testDir (instead of testDir: '.') so playwright does not walk the
 * whole repository, including node_modules, vendor and nested worktrees, to find the specs.
 */
const testRoots = [
    { suffix: '', testDir: './e2e' },
    ...enabledExtensions().map((name) => ({
        suffix: '-' + name,
        testDir: `./extensions/${name}/tests/e2e`,
    })),
];

const browsers = [
    {
        name: 'chromium',
        use: { ...devices['Desktop Chrome'] },
    },

    // Firefox only in CI to keep local runs fast
    ...(process.env.CI
        ? [
              {
                  name: 'firefox',
                  use: { ...devices['Desktop Firefox'] },
              },
          ]
        : []),
];

/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
    /* Resolves the @e2e/* and @e2e-support/* aliases that extension specs import core helpers with */
    tsconfig: './tsconfig.json',
    /* Run tests in files in parallel */
    fullyParallel: true,
    /* Fail the build on CI if you accidentally left test.only in the source code. */
    forbidOnly: !!process.env.CI,
    /* Retry on CI only */
    retries: process.env.CI ? 1 : 0,
    /* Run tests in parallel */
    workers: process.env.CI ? 2 : 4,
    /* Reporter to use. See https://playwright.dev/docs/test-reporters */
    reporter: process.env.CI ? 'blob' : 'html',
    /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
    use: {
        /* Base URL to use in actions like `await page.goto('/')`. */
        // baseURL: 'http://127.0.0.1:3000',

        /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
        trace: process.env.CI ? 'on-first-retry' : 'on',
    },

    timeout: 20 * 1000,

    /* Configure projects for major browsers, core specs keep the plain browser project name */
    projects: browsers.flatMap((browser) =>
        testRoots.map((root) => ({
            name: browser.name + root.suffix,
            testDir: root.testDir,
            use: browser.use,
        }))
    ),

    /* Run your local dev server before starting the tests */
    // webServer: {
    //   command: 'npm run start',
    //   url: 'http://127.0.0.1:3000',
    //   reuseExistingServer: !process.env.CI,
    // },
});
