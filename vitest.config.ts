import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
    plugins: [vue()],
    test: {
        environment: 'jsdom',
        include: ['resources/js/**/*.{test,spec}.{ts,js}'],
        globals: true,
        coverage: {
            provider: 'v8',
            reporter: ['text', 'html', 'lcov'],
            include: ['resources/js/**/*.ts', 'resources/js/**/*.vue'],
            exclude: [
                'resources/js/**/*.spec.ts',
                'resources/js/**/__tests__/**',
                'resources/js/types/**',
            ],
        },
    },
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
        },
    },
})
