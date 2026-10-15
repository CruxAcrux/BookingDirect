<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'

import AppButton from '@/components/ui/AppButton.vue'
import { useToast } from '@/composables/useToast'
import { addDays, todayIn, weekdayOf } from '@/lib/dates'
import { ApiError } from '@/lib/apiError'

import BookingPanel from '../components/BookingPanel.vue'
import CalendarGrid from '../components/CalendarGrid.vue'
import NewBookingDialog from '../components/NewBookingDialog.vue'
import { useCalendarSchedule, useMoveBooking, useStaffBookings, type StaffBooking } from '../api'
import { buildColumn, type Column } from '../lib/columns'
import { clockOf, localToUtc, minutesOf, visibleWindow } from '../lib/grid'

type View = 'day' | 'week'

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const toast = useToast()

const FALLBACK_TIMEZONE = 'Europe/Stockholm'
const timezone = ref(FALLBACK_TIMEZONE)
const today = computed(() => todayIn(timezone.value))

// View state lives in the URL, so a reload or a shared link shows the same calendar.
const view = computed<View>(() => (route.query.view === 'week' ? 'week' : 'day'))
const date = computed(() =>
  typeof route.query.date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(route.query.date)
    ? route.query.date
    : today.value,
)
const showCancelled = computed(() => route.query.cancelled === '1')
const selectedBarberId = computed(() =>
  typeof route.query.barber === 'string' ? Number(route.query.barber) : null,
)

function setQuery(changes: Record<string, string | null>) {
  const merged = { ...route.query, ...changes }
  const query = Object.fromEntries(Object.entries(merged).filter(([, value]) => value !== null))
  void router.replace({ query })
}

const weekStart = computed(() => addDays(date.value, 1 - weekdayOf(date.value)))
const range = computed(() =>
  view.value === 'day'
    ? { from: date.value, to: date.value }
    : { from: weekStart.value, to: addDays(weekStart.value, 6) },
)

const schedule = useCalendarSchedule(range)
const bookings = useStaffBookings(range, showCancelled)
const move = useMoveBooking()

const barbers = computed(() => schedule.data.value?.barbers ?? [])
watch(
  () => schedule.data.value?.timezone,
  (zone) => {
    if (zone) timezone.value = zone
  },
)
const weekBarber = computed(
  () =>
    barbers.value.find((barber) => barber.id === selectedBarberId.value) ??
    barbers.value[0] ??
    null,
)

const columns = computed<Column[]>(() => {
  const opening = schedule.data.value?.opening_hours ?? []
  const list = bookings.data.value ?? []

  if (view.value === 'day')
    return barbers.value.map((barber) => buildColumn(date.value, barber, opening, list))

  const barber = weekBarber.value
  return barber
    ? Array.from({ length: 7 }, (_, i) =>
        buildColumn(addDays(weekStart.value, i), barber, opening, list),
      )
    : []
})

const window = computed(() =>
  visibleWindow(
    columns.value.flatMap((column) => [
      ...column.open,
      ...column.bookings.map((booking) => ({
        start: minutesOf(booking.starts_at_local),
        end: minutesOf(booking.starts_at_local) + booking.duration_minutes + booking.buffer_minutes,
      })),
    ]),
  ),
)

const dayFormat = (value: string, options: Intl.DateTimeFormatOptions) =>
  new Intl.DateTimeFormat(locale.value, { ...options, timeZone: 'UTC' }).format(
    new Date(`${value}T00:00:00Z`),
  )

const title = computed(() =>
  view.value === 'day'
    ? dayFormat(date.value, { weekday: 'long', day: 'numeric', month: 'long' })
    : `${dayFormat(weekStart.value, { day: 'numeric', month: 'short' })} – ${dayFormat(addDays(weekStart.value, 6), { day: 'numeric', month: 'short', year: 'numeric' })}`,
)

function headerFor(column: Column) {
  return view.value === 'day'
    ? { title: column.barber.display_name }
    : {
        title: dayFormat(column.date, { weekday: 'short' }),
        subtitle: dayFormat(column.date, { day: 'numeric', month: 'short' }),
      }
}

// The server only returns the columns a user may work in (barbers get their own), so every
// column shown is editable; the API still checks each change.
const canEdit = () => true

function step(direction: -1 | 1) {
  setQuery({ date: addDays(date.value, direction * (view.value === 'day' ? 1 : 7)) })
}

// New booking
const creating = ref(false)
const createAt = ref({ barberId: 0, date: today.value, minutes: 600 })
function openCreate(column?: Column, minutes?: number) {
  const barber = column?.barber ?? (view.value === 'week' ? weekBarber.value : barbers.value[0])
  if (!barber) return
  createAt.value = {
    barberId: barber.id,
    date: column?.date ?? date.value,
    minutes: minutes ?? window.value.start,
  }
  creating.value = true
}

// Booking panel
const panelOpen = ref(false)
const openId = ref<number | null>(null)
const openBooking = computed(
  () => bookings.data.value?.find((booking) => booking.id === openId.value) ?? null,
)
function showBooking(booking: StaffBooking) {
  openId.value = booking.id
  panelOpen.value = true
}

async function moveBooking(booking: StaffBooking, column: Column, minutes: number) {
  try {
    await move.mutateAsync({
      booking,
      startsAt: localToUtc(column.date, minutes, timezone.value),
      barberId: column.barber.id,
      preview: {
        startsAtLocal: `${column.date}T${clockOf(minutes)}`,
        endsAtLocal: `${column.date}T${clockOf(minutes + booking.duration_minutes)}`,
      },
    })
    toast.show(t('calendar.moved'))
  } catch (error) {
    toast.show(
      error instanceof ApiError && [409, 422].includes(error.status)
        ? error.message
        : t('errors.generic'),
      'error',
    )
  }
}
</script>

<template>
  <div class="px-4 py-6 sm:px-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <p class="font-mono text-xs tracking-[0.2em] text-pole uppercase">
          {{ t('calendar.kicker') }}
        </p>
        <h1
          class="mt-1 text-4xl leading-none font-black uppercase font-condensed first-letter:uppercase"
        >
          {{ title }}
        </h1>
      </div>
      <AppButton @click="openCreate()">+ {{ t('calendar.newBooking') }}</AppButton>
    </div>

    <div class="mt-5 flex flex-wrap items-center gap-3">
      <div class="flex border-2 border-ink" role="group" :aria-label="t('calendar.navigation')">
        <button
          type="button"
          class="px-3 py-1.5 hover:bg-paper-deep"
          :aria-label="t('calendar.previous')"
          @click="step(-1)"
        >
          ←
        </button>
        <button
          type="button"
          class="border-x-2 border-ink px-3 py-1.5 font-mono text-xs tracking-wider uppercase hover:bg-paper-deep"
          @click="setQuery({ date: null })"
        >
          {{ t('calendar.today') }}
        </button>
        <button
          type="button"
          class="px-3 py-1.5 hover:bg-paper-deep"
          :aria-label="t('calendar.next')"
          @click="step(1)"
        >
          →
        </button>
      </div>
      <input
        type="date"
        :value="date"
        :aria-label="t('calendar.pickDate')"
        class="border-2 border-ink bg-paper px-2 py-1 font-mono text-sm"
        @change="setQuery({ date: ($event.target as HTMLInputElement).value || null })"
      />
      <div
        class="flex border-2 border-ink font-mono text-xs uppercase"
        role="group"
        :aria-label="t('calendar.view')"
      >
        <button
          v-for="option in ['day', 'week'] as const"
          :key="option"
          type="button"
          :aria-pressed="view === option"
          class="px-3 py-1.5 tracking-wider aria-pressed:bg-ink aria-pressed:text-paper"
          @click="setQuery({ view: option === 'day' ? null : option })"
        >
          {{ t(`calendar.views.${option}`) }}
        </button>
      </div>
      <select
        v-if="view === 'week' && barbers.length > 1"
        :value="weekBarber?.id"
        :aria-label="t('calendar.fields.barber')"
        class="border-2 border-ink bg-paper px-2 py-1.5 text-sm"
        @change="setQuery({ barber: ($event.target as HTMLSelectElement).value })"
      >
        <option v-for="barber in barbers" :key="barber.id" :value="barber.id">
          {{ barber.display_name }}
        </option>
      </select>
      <label class="flex items-center gap-2 text-sm">
        <input
          type="checkbox"
          class="size-4 accent-ink"
          :checked="showCancelled"
          @change="
            setQuery({ cancelled: ($event.target as HTMLInputElement).checked ? '1' : null })
          "
        />
        {{ t('calendar.showCancelled') }}
      </label>
    </div>

    <p v-if="schedule.isPending.value" class="mt-8 font-mono text-sm text-ink-soft">
      {{ t('common.loading') }}
    </p>
    <p v-else-if="columns.length === 0" class="mt-8 text-ink-soft">{{ t('calendar.noBarbers') }}</p>
    <CalendarGrid
      v-else
      class="mt-5"
      :columns="columns"
      :window="window"
      :timezone="timezone"
      :header-for="headerFor"
      :can-edit="canEdit"
      @open="showBooking"
      @create="openCreate"
      @move="moveBooking"
    />

    <NewBookingDialog
      v-model:open="creating"
      :barbers="barbers"
      :timezone="timezone"
      :initial="createAt"
    />
    <BookingPanel
      v-if="openBooking"
      v-model:open="panelOpen"
      :booking="openBooking"
      :barbers="barbers"
      :timezone="timezone"
      :can-edit="true"
    />
  </div>
</template>
