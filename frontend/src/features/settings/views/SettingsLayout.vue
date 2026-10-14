<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

import PageHeader from '@/components/ui/PageHeader.vue'
import TabNav from '@/components/ui/TabNav.vue'

const { t } = useI18n()

const tabs = computed(() => [
  { to: { name: 'settings' }, label: t('settings.tabs.profile') },
  { to: { name: 'settings-booking' }, label: t('settings.tabs.booking') },
  { to: { name: 'settings-hours' }, label: t('settings.tabs.hours') },
  { to: { name: 'settings-images' }, label: t('settings.tabs.images') },
])
</script>

<template>
  <div class="mx-auto max-w-4xl px-4 py-8 sm:px-8 md:py-12">
    <PageHeader :kicker="t('settings.kicker')" :title="t('settings.title')" />
    <TabNav :label="t('settings.tabsLabel')" :tabs="tabs" class="mt-6" />
    <div class="mt-8">
      <RouterView />
    </div>
  </div>
</template>
