import { customRef, watch, type Ref } from 'vue'

/** A read-only copy of `source` that only updates after it has been still for `delay` ms. */
export function refDebounced<T>(source: Ref<T>, delay: number): Readonly<Ref<T>> {
  let timer: ReturnType<typeof setTimeout> | undefined
  let value = source.value

  return customRef((track, trigger) => {
    watch(source, (next) => {
      clearTimeout(timer)
      timer = setTimeout(() => {
        value = next
        trigger()
      }, delay)
    })

    return {
      get: () => {
        track()
        return value
      },
      set: () => {},
    }
  })
}
