<script setup lang="ts" generic="T extends string | number">
import { useId } from 'vue'

const { label, options, error } = defineProps<{
  label: string
  options: readonly { value: T; label: string }[]
  error?: string | undefined
}>()

const model = defineModel<T>({ required: true })

defineOptions({ inheritAttrs: false })

const id = useId()
</script>

<template>
  <div>
    <label :for="id" class="block font-mono text-xs tracking-widest uppercase">{{ label }}</label>
    <select
      :id="id"
      v-model="model"
      :aria-invalid="error ? true : undefined"
      :aria-describedby="error ? `${id}-error` : undefined"
      v-bind="$attrs"
      class="mt-2 block w-full appearance-none border-2 border-ink bg-paper bg-[length:12px] bg-[right_0.75rem_center] bg-no-repeat px-3 py-2.5 pr-9 text-base text-ink focus:bg-white focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-pole"
      style="
        background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' fill='none' stroke='%2317140f' stroke-width='2'/%3E%3C/svg%3E&quot;);
      "
    >
      <option v-for="option in options" :key="option.value" :value="option.value">
        {{ option.label }}
      </option>
    </select>
    <p v-if="error" :id="`${id}-error`" class="mt-1.5 text-sm font-medium text-pole">
      {{ error }}
    </p>
  </div>
</template>
