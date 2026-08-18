import { defineConfig } from '@playwright/test';

const responsiveViewports = [
    ['320x568', 320, 568],
    ['360x800', 360, 800],
    ['375x812', 375, 812],
    ['390x844', 390, 844],
    ['430x932', 430, 932],
    ['768x1024', 768, 1024],
    ['820x1180', 820, 1180],
    ['1024x1366', 1024, 1366],
    ['1280x800', 1280, 800],
    ['1440x900', 1440, 900],
    ['1536x960', 1536, 960],
    ['1920x1080', 1920, 1080],
];

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['line'], ['html', { open: 'never' }]] : 'line',
    use: {
        baseURL: 'http://127.0.0.1:8000',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        // --no-proxy-server: without it, Chromium's default system-proxy auto-detection adds a
        // flat ~13s delay to EVERY navigation on machines with an HTTPS_PROXY env var set but no
        // HTTP_PROXY (this sandbox's setup) — reproduced directly, confirmed fixed by this flag
        // alone. All navigations in this suite target 127.0.0.1 only, so a proxy is never wanted.
        launchOptions: { args: ['--no-proxy-server'], executablePath: '/opt/pw-browsers/chromium' },
    },
    webServer: {
        command: 'node tests/e2e/serve.mjs',
        url: 'http://127.0.0.1:8000/up',
        reuseExistingServer: false,
        timeout: 120_000,
    },
    projects: [
        {
            name: 'functional-mobile',
            testMatch: /critical-flows\.spec\.js/,
            use: { browserName: 'chromium', viewport: { width: 375, height: 812 } },
        },
        {
            name: 'functional-desktop',
            testMatch: /(calculator-wizard|critical-flows)\.spec\.js/,
            use: { browserName: 'chromium', viewport: { width: 1280, height: 800 } },
        },
        {
            name: 'accessibility-mobile',
            testMatch: /accessibility\.spec\.js/,
            use: { browserName: 'chromium', viewport: { width: 375, height: 812 } },
        },
        {
            name: 'accessibility-desktop',
            testMatch: /accessibility\.spec\.js/,
            use: { browserName: 'chromium', viewport: { width: 1280, height: 800 } },
        },
        ...responsiveViewports.map(([name, width, height]) => ({
            name: `responsive-${name}`,
            testMatch: /responsive\.spec\.js/,
            use: { browserName: 'chromium', viewport: { width, height } },
        })),
    ],
});
