import { describe, expect, it } from 'vitest'

import en from '@/locales/en.json'
import sv from '@/locales/sv.json'

function keyPaths(value: unknown, prefix = ''): string[] {
  if (typeof value !== 'object' || value === null) return [prefix]
  return Object.entries(value).flatMap(([key, child]) =>
    keyPaths(child, prefix ? `${prefix}.${key}` : key),
  )
}

describe('locale files', () => {
  it('define the same keys in Swedish and English', () => {
    expect(keyPaths(en).sort()).toEqual(keyPaths(sv).sort())
  })
})
