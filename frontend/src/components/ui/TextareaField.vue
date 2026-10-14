<script setup lang="ts">
import { useId } from 'vue'

const {
  label,
  error,
  rows = 4,
} = defineProps<{
  label: string
  error?: string | undefined
  rows?: number
}>()

const model = defineModel<string>({ default: '' })

defineOptions({ inheritAttrs: false })

const id = useId()
</script>

<template>
  <div>
    <label :for="id" class="block font-mono text-xs tracking-widest uppercase">{{ label }}</label>
    <textarea
      :id="id"
      v-model="model"
      :rows="rows"
      :aria-invalid="error ? true : undefined"
      :aria-describedby="error ? `${id}-error` : undefined"
      v-bind="$attrs"
      class="mt-2 block w-full border-2 border-ink bg-paper px-3 py-2.5 text-base text-ink focus:bg-white focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-pole aria-invalid:border-pole"
    />
    <p v-if="error" :id="`${id}-error`" class="mt-1.5 text-sm font-medium text-pole">
      {{ error }}
    </p>
  </div>
</template>
