<script setup lang="ts">
const { title, kicker } = defineProps<{ title: string; kicker?: string }>()
</script>

<template>
  <header class="flex flex-wrap items-end justify-between gap-4 border-b-2 border-ink pb-5">
    <div class="min-w-0">
      <p v-if="kicker" class="font-mono text-xs tracking-[0.2em] text-pole uppercase">
        {{ kicker }}
      </p>
      <h1 class="mt-1 text-4xl leading-none font-black uppercase font-condensed sm:text-5xl">
        {{ title }}
      </h1>
      <slot name="description" />
    </div>
    <div v-if="$slots.actions" class="flex flex-wrap gap-3">
      <slot name="actions" />
    </div>
  </header>
</template>
