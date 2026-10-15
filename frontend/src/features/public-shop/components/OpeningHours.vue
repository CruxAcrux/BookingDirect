<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

import { clockIn } from '@/lib/dates'
import { WEEKDAYS, weekdayName, type WeeklyRange } from '@/lib/schedule/weekly'

const { hours, timezone } = defineProps<{ hours: WeeklyRange[]; timezone: string }>()

const { t, locale } = useI18n()

const now = clockIn(timezone)

const week = computed(() =>
  WEEKDAYS.map((weekday) => ({
    weekday,
    ranges: hours.filter((range) => range.weekday === weekday),
  })),
)

/** Open right now, in the shop's own timezone. */
const openNow = computed(() =>
  hours.some(
    (range) =>
      range.weekday === now.weekday && range.starts_at <= now.time && now.time < range.ends_at,
  ),
)
</script>

<template>
  <section aria-labelledby="hours-title" class="border-2 border-ink bg-paper">
    <div class="flex items-center justify-between border-b-2 border-ink px-4 py-3">
      <h2 id="hours-title" class="font-mono text-xs tracking-widest uppercase">
        {{ t('shop.hours') }}
      </h2>
      <span
        class="px-1.5 py-0.5 font-mono text-[11px] tracking-wider uppercase"
        :class="openNow ? 'bg-ink text-paper' : 'border border-ink'"
      >
        {{ openNow ? t('shop.openNow') : t('shop.closedNow') }}
      </span>
    </div>
    <dl class="divide-y divide-line px-4">
      <div
        v-for="day in week"
        :key="day.weekday"
        class="flex justify-between gap-4 py-2 text-sm"
        :class="{ 'font-semibold': day.weekday === now.weekday }"
      >
        <dt class="first-letter:uppercase">
          {{ weekdayName(day.weekday, locale) }}
          <span v-if="day.weekday === now.weekday" class="sr-only">({{ t('shop.today') }})</span>
        </dt>
        <dd class="text-right font-mono tabular">
          <template v-if="day.ranges.length">
            <span v-for="range in day.ranges" :key="range.starts_at" class="block">
              {{ range.starts_at }}–{{ range.ends_at }}
            </span>
          </template>
          <span v-else class="text-ink-soft">{{ t('schedule.closed') }}</span>
        </dd>
      </div>
    </dl>
  </section>
</template>
