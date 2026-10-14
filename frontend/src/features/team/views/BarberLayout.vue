<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

import TabNav from '@/components/ui/TabNav.vue'

import BarberAvatar from '../components/BarberAvatar.vue'
import { useBarberContext } from '../composables/useBarberContext'

const { t } = useI18n()
const { barberId, barber } = useBarberContext()

const tabs = computed(() => {
  const params = { barberId: barberId.value }
  return [
    { to: { name: 'barber', params }, label: t('team.tabs.profile') },
    { to: { name: 'barber-services', params }, label: t('team.tabs.services') },
    { to: { name: 'barber-schedule', params }, label: t('team.tabs.schedule') },
    { to: { name: 'barber-time-off', params }, label: t('team.tabs.timeOff') },
    { to: { name: 'barber-portfolio', params }, label: t('team.tabs.portfolio') },
  ]
})
</script>

<template>
  <div class="mx-auto max-w-5xl px-4 py-8 sm:px-8 md:py-12">
    <RouterLink
      :to="{ name: 'team' }"
      class="font-mono text-xs tracking-widest uppercase underline decoration-pole underline-offset-4"
    >
      ← {{ t('team.back') }}
    </RouterLink>

    <p v-if="barber.isPending.value" class="mt-8 font-mono text-sm text-ink-soft">
      {{ t('common.loading') }}
    </p>
    <p v-else-if="barber.isError.value" class="mt-8" role="alert">{{ t('team.notFound') }}</p>

    <template v-else-if="barber.data.value">
      <header class="mt-6 flex items-center gap-5 border-b-2 border-ink pb-5">
        <BarberAvatar :barber="barber.data.value" class="size-20" />
        <div class="min-w-0">
          <p class="font-mono text-xs tracking-[0.2em] text-pole uppercase">
            {{ t('team.kicker') }}
          </p>
          <h1 class="mt-1 text-4xl leading-none font-black uppercase font-condensed sm:text-5xl">
            {{ barber.data.value.display_name }}
          </h1>
        </div>
      </header>
      <TabNav :label="t('team.tabsLabel')" :tabs="tabs" class="mt-6" />
      <div class="mt-8">
        <RouterView />
      </div>
    </template>
  </div>
</template>
