/** Moves the item at `index` one step up or down and returns the new id order. */
export function moveId(
  items: readonly { id: number }[],
  index: number,
  direction: -1 | 1,
): number[] {
  const ids = items.map((item) => item.id)
  const target = index + direction
  if (target < 0 || target >= ids.length) return ids

  const [moved] = ids.splice(index, 1)
  if (moved !== undefined) ids.splice(target, 0, moved)
  return ids
}
