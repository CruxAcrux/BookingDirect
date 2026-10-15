import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it } from 'vitest'

// Read the raw files: imports go through the i18n plugin, which compiles messages.
const load = (locale: string): unknown =>
  JSON.parse(readFileSync(resolve(import.meta.dirname, `../../locales/${locale}.json`), 'utf8'))

function keyPaths(value: unknown, prefix = ''): string[] {
  if (typeof value !== 'object' || value === null) return [prefix]
  return Object.entries(value).flatMap(([key, child]) =>
    keyPaths(child, prefix ? `${prefix}.${key}` : key),
  )
}

describe('locale files', () => {
  it('define the same keys in Swedish and English', () => {
    expect(keyPaths(load('en')).sort()).toEqual(keyPaths(load('sv')).sort())
  })
})
