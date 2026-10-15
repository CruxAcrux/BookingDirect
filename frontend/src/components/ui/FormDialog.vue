<script setup lang="ts">
import { nextTick, useId, useTemplateRef, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const { title } = defineProps<{ title: string }>()

const open = defineModel<boolean>('open', { required: true })

const { t } = useI18n()
const id = useId()
const dialog = useTemplateRef<HTMLDialogElement>('dialog')

watch(
  open,
  async (value) => {
    await nextTick()
    if (value && !dialog.value?.open) dialog.value?.showModal()
    if (!value && dialog.value?.open) dialog.value.close()
  },
  // The dialog may be created already open, e.g. when its content appears with v-if.
  { immediate: true },
)
</script>

<template>
  <dialog
    ref="dialog"
    :aria-labelledby="id"
    class="m-auto max-h-[calc(100dvh-2rem)] w-[min(36rem,calc(100%-2rem))] border-2 border-ink bg-paper p-0 text-ink shadow-[8px_8px_0_var(--color-ink)] backdrop:bg-ink/50"
    @close="open = false"
  >
    <div v-if="open">
      <div class="flex items-center justify-between gap-4 border-b-2 border-ink px-6 py-4">
        <h2 :id="id" class="text-2xl font-extrabold uppercase font-condensed">{{ title }}</h2>
        <button
          type="button"
          class="font-mono text-xs uppercase underline underline-offset-4"
          @click="open = false"
        >
          {{ t('common.close') }}
        </button>
      </div>
      <div class="px-6 py-5">
        <slot />
      </div>
    </div>
  </dialog>
</template>
