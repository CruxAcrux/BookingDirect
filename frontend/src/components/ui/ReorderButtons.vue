<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const { name, first, last, disabled } = defineProps<{
  name: string
  first: boolean
  last: boolean
  disabled?: boolean
}>()

const emit = defineEmits<{ move: [direction: -1 | 1] }>()

const { t } = useI18n()
</script>

<template>
  <div class="flex flex-col">
    <button
      type="button"
      class="grid size-7 place-items-center border border-ink/30 hover:bg-paper-deep disabled:opacity-30"
      :disabled="first || disabled"
      :aria-label="t('common.moveUp', { name })"
      @click="emit('move', -1)"
    >
      <svg viewBox="0 0 12 8" class="w-3" aria-hidden="true">
        <path d="M1 7l5-5 5 5" fill="none" stroke="currentColor" stroke-width="2" />
      </svg>
    </button>
    <button
      type="button"
      class="-mt-px grid size-7 place-items-center border border-ink/30 hover:bg-paper-deep disabled:opacity-30"
      :disabled="last || disabled"
      :aria-label="t('common.moveDown', { name })"
      @click="emit('move', 1)"
    >
      <svg viewBox="0 0 12 8" class="w-3" aria-hidden="true">
        <path d="M1 1l5 5 5-5" fill="none" stroke="currentColor" stroke-width="2" />
      </svg>
    </button>
  </div>
</template>
