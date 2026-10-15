<script setup lang="ts">
import { nextTick, useTemplateRef, watch } from 'vue'
import { useI18n } from 'vue-i18n'

import { useConfirm } from '@/composables/useConfirm'

import AppButton from './AppButton.vue'

const { t } = useI18n()
const { pending, settle } = useConfirm()
const dialog = useTemplateRef<HTMLDialogElement>('dialog')

watch(pending, async (value) => {
  await nextTick()
  if (value && !dialog.value?.open) dialog.value?.showModal()
  if (!value && dialog.value?.open) dialog.value.close()
})
</script>

<template>
  <dialog
    ref="dialog"
    aria-labelledby="confirm-title"
    class="m-auto w-[min(28rem,calc(100%-2rem))] border-2 border-ink bg-paper p-0 text-ink shadow-[8px_8px_0_var(--color-ink)] backdrop:bg-ink/50"
    @cancel.prevent="settle(false)"
  >
    <div v-if="pending" class="p-6">
      <h2 id="confirm-title" class="text-2xl font-extrabold uppercase font-condensed">
        {{ pending.title }}
      </h2>
      <p v-if="pending.body" class="mt-2 text-ink-soft">{{ pending.body }}</p>
      <div class="mt-6 flex justify-end gap-3">
        <AppButton variant="ghost" @click="settle(false)">{{ t('common.cancel') }}</AppButton>
        <AppButton
          :variant="pending.tone === 'danger' ? 'danger' : 'primary'"
          @click="settle(true)"
        >
          {{ pending.confirmLabel }}
        </AppButton>
      </div>
    </div>
  </dialog>
</template>
