import { expect, test } from '@playwright/test'

test.beforeEach(async ({ page }) => {
  await page.addInitScript(() => {
    document.addEventListener('DOMContentLoaded', () => {
      const style = document.createElement('style')
      style.textContent = '#__vue-devtools-container__ { display: none !important; }'
      document.head.append(style)
    })
  })
})

// Dragging needs a pointer; the phone-sized project covers the public pages.
test.skip(({ isMobile }) => isMobile, 'The calendar is built for desktop and tablet.')

test('staff book a phone customer and drag the booking to a new time', async ({ page }) => {
  await page.goto('/login')
  await page.getByLabel('E-post').fill('owner@bookingdirect.test')
  await page.getByLabel('Lösenord').fill('password')
  await page.getByRole('button', { name: 'Logga in' }).click()
  await expect(page).toHaveURL(/\/dashboard$/)

  // Two weeks ahead, late in the evening: staff may book outside opening hours.
  const day = new Date(Date.now() + 14 * 86_400_000).toISOString().slice(0, 10)
  await page.goto(`/dashboard/calendar?date=${day}`)

  await page.getByRole('button', { name: '+ Ny bokning' }).click()
  const dialog = page.getByRole('dialog')
  await dialog.getByLabel('Datum').fill(day)
  await dialog.getByLabel('Tid', { exact: true }).fill('19:00')
  await dialog.getByLabel('Namn').fill('Playwright Kund')
  await dialog.getByLabel('Telefon').fill(`070${String(Date.now()).slice(-7)}`)
  await dialog.getByRole('button', { name: 'Boka', exact: true }).click()

  const booking = page.getByRole('button', { name: /^Bokning 19:00, Playwright Kund/ })
  await expect(booking).toBeVisible()

  // Drag it up by thirty minutes (1.6 px per minute).
  await booking.scrollIntoViewIfNeeded()
  const box = await booking.boundingBox()
  if (!box) throw new Error('booking not rendered')
  await page.mouse.move(box.x + box.width / 2, box.y + 8)
  await page.mouse.down()
  await page.mouse.move(box.x + box.width / 2, box.y + 8 - 24, { steps: 4 })
  await page.mouse.move(box.x + box.width / 2, box.y + 8 - 48, { steps: 4 })
  await page.mouse.up()

  await expect(page.getByRole('button', { name: /^Bokning 18:30, Playwright Kund/ })).toBeVisible()
  await expect(page.getByText('Bokningen är flyttad.')).toBeVisible()

  // The move was saved, not just drawn.
  await page.reload()
  await expect(page.getByRole('button', { name: /^Bokning 18:30, Playwright Kund/ })).toBeVisible()
})
