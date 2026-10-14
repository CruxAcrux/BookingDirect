<script setup lang="ts">
import { computed, useId } from 'vue'

const {
  label,
  type = 'text',
  error,
  hint,
} = defineProps<{
  label: string
  type?: 'text' | 'email' | 'password' | 'tel'
  error?: string | undefined
  hint?: string
}>()

const model = defineModel<string>({ default: '' })

defineOptions({ inheritAttrs: false })

const id = useId()
const describedBy = computed(
  () =>
    [error ? `${id}-error` : null, hint ? `${id}-hint` : null].filter(Boolean).join(' ') ||
    undefined,
)
</script>

<template>
  <div>
    <label :for="id" class="block font-mono text-xs tracking-widest uppercase">{{ label }}</label>
    <input
      :id="id"
      v-model="model"
      :type="type"
      :aria-invalid="error ? true : undefined"
      :aria-describedby="describedBy"
      v-bind="$attrs"
      class="mt-2 block w-full border-2 border-ink bg-paper px-3 py-2.5 text-base text-ink placeholder:text-ink-soft/60 focus:bg-white focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-pole aria-invalid:border-pole"
    />
    <p v-if="hint && !error" :id="`${id}-hint`" class="mt-1.5 text-sm text-ink-soft">
      {{ hint }}
    </p>
    <p v-if="error" :id="`${id}-error`" class="mt-1.5 text-sm font-medium text-pole">
      {{ error }}
    </p>
  </div>
</template>
