import { test, expect } from '@playwright/test'
import AxeBuilder from '@axe-core/playwright'

test.describe('Accessibility audits', () => {
    test('homepage has no WCAG violations', async ({ page }) => {
        await page.goto('/')
        await page.waitForLoadState('networkidle')

        const results = await new AxeBuilder({ page }).analyze()

        expect(results.violations).toEqual([])
    })

    test('login page has no WCAG violations', async ({ page }) => {
        await page.goto('/login')
        await page.waitForLoadState('networkidle')

        const results = await new AxeBuilder({ page }).analyze()

        expect(results.violations).toEqual([])
    })

    test('register page has no WCAG violations', async ({ page }) => {
        await page.goto('/register')
        await page.waitForLoadState('networkidle')

        const results = await new AxeBuilder({ page }).analyze()

        expect(results.violations).toEqual([])
    })
})
