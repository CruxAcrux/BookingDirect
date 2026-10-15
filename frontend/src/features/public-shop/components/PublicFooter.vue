<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const { slug } = defineProps<{ slug?: string }>()

const { t } = useI18n()
</script>

<template>
  <footer class="mt-16 border-t border-line">
    <div
      class="mx-auto flex max-w-5xl flex-wrap justify-between gap-3 px-4 py-6 font-mono text-xs text-ink-soft sm:px-8"
    >
      <RouterLink
        v-if="slug"
        :to="{ name: 'my-bookings', params: { slug } }"
        class="underline underline-offset-4 sm:hidden"
      >
        {{ t('shop.myBookings') }}
      </RouterLink>
      <span>
        {{ t('shop.poweredBy') }}
        <RouterLink to="/" class="text-sm font-extrabold text-ink uppercase font-condensed">
          Booking<span class="text-pole">Direct</span>
        </RouterLink>
      </span>
    </div>
  </footer>
</template>
