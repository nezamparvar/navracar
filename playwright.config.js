import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['line'], ['html', { open: 'never' }]] : 'line',
    use: {
        baseURL: 'http://127.0.0.1:8000',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    webServer: {
        command: 'node tests/e2e/serve.mjs',
        url: 'http://127.0.0.1:8000/up',
        reuseExistingServer: false,
        timeout: 120_000,
    },
    projects: [
        { name: '360x800', use: { browserName: 'chromium', viewport: { width: 360, height: 800 } } },
        { name: '375x812', use: { browserName: 'chromium', viewport: { width: 375, height: 812 } } },
        { name: '390x844', use: { browserName: 'chromium', viewport: { width: 390, height: 844 } } },
        { name: '430x932', use: { browserName: 'chromium', viewport: { width: 430, height: 932 } } },
        { name: '768x1024', use: { browserName: 'chromium', viewport: { width: 768, height: 1024 } } },
        { name: '820x1180', use: { browserName: 'chromium', viewport: { width: 820, height: 1180 } } },
        { name: '1024x1366', use: { browserName: 'chromium', viewport: { width: 1024, height: 1366 } } },
        { name: '1280x800', use: { browserName: 'chromium', viewport: { width: 1280, height: 800 } } },
        { name: '1440x900', use: { browserName: 'chromium', viewport: { width: 1440, height: 900 } } },
        { name: '1536x960', use: { browserName: 'chromium', viewport: { width: 1536, height: 960 } } },
        { name: '1920x1080', use: { browserName: 'chromium', viewport: { width: 1920, height: 1080 } } },
    ],
});
