<script setup lang="ts">
const {
  variant = 'primary',
  size = 'md',
  type = 'button',
  loading,
} = defineProps<{
  variant?: 'primary' | 'ghost' | 'danger'
  size?: 'sm' | 'md'
  type?: 'button' | 'submit'
  loading?: boolean
}>()

const variants = {
  primary: 'border-ink bg-ink text-paper hover:border-pole hover:bg-pole',
  ghost: 'border-ink bg-transparent text-ink hover:bg-paper-deep',
  danger: 'border-pole bg-pole text-paper hover:border-pole-deep hover:bg-pole-deep',
}
</script>

<template>
  <button
    :type="type"
    :disabled="loading"
    :aria-busy="loading"
    class="inline-flex items-center justify-center gap-2 border-2 font-mono tracking-wider uppercase transition-colors disabled:cursor-progress disabled:opacity-70"
    :class="[variants[variant], size === 'sm' ? 'px-3 py-1.5 text-xs' : 'px-5 py-3 text-sm']"
  >
    <span
      v-if="loading"
      class="size-3 animate-spin border-2 border-current border-t-transparent"
      aria-hidden="true"
    />
    <slot />
  </button>
</template>
