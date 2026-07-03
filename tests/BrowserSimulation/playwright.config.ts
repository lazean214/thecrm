import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: '.',
    timeout: 60000,
    fullyParallel: false,
    retries: 0,
    workers: 1,
    use: {
        baseURL: 'http://localhost:8000',
        headless: false,
        viewport: { width: 1280, height: 720 },
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    webServer: {
        command: 'php artisan serve --port=8000',
        url: 'http://localhost:8000',
        reuseExistingServer: true,
    },
});
