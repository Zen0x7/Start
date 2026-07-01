import { defineConfig } from '@playwright/test'

export default defineConfig({
    testDir: './tests/E2E',
    fullyParallel: true,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: 'html',
    use: {
        baseURL: process.env.CI
            ? 'http://localhost:8000'
            : 'http://localhost:8000',
        trace: 'on-first-retry',
    },
    webServer: {
        command: 'php artisan serve --port=8000 & yarn dev &',
        port: 8000,
        reuseExistingServer: !process.env.CI,
        timeout: 30000,
    },
})
