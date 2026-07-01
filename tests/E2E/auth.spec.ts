import { test, expect } from '@playwright/test'

test.describe('Authentication Flow', () => {
    test('register page loads and has form fields', async ({ page }) => {
        await page.goto('/register')

        await expect(page.locator('h1')).toHaveText('Crear Cuenta')
        await expect(page.locator('#name')).toBeVisible()
        await expect(page.locator('#email')).toBeVisible()
        await expect(page.locator('#password')).toBeVisible()
        await expect(page.locator('#password-confirmation')).toBeVisible()
        await expect(page.getByRole('button', { name: 'Registrarse' })).toBeVisible()
    })

    test('register page links to login', async ({ page }) => {
        await page.goto('/register')

        await page.click('text=Iniciar Sesión')
        await expect(page).toHaveURL('/login')
    })

    test('login page loads and has form fields', async ({ page }) => {
        await page.goto('/login')

        await expect(page.locator('h1')).toHaveText('Iniciar Sesión')
        await expect(page.locator('#email')).toBeVisible()
        await expect(page.locator('#password')).toBeVisible()
        await expect(page.getByRole('button', { name: 'Iniciar Sesión' })).toBeVisible()
    })

    test('login page links to register', async ({ page }) => {
        await page.goto('/login')

        await page.click('text=Registrarse')
        await expect(page).toHaveURL('/register')
    })

    test('register with empty fields shows validation', async ({ page }) => {
        await page.goto('/register')

        // HTML5 validation should prevent submission of empty form
        // Submit with empty fields to trigger browser validation
        await page.getByRole('button', { name: 'Registrarse' }).click()

        // Browser validation prevents submission, we should stay on register page
        await expect(page).toHaveURL('/register')
    })

    test('login with empty fields shows validation', async ({ page }) => {
        await page.goto('/login')

        await page.getByRole('button', { name: 'Iniciar Sesión' }).click()

        await expect(page).toHaveURL('/login')
    })

    test('navigation to email verify page shows waiting message', async ({ page }) => {
        await page.goto('/email/verify?email=test@example.com')

        await expect(page.locator('h1')).toHaveText(
            'Antes de continuar deberás confirmar tu correo electrónico',
        )
        await expect(page.getByRole('button', { name: 'Reenviar correo de verificación' })).toBeVisible()
    })

    test('homepage loads after logout', async ({ page }) => {
        await page.goto('/')
        await expect(page.locator('h1')).toHaveText('Vue 3 + TypeScript + Laravel')
    })
})
