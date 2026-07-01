import { test, expect } from '@playwright/test'

test.describe('Authentication Flow', () => {
    test('landing page redirects to login for guests', async ({ page }) => {
        await page.goto('/', { waitUntil: 'domcontentloaded' })
        await page.waitForURL('/login', { timeout: 15000 })
        await page.waitForSelector('h1', { timeout: 10000 })
        await expect(page.locator('h1')).toContainText('Sign In')
    })

    test('login page loads with heading', async ({ page }) => {
        await page.goto('/login', { waitUntil: 'domcontentloaded' })
        await page.waitForSelector('h1', { timeout: 15000 })
        await expect(page.locator('h1')).toContainText('Sign In')
    })

    test('register page loads with heading', async ({ page }) => {
        await page.goto('/register', { waitUntil: 'domcontentloaded' })
        await page.waitForSelector('h1', { timeout: 15000 })
        await expect(page.locator('h1')).toContainText('Create Account')
    })

    test('links between login and register', async ({ page }) => {
        await page.goto('/login', { waitUntil: 'domcontentloaded' })
        await page.waitForSelector('h1', { timeout: 15000 })

        await page.getByText('Create Account').click()
        await page.waitForURL('/register', { timeout: 10000 })
        await expect(page.locator('h1')).toContainText('Create Account')
    })

    test('dashboard redirects to login for guests', async ({ page }) => {
        await page.goto('/dashboard', { waitUntil: 'domcontentloaded' })
        await page.waitForURL('/login', { timeout: 15000 })
        await expect(page.locator('h1')).toContainText('Sign In')
    })
})
