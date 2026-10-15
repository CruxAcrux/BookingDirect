<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

import { addDays, todayIn } from '@/lib/dates'

import { useAvailability, type Slot } from '../api'

const {
  slug,
  timezone,
  serviceId,
  barberId,
  maxDaysAhead,
  barberNames = {},
  selected,
} = defineProps<{
  slug: string
  timezone: string
  serviceId: number
  barberId: number | null
  maxDaysAhead: number
  /** Shown next to each time when the customer chose "first available". */
  barberNames?: Record<number, string>
  selected: string | null
}>()

const emit = defineEmits<{ select: [slot: Slot] }>()
const date = defineModel<string | null>('date', { default: null })
const WINDOW_DAYS = 14

const { t, locale } = useI18n()

const today = todayIn(timezone)
const lastDay = addDays(today, maxDaysAhead)

function windowStartFor(day: string | null): string {
  if (day === null || day < today) return today
  const offset = Math.floor((Date.parse(day) - Date.parse(today)) / 86_400_000 / WINDOW_DAYS)
  return addDays(today, offset * WINDOW_DAYS)
}

const windowStart = ref(windowStartFor(date.value))
const windowEnd = computed(() => {
  const end = addDays(windowStart.value, WINDOW_DAYS - 1)
  return end < lastDay ? end : lastDay
})

const availability = useAvailability(() => ({
  slug,
  serviceId,
  barberId,
  from: windowStart.value,
  to: windowEnd.value,
}))

const days = computed(() => availability.data.value ?? [])
const selectedDay = computed(() => days.value.find((day) => day.date === date.value))

// Open on the first day with free times, unless the customer already picked a day.
watch(
  days,
  (list) => {
    if (list.length === 0 || selectedDay.value) return
    const first = list.find((day) => day.slots.length > 0)
    if (first) date.value = first.date
  },
  { immediate: true },
)

function shift(direction: -1 | 1) {
  windowStart.value = addDays(windowStart.value, direction * WINDOW_DAYS)
  date.value = null
}

const format = (day: string, options: Intl.DateTimeFormatOptions) =>
  new Intl.DateTimeFormat(locale.value, { ...options, timeZone: 'UTC' }).format(
    new Date(`${day}T00:00:00Z`),
  )

const monthLabel = computed(() => format(windowStart.value, { month: 'long', year: 'numeric' }))
const noTimesInWindow = computed(
  () => !availability.isPending.value && days.value.every((day) => day.slots.length === 0),
)
</script>

<template>
  <div>
    <div class="flex items-center justify-between gap-3">
      <p class="font-mono text-xs tracking-widest uppercase first-letter:uppercase">
        {{ monthLabel }}
      </p>
      <div class="flex gap-1">
        <button
          type="button"
          class="grid size-9 place-items-center border-2 border-ink disabled:border-line disabled:text-line"
          :disabled="windowStart <= today"
          :aria-label="t('booking.time.earlier')"
          @click="shift(-1)"
        >
          ←
        </button>
        <button
          type="button"
          class="grid size-9 place-items-center border-2 border-ink disabled:border-line disabled:text-line"
          :disabled="windowEnd >= lastDay"
          :aria-label="t('booking.time.later')"
          @click="shift(1)"
        >
          →
        </button>
      </div>
    </div>

    <div
      role="radiogroup"
      :aria-label="t('booking.time.day')"
      class="-mx-4 mt-3 flex snap-x gap-2 overflow-x-auto px-4 pb-2 sm:mx-0 sm:grid sm:grid-cols-7 sm:px-0"
      :aria-busy="availability.isFetching.value"
    >
      <button
        v-for="day in days"
        :key="day.date"
        type="button"
        role="radio"
        :aria-checked="day.date === date"
        :disabled="day.slots.length === 0"
        class="flex w-16 shrink-0 snap-start flex-col items-center border-2 py-2 transition-colors sm:w-auto"
        :class="
          day.date === date
            ? 'border-ink bg-ink text-paper'
            : 'border-ink/25 hover:border-ink disabled:border-transparent disabled:text-ink-soft/50 disabled:line-through'
        "
        @click="date = day.date"
      >
        <span class="font-mono text-[11px] tracking-wider uppercase">{{
          format(day.date, { weekday: 'short' })
        }}</span>
        <span class="text-2xl leading-none font-black tabular font-condensed">{{
          format(day.date, { day: 'numeric' })
        }}</span>
      </button>
    </div>

    <p v-if="availability.isPending.value" class="mt-6 font-mono text-sm text-ink-soft">
      {{ t('common.loading') }}
    </p>
    <p v-else-if="noTimesInWindow" class="mt-6 text-ink-soft">
      {{ t('booking.time.noneInWindow') }}
    </p>

    <div v-else-if="selectedDay" class="mt-6">
      <p class="font-mono text-xs tracking-widest uppercase first-letter:uppercase">
        {{ format(selectedDay.date, { weekday: 'long', day: 'numeric', month: 'long' }) }}
      </p>
      <div
        role="radiogroup"
        :aria-label="t('booking.time.time')"
        class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-5"
      >
        <button
          v-for="slot in selectedDay.slots"
          :key="slot.starts_at"
          type="button"
          role="radio"
          :aria-checked="slot.starts_at === selected"
          class="flex flex-col items-center border-2 px-2 py-2.5 transition-colors"
          :class="
            slot.starts_at === selected
              ? 'border-pole bg-pole text-paper'
              : 'border-ink hover:bg-paper-deep'
          "
          @click="emit('select', slot)"
        >
          <span class="font-mono text-base tabular">{{ slot.time }}</span>
          <span v-if="barberNames[slot.barber_id]" class="text-xs opacity-80">{{
            barberNames[slot.barber_id]
          }}</span>
        </button>
      </div>
    </div>
  </div>
</template>
