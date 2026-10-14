<script setup lang="ts">
import { useI18n } from 'vue-i18n'

import { useToast } from '@/composables/useToast'

const { t } = useI18n()
const { toasts, dismiss } = useToast()
</script>

<template>
  <div
    aria-live="polite"
    class="pointer-events-none fixed inset-x-4 bottom-4 z-50 flex flex-col items-center gap-2 sm:inset-x-auto sm:right-6 sm:items-end"
  >
    <TransitionGroup
      enter-from-class="translate-y-2 opacity-0"
      leave-to-class="opacity-0"
      enter-active-class="transition duration-200"
      leave-active-class="transition duration-200"
    >
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :role="toast.tone === 'error' ? 'alert' : 'status'"
        class="pointer-events-auto flex items-center gap-4 border-2 border-ink px-4 py-3 text-sm shadow-[4px_4px_0_var(--color-ink)]"
        :class="toast.tone === 'error' ? 'bg-pole text-paper' : 'bg-paper text-ink'"
      >
        <span>{{ toast.message }}</span>
        <button
          type="button"
          class="font-mono text-xs uppercase underline underline-offset-4"
          @click="dismiss(toast.id)"
        >
          {{ t('common.close') }}
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>
