<script setup lang="ts">
import { useId } from 'vue'

const { label, description } = defineProps<{ label: string; description?: string }>()

const model = defineModel<boolean>({ default: false })

const id = useId()
</script>

<template>
  <div class="flex items-start justify-between gap-6">
    <div>
      <label :for="id" class="font-medium">{{ label }}</label>
      <p v-if="description" :id="`${id}-description`" class="mt-0.5 text-sm text-ink-soft">
        {{ description }}
      </p>
    </div>
    <button
      :id="id"
      type="button"
      role="switch"
      :aria-checked="model"
      :aria-describedby="description ? `${id}-description` : undefined"
      class="relative h-7 w-12 shrink-0 border-2 border-ink transition-colors"
      :class="model ? 'bg-ink' : 'bg-paper'"
      @click="model = !model"
    >
      <span
        class="absolute top-0.5 size-5 transition-all"
        :class="model ? 'left-[calc(100%-1.375rem)] bg-paper' : 'left-0.5 bg-ink'"
        aria-hidden="true"
      />
    </button>
  </div>
</template>
