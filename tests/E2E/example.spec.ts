import { test, expect } from '@playwright/test'

test('homepage loads and counter works', async ({ page }) => {
    await page.goto('/')

    await expect(page.locator('h1')).toHaveText('Vue 3 + TypeScript + Laravel')

    const counter = page.locator('text=0')
    await expect(counter).toBeVisible()

    await page.click('text=+')
    await expect(page.locator('text=1')).toBeVisible()

    await page.click('text=Reset')
    await expect(page.locator('text=0')).toBeVisible()
})
