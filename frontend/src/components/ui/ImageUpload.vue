<script setup lang="ts">
import { ref, useId, useTemplateRef } from 'vue'
import { useI18n } from 'vue-i18n'

const {
  label,
  imageUrl,
  shape = 'square',
  busy,
  error: serverError,
  readonly: isReadonly,
} = defineProps<{
  label: string
  imageUrl: string | null | undefined
  shape?: 'square' | 'wide' | 'round'
  busy?: boolean
  error?: string | null
  readonly?: boolean
}>()
const emit = defineEmits<{ upload: [file: File]; remove: [] }>()
const MAX_BYTES = 10 * 1024 * 1024
const ACCEPT = ['image/jpeg', 'image/png', 'image/webp']

const { t } = useI18n()
const id = useId()
const input = useTemplateRef<HTMLInputElement>('input')
const clientError = ref<string | null>(null)

function onChange(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (input.value) input.value.value = ''
  if (!file) return

  if (!ACCEPT.includes(file.type)) {
    clientError.value = t('upload.wrongType')
  } else if (file.size > MAX_BYTES) {
    clientError.value = t('upload.tooLarge', { max: 10 })
  } else {
    clientError.value = null
    emit('upload', file)
  }
}

const frames = {
  square: 'aspect-square w-32',
  round: 'aspect-square w-32 rounded-full',
  wide: 'aspect-[3/1] w-full max-w-xl',
}
</script>

<template>
  <div>
    <p :id="`${id}-label`" class="font-mono text-xs tracking-widest uppercase">{{ label }}</p>
    <div class="mt-2 flex flex-wrap items-end gap-4">
      <div
        class="relative grid place-items-center overflow-hidden border-2 border-ink bg-paper-deep"
        :class="frames[shape]"
      >
        <img v-if="imageUrl" :src="imageUrl" alt="" class="size-full object-cover" />
        <span v-else class="size-full pole-stripe opacity-20" aria-hidden="true" />
        <span
          v-if="busy"
          class="absolute inset-0 grid place-items-center bg-paper/70 font-mono text-xs uppercase"
        >
          {{ t('upload.working') }}
        </span>
      </div>
      <div v-if="!isReadonly" class="flex flex-wrap gap-2">
        <label
          :for="id"
          class="inline-flex cursor-pointer items-center border-2 border-ink px-3 py-1.5 font-mono text-xs tracking-wider uppercase focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-pole hover:bg-paper-deep"
        >
          {{ imageUrl ? t('upload.replace') : t('upload.choose') }}
          <input
            :id="id"
            ref="input"
            type="file"
            :accept="ACCEPT.join(',')"
            class="sr-only"
            :disabled="busy"
            :aria-describedby="`${id}-label`"
            @change="onChange"
          />
        </label>
        <button
          v-if="imageUrl"
          type="button"
          class="px-2 font-mono text-xs uppercase underline decoration-pole underline-offset-4 hover:text-pole"
          :disabled="busy"
          @click="emit('remove')"
        >
          {{ t('common.remove') }}
        </button>
      </div>
    </div>
    <p v-if="clientError || serverError" class="mt-1.5 text-sm font-medium text-pole" role="alert">
      {{ clientError ?? serverError }}
    </p>
  </div>
</template>
