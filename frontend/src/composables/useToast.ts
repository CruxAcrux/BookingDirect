import { readonly, ref } from 'vue'

export interface Toast {
  id: number
  message: string
  tone: 'success' | 'error'
}

const toasts = ref<Toast[]>([])
let nextId = 1

function dismiss(id: number): void {
  toasts.value = toasts.value.filter((toast) => toast.id !== id)
}

function show(message: string, tone: Toast['tone'] = 'success'): void {
  const id = nextId++
  toasts.value = [...toasts.value, { id, message, tone }]
  setTimeout(() => {
    dismiss(id)
  }, 4000)
}

export function useToast() {
  return { toasts: readonly(toasts), show, dismiss }
}
