export type TextPart = { text: string; href?: undefined } | { text: string; href: string }

const URL_PATTERN = /https?:\/\/[^\s<>"]+/g
/** Punctuation that ends a sentence rather than the link, e.g. "…/b/abc." */
const TRAILING = /[.,!?;:)]+$/

/** Splits message text into plain parts and links, so links can be rendered without v-html. */
export function linkify(text: string): TextPart[] {
  const parts: TextPart[] = []
  let last = 0

  for (const match of text.matchAll(URL_PATTERN)) {
    const url = match[0].replace(TRAILING, '')
    const start = match.index
    if (start > last) parts.push({ text: text.slice(last, start) })
    parts.push({ text: url, href: url })
    last = start + url.length
  }
  if (last < text.length) parts.push({ text: text.slice(last) })

  return parts
}
