<script setup lang="ts">
const { tone = 'error' } = defineProps<{ tone?: 'error' | 'success' }>()
</script>

<template>
  <div
    :role="tone === 'error' ? 'alert' : 'status'"
    class="border-l-4 px-4 py-3 text-sm"
    :class="tone === 'error' ? 'border-pole bg-pole/10 text-pole-deep' : 'border-ink bg-paper-deep'"
  >
    <slot />
  </div>
</template>
