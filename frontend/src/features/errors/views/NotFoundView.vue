<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>

<template>
  <section class="mx-auto max-w-6xl px-4 py-20 sm:px-8" aria-labelledby="not-found-title">
    <p class="text-8xl leading-none font-black text-pole tabular font-condensed sm:text-9xl">
      {{ t('notFound.code') }}
    </p>
    <h1 id="not-found-title" class="mt-4 text-4xl font-extrabold uppercase font-condensed">
      {{ t('notFound.title') }}
    </h1>
    <p class="mt-3 text-ink-soft">{{ t('notFound.body') }}</p>
    <RouterLink
      to="/"
      class="mt-8 inline-block border-2 border-ink bg-ink px-5 py-3 font-mono text-sm tracking-wider text-paper uppercase transition-colors hover:bg-paper hover:text-ink"
    >
      {{ t('notFound.back') }}
    </RouterLink>
  </section>
</template>
