<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'

const { label, tabs } = defineProps<{
  label: string
  tabs: { to: RouteLocationRaw; label: string }[]
}>()
</script>

<template>
  <nav :aria-label="label" class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
    <ul class="flex gap-1 border-b border-line">
      <li v-for="tab in tabs" :key="tab.label">
        <RouterLink
          :to="tab.to"
          exact-active-class="border-pole! text-ink!"
          class="-mb-px block border-b-4 border-transparent px-3 py-2.5 font-mono text-xs tracking-widest whitespace-nowrap text-ink-soft uppercase hover:text-ink"
        >
          {{ tab.label }}
        </RouterLink>
      </li>
    </ul>
  </nav>
</template>
