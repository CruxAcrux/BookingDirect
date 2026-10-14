<script setup lang="ts">
import LocaleSwitcher from '@/components/ui/LocaleSwitcher.vue'
</script>

<template>
  <header>
    <div class="h-1.5 pole-stripe" aria-hidden="true" />
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-8">
      <RouterLink to="/" class="text-2xl font-extrabold tracking-tight uppercase font-condensed">
        Booking<span class="text-pole">Direct</span>
      </RouterLink>
      <LocaleSwitcher />
    </div>
  </header>
</template>
