<script setup lang="ts">
import { useI18n } from 'vue-i18n'

import { useFormat } from '@/composables/useFormat'
import type { PublicShop } from '@/features/booking/api'

import BarberCard from '../components/BarberCard.vue'
import OpeningHours from '../components/OpeningHours.vue'

const { shop } = defineProps<{ shop: PublicShop }>()

const { t } = useI18n()
const format = useFormat()

const mapsUrl = (address: string) =>
  `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`
</script>

<template>
  <div>
    <section class="mx-auto max-w-5xl px-4 pt-6 sm:px-8 md:pt-10" aria-labelledby="shop-title">
      <div
        v-if="shop.cover_url"
        class="aspect-[5/2] overflow-hidden border-2 border-ink bg-paper-deep md:aspect-[3/1]"
      >
        <img :src="shop.cover_url" alt="" class="size-full object-cover" />
      </div>
      <div class="mt-6 grid gap-8 md:grid-cols-[1fr_20rem]">
        <div>
          <p class="font-mono text-xs tracking-[0.2em] text-pole uppercase">
            {{ t('shop.kicker') }}
          </p>
          <h1
            id="shop-title"
            class="mt-2 text-5xl leading-[0.92] font-black text-balance uppercase font-condensed sm:text-7xl"
          >
            {{ shop.name }}
          </h1>
          <address class="mt-4 space-y-1 not-italic">
            <a
              v-if="shop.address"
              :href="mapsUrl(shop.address)"
              target="_blank"
              rel="noopener"
              class="block underline decoration-line underline-offset-4 hover:decoration-pole"
            >
              {{ shop.address }}
            </a>
            <a
              v-if="shop.phone"
              :href="`tel:${shop.phone}`"
              class="block font-mono text-sm tabular"
              >{{ shop.phone }}</a
            >
          </address>
          <RouterLink
            :to="{ name: 'book', params: { slug: shop.slug } }"
            class="mt-8 hidden border-2 border-ink bg-ink px-8 py-4 font-mono text-sm tracking-wider text-paper uppercase hover:border-pole hover:bg-pole md:inline-block"
          >
            {{ t('shop.book') }}
          </RouterLink>
        </div>
        <OpeningHours :hours="shop.opening_hours" :timezone="shop.timezone" />
      </div>
    </section>

    <section class="mx-auto mt-14 max-w-5xl px-4 sm:px-8" aria-labelledby="menu-title">
      <h2
        id="menu-title"
        class="border-b-2 border-ink pb-2 text-3xl font-black uppercase font-condensed"
      >
        {{ t('shop.services') }}
      </h2>
      <ul class="mt-2">
        <li v-for="service in shop.services" :key="service.id">
          <RouterLink
            :to="{ name: 'book', params: { slug: shop.slug }, query: { service: service.id } }"
            class="group flex items-baseline gap-3 py-3"
          >
            <span class="min-w-0">
              <span class="block font-semibold group-hover:text-pole">{{ service.name }}</span>
              <span v-if="service.description" class="block text-sm text-ink-soft">{{
                service.description
              }}</span>
            </span>
            <span
              class="mb-1 flex-1 self-end border-b-2 border-dotted border-ink/30"
              aria-hidden="true"
            />
            <span class="shrink-0 font-mono text-sm text-ink-soft tabular">{{
              format.duration(service.duration_minutes)
            }}</span>
            <span class="shrink-0 font-mono text-lg tabular">{{
              format.price(service.price_ore)
            }}</span>
          </RouterLink>
        </li>
      </ul>
    </section>

    <section class="mx-auto mt-14 max-w-5xl px-4 sm:px-8" aria-labelledby="barbers-title">
      <h2
        id="barbers-title"
        class="border-b-2 border-ink pb-2 text-3xl font-black uppercase font-condensed"
      >
        {{ t('shop.barbers') }}
      </h2>
      <div class="mt-6 grid gap-6 md:grid-cols-2">
        <BarberCard
          v-for="barber in shop.barbers"
          :key="barber.id"
          :barber="barber"
          :slug="shop.slug"
        />
      </div>
    </section>

    <div class="fixed inset-x-0 bottom-0 z-30 border-t-2 border-ink bg-paper p-3 md:hidden">
      <RouterLink
        :to="{ name: 'book', params: { slug: shop.slug } }"
        class="block bg-ink py-3.5 text-center font-mono text-sm tracking-wider text-paper uppercase"
      >
        {{ t('shop.book') }}
      </RouterLink>
    </div>
    <div class="h-20 md:hidden" aria-hidden="true" />
  </div>
</template>
