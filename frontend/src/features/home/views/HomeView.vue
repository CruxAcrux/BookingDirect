<script setup lang="ts">
import { useI18n } from 'vue-i18n'

import BookingTicket from '../components/BookingTicket.vue'

const { t } = useI18n()

const features = ['page', 'queue', 'rebook'] as const
</script>

<template>
  <div class="mx-auto max-w-6xl px-4 sm:px-8">
    <section
      class="grid items-center gap-12 border-b-2 border-ink py-12 md:grid-cols-[1fr_auto] md:gap-16 md:py-20"
      aria-labelledby="home-title"
    >
      <div>
        <p class="font-mono text-xs tracking-[0.2em] text-pole uppercase">{{ t('home.kicker') }}</p>
        <h1
          id="home-title"
          class="mt-4 text-5xl leading-[0.92] font-black tracking-tight text-balance uppercase font-condensed sm:text-7xl lg:text-8xl"
        >
          {{ t('home.title') }}
        </h1>
        <p class="mt-6 max-w-xl text-lg text-pretty text-ink-soft">{{ t('home.lead') }}</p>
      </div>
      <div class="flex justify-center md:justify-end">
        <BookingTicket />
      </div>
    </section>

    <section class="py-12 md:py-16" aria-labelledby="features-title">
      <h2 id="features-title" class="font-mono text-xs tracking-[0.2em] uppercase">
        {{ t('home.featuresTitle') }}
      </h2>
      <ol class="mt-6 grid md:grid-cols-3">
        <li
          v-for="(key, index) in features"
          :key="key"
          class="border-t border-line py-6 md:border-t-0 md:border-l md:px-6 md:first:border-l-0 md:first:pl-0"
        >
          <span class="font-mono text-sm text-pole tabular" aria-hidden="true">
            {{ String(index + 1).padStart(2, '0') }}
          </span>
          <h3 class="mt-2 text-2xl font-extrabold uppercase font-condensed">
            {{ t(`home.features.${key}.title`) }}
          </h3>
          <p class="mt-2 text-ink-soft">{{ t(`home.features.${key}.body`) }}</p>
        </li>
      </ol>
    </section>
  </div>
</template>
