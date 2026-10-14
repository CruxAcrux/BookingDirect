<script setup lang="ts">
import SiteFooter from './SiteFooter.vue'
import SiteHeader from './SiteHeader.vue'
</script>

<template>
  <div class="flex min-h-dvh flex-col">
    <SiteHeader />
    <main id="main" tabindex="-1" class="flex-1 focus:outline-none">
      <RouterView />
    </main>
    <SiteFooter />
  </div>
</template>
