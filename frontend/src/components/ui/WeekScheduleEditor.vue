<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

import { WEEKDAYS, weekdayName, weeklyIssues, type WeeklyRange } from '@/lib/schedule/weekly'

const {
  label,
  reference,
  referenceWarning,
  defaultRange = { starts_at: '10:00', ends_at: '18:00' },
  readonly: isReadonly,
} = defineProps<{
  label: string
  reference?: WeeklyRange[]
  referenceWarning?: string
  defaultRange?: { starts_at: string; ends_at: string }
  readonly?: boolean
}>()

const ranges = defineModel<WeeklyRange[]>({ required: true })

const { t, locale } = useI18n()

const issues = computed(() => weeklyIssues(ranges.value, reference))

function dayRanges(weekday: number) {
  return ranges.value
    .map((range, index) => ({ range, index }))
    .filter(({ range }) => range.weekday === weekday)
}

function add(weekday: number) {
  const existing = dayRanges(weekday).map(({ range }) => range)
  const last = existing.at(-1)
  const next = last
    ? { starts_at: last.ends_at, ends_at: last.ends_at < '23:00' ? '23:00' : '23:59' }
    : defaultRange
  ranges.value = [...ranges.value, { weekday, ...next }]
}

function update(index: number, key: 'starts_at' | 'ends_at', value: string) {
  ranges.value = ranges.value.map((range, i) => (i === index ? { ...range, [key]: value } : range))
}

function remove(index: number) {
  ranges.value = ranges.value.filter((_, i) => i !== index)
}
</script>

<template>
  <fieldset>
    <legend class="sr-only">{{ label }}</legend>
    <ul class="divide-y divide-line border-y border-line">
      <li
        v-for="weekday in WEEKDAYS"
        :key="weekday"
        class="grid gap-3 py-3 sm:grid-cols-[9rem_1fr] sm:items-start"
      >
        <p class="pt-2 text-lg font-extrabold uppercase font-condensed">
          {{ weekdayName(weekday, locale) }}
        </p>
        <div class="space-y-2">
          <p v-if="dayRanges(weekday).length === 0" class="pt-2 font-mono text-sm text-ink-soft">
            {{ t('schedule.closed') }}
          </p>
          <div
            v-for="{ range, index } in dayRanges(weekday)"
            :key="index"
            class="flex flex-wrap items-center gap-2"
          >
            <input
              type="time"
              :value="range.starts_at"
              :disabled="isReadonly"
              :aria-label="t('schedule.from', { day: weekdayName(weekday, locale) })"
              class="w-32 border-2 border-ink bg-paper px-2 py-1.5 font-mono tabular focus:bg-white disabled:border-line"
              @change="update(index, 'starts_at', ($event.target as HTMLInputElement).value)"
            />
            <span aria-hidden="true">–</span>
            <input
              type="time"
              :value="range.ends_at"
              :disabled="isReadonly"
              :aria-label="t('schedule.to', { day: weekdayName(weekday, locale) })"
              class="w-32 border-2 border-ink bg-paper px-2 py-1.5 font-mono tabular focus:bg-white disabled:border-line"
              @change="update(index, 'ends_at', ($event.target as HTMLInputElement).value)"
            />
            <button
              v-if="!isReadonly"
              type="button"
              class="px-2 py-1 font-mono text-xs uppercase underline decoration-pole underline-offset-4 hover:text-pole"
              @click="remove(index)"
            >
              {{ t('common.remove') }}
            </button>
          </div>
          <button
            v-if="!isReadonly"
            type="button"
            class="font-mono text-xs tracking-wider uppercase underline decoration-2 underline-offset-4 hover:text-pole"
            @click="add(weekday)"
          >
            + {{ t('schedule.addRange') }}
          </button>
          <p v-if="issues.get(weekday)?.invalid" class="text-sm font-medium text-pole" role="alert">
            {{ t('schedule.invalidRange') }}
          </p>
          <p
            v-else-if="issues.get(weekday)?.overlaps"
            class="text-sm font-medium text-pole"
            role="alert"
          >
            {{ t('schedule.overlap') }}
          </p>
          <p
            v-else-if="issues.get(weekday)?.outsideReference && referenceWarning"
            class="text-sm font-medium text-brass-deep"
          >
            {{ referenceWarning }}
          </p>
        </div>
      </li>
    </ul>
  </fieldset>
</template>
