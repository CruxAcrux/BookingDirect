<script setup lang="ts">
const {
  variant = 'primary',
  type = 'button',
  loading,
} = defineProps<{
  variant?: 'primary' | 'ghost'
  type?: 'button' | 'submit'
  loading?: boolean
}>()
</script>

<template>
  <button
    :type="type"
    :disabled="loading"
    :aria-busy="loading"
    class="inline-flex items-center justify-center gap-2 border-2 border-ink px-5 py-3 font-mono text-sm tracking-wider uppercase transition-colors disabled:cursor-progress disabled:opacity-70"
    :class="
      variant === 'primary'
        ? 'bg-ink text-paper hover:border-pole hover:bg-pole'
        : 'bg-transparent text-ink hover:bg-paper-deep'
    "
  >
    <span
      v-if="loading"
      class="size-3 animate-spin border-2 border-current border-t-transparent"
      aria-hidden="true"
    />
    <slot />
  </button>
</template>
