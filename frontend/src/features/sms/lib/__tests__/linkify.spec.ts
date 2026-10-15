import { describe, expect, it } from 'vitest'

import { linkify } from '../linkify'

describe('linkify', () => {
  it('splits links out of the text and leaves sentence punctuation outside', () => {
    expect(linkify('Change: http://localhost:5173/b/abc123.\nSee you!')).toEqual([
      { text: 'Change: ' },
      { text: 'http://localhost:5173/b/abc123', href: 'http://localhost:5173/b/abc123' },
      { text: '.\nSee you!' },
    ])
  })

  it('ignores text that only looks like a link', () => {
    expect(linkify('javascript:alert(1) and www.example.com')).toEqual([
      { text: 'javascript:alert(1) and www.example.com' },
    ])
  })
})
