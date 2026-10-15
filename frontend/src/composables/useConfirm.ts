import { readonly, ref } from 'vue'

export interface ConfirmOptions {
  title: string
  body?: string
  confirmLabel: string
  tone?: 'danger' | 'default'
}

interface PendingConfirm extends ConfirmOptions {
  resolve: (confirmed: boolean) => void
}

const pending = ref<PendingConfirm | null>(null)

/** Asks the user to confirm in a modal dialog. Resolves to false when cancelled. */
function confirm(options: ConfirmOptions): Promise<boolean> {
  pending.value?.resolve(false)
  return new Promise((resolve) => {
    pending.value = { ...options, resolve }
  })
}

function settle(confirmed: boolean): void {
  pending.value?.resolve(confirmed)
  pending.value = null
}

export function useConfirm() {
  return { pending: readonly(pending), confirm, settle }
}
