import { test, expect } from '@playwright/test'
import AxeBuilder from '@axe-core/playwright'

const KNOWN_ISSUES = [
    'aria-allowed-attr',
    'color-contrast',
    'label',
    'region',
]

test.describe('Accessibility audits', () => {
    test('login page has no violations', async ({ page }) => {
        await page.goto('/login')
        await page.waitForSelector('h1', { timeout: 15000 })

        const results = await new AxeBuilder({ page }).analyze()

        const violations = results.violations.filter(v => !KNOWN_ISSUES.includes(v.id))
        expect(violations).toEqual([])
    })

    test('register page has no violations', async ({ page }) => {
        await page.goto('/register')
        await page.waitForSelector('h1', { timeout: 15000 })

        const results = await new AxeBuilder({ page }).analyze()

        const violations = results.violations.filter(v => !KNOWN_ISSUES.includes(v.id))
        expect(violations).toEqual([])
    })

    test('dashboard is not accessible without login', async ({ page }) => {
        await page.goto('/dashboard')
        await page.waitForURL('/login')
        await expect(page.locator('h1')).toContainText('Sign In')
    })
})
