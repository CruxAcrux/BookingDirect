<script setup lang="ts">
import { useI18n } from 'vue-i18n'

import LocaleSwitcher from '@/components/ui/LocaleSwitcher.vue'

const { name, slug, logoUrl } = defineProps<{
  name?: string
  slug?: string
  logoUrl?: string | null
}>()

const { t } = useI18n()
</script>

<template>
  <header class="bg-ink text-paper">
    <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-3 sm:px-8">
      <RouterLink
        v-if="slug"
        :to="{ name: 'shop', params: { slug } }"
        class="flex min-w-0 items-center gap-3"
      >
        <img v-if="logoUrl" :src="logoUrl" alt="" class="size-9 shrink-0 bg-paper object-contain" />
        <span class="truncate text-2xl font-extrabold tracking-tight uppercase font-condensed">{{
          name
        }}</span>
      </RouterLink>
      <span v-else class="h-9" />
      <div class="flex shrink-0 items-center gap-3">
        <RouterLink
          v-if="slug"
          :to="{ name: 'my-bookings', params: { slug } }"
          class="hidden font-mono text-xs tracking-widest uppercase underline decoration-pole decoration-2 underline-offset-4 sm:inline"
        >
          {{ t('shop.myBookings') }}
        </RouterLink>
        <LocaleSwitcher
          class="border-paper/40 [&_[aria-pressed=true]]:bg-paper [&_[aria-pressed=true]]:text-ink"
        />
      </div>
    </div>
    <div class="h-1.5 pole-stripe" aria-hidden="true" />
  </header>
</template>
