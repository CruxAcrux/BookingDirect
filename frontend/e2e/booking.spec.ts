import { expect, test } from '@playwright/test'

// A fresh number each run, so the per-number SMS code limit is never hit.
const phone = () => `070${String(Math.floor(Math.random() * 10_000_000)).padStart(7, '0')}`

// The dev server's devtools button floats over the page; it does not exist in production builds.
test.beforeEach(async ({ page }) => {
  await page.addInitScript(() => {
    document.addEventListener('DOMContentLoaded', () => {
      const style = document.createElement('style')
      style.textContent = '#__vue-devtools-container__ { display: none !important; }'
      document.head.append(style)
    })
  })
})

test('a customer books a haircut from the shop page', async ({ page }) => {
  await page.goto('/s/fade-studio')
  await expect(page.getByRole('heading', { level: 1, name: 'Fade Studio Göteborg' })).toBeVisible()

  await page.getByRole('link', { name: 'Boka tid' }).last().click()

  await page.getByRole('button', { name: /Skinfade/ }).click()
  await page.getByRole('button', { name: /Första lediga/ }).click()

  const days = page.getByRole('radiogroup', { name: 'Dag' })
  await days.getByRole('radio', { disabled: false }).nth(1).click()
  const time = page.getByRole('radiogroup', { name: 'Tid' }).getByRole('radio').first()
  const chosen = (await time.locator('span').first().innerText()).trim()
  await time.click()

  await page.getByLabel('Namn', { exact: true }).fill('Kim Andersson')
  await page.getByLabel('Mobilnummer').fill(phone())
  await page.getByRole('button', { name: 'Skicka kod' }).click()

  const demo = page.getByRole('status').filter({ hasText: 'Demokod' })
  const code = (await demo.innerText()).match(/\d{6}/)?.[0] ?? ''
  await page.getByLabel('Kod från sms').fill(code)
  await page.getByRole('button', { name: 'Bekräfta bokningen' }).click()

  await expect(page).toHaveURL(/\/b\/[A-Za-z0-9]{40}\?new=1$/)
  await expect(page.getByRole('heading', { name: 'Du är bokad.' })).toBeVisible()
  const ticket = page.getByRole('article', { name: 'Din bokning' })
  await expect(ticket).toContainText(chosen)
  await expect(ticket).toContainText('Skinfade')
  await expect(ticket).toContainText('Bekräftad')

  // The manage link works on its own, e.g. opened later from an SMS.
  await page.reload()
  await expect(page.getByRole('article', { name: 'Din bokning' })).toContainText(chosen)
})
