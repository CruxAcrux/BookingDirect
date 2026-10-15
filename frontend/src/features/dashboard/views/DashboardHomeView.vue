<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

import AppButton from '@/components/ui/AppButton.vue'
import FormAlert from '@/components/ui/FormAlert.vue'
import { useFormat } from '@/composables/useFormat'
import { useToast } from '@/composables/useToast'
import {
  useBookingActions,
  useCalendarSchedule,
  useStaffBookings,
  type StaffBooking,
} from '@/features/calendar/api'
import { needsOutcome } from '@/features/calendar/lib/columns'
import { addDays, todayIn } from '@/lib/dates'
import { useAuthStore } from '@/stores/auth'

const OUTCOME_LOOKBACK_DAYS = 14

const { t, d } = useI18n()
const format = useFormat()
const toast = useToast()
const auth = useAuthStore()

const now = new Date()
const greetingKey = computed(() => {
  const hour = now.getHours()
  if (hour < 10) return 'dashboard.greeting.morning'
  if (hour < 17) return 'dashboard.greeting.day'
  return 'dashboard.greeting.evening'
})
const firstName = computed(() => auth.user?.name.split(' ')[0] ?? '')

const timezone = ref('Europe/Stockholm')
const today = computed(() => todayIn(timezone.value))
const hasShop = computed(() => auth.currentShop !== null)

const schedule = useCalendarSchedule(() => ({ from: today.value, to: today.value }))
watch(
  () => schedule.data.value?.timezone,
  (zone) => {
    if (zone) timezone.value = zone
  },
)
const bookings = useStaffBookings(() => ({
  from: addDays(today.value, -OUTCOME_LOOKBACK_DAYS),
  to: today.value,
}))
const { outcome } = useBookingActions()

const barberName = (id: number) =>
  schedule.data.value?.barbers.find((barber) => barber.id === id)?.display_name ?? ''

const todays = computed(() =>
  (bookings.data.value ?? []).filter((booking) => booking.starts_at_local.startsWith(today.value)),
)
const upcoming = computed(() =>
  todays.value.filter((booking) => Date.parse(booking.ends_at) >= Date.now()),
)
const toMark = computed(() =>
  (bookings.data.value ?? []).filter((booking) => needsOutcome(booking)).reverse(),
)

async function mark(booking: StaffBooking, status: 'completed' | 'no_show') {
  try {
    await outcome.mutateAsync({ id: booking.id, status })
  } catch {
    toast.show(t('errors.generic'), 'error')
  }
}
</script>

<template>
  <div class="mx-auto max-w-6xl px-4 py-10 sm:px-8 md:py-14">
    <p class="font-mono text-xs tracking-[0.2em] text-pole uppercase">{{ d(now, 'long') }}</p>
    <h1 class="mt-3 text-5xl leading-none font-black uppercase font-condensed sm:text-6xl">
      {{ t(greetingKey, { name: firstName }) }}
    </h1>

    <FormAlert v-if="!hasShop" class="mt-8 max-w-xl">
      {{ auth.user?.is_platform_admin ? t('dashboard.adminNoShop') : t('dashboard.noShop') }}
    </FormAlert>

    <div v-else class="mt-10 grid gap-10 lg:grid-cols-[1fr_22rem]">
      <section aria-labelledby="today-title">
        <div class="flex items-end justify-between gap-4 border-b-2 border-ink pb-2">
          <h2 id="today-title" class="text-3xl font-black uppercase font-condensed">
            {{ t('dashboard.today.title') }}
          </h2>
          <p class="font-mono text-sm text-ink-soft tabular">
            {{ t('dashboard.today.count', { left: upcoming.length, total: todays.length }) }}
          </p>
        </div>
        <p v-if="bookings.isPending.value" class="mt-4 font-mono text-sm text-ink-soft">
          {{ t('common.loading') }}
        </p>
        <p v-else-if="upcoming.length === 0" class="mt-4 text-ink-soft">
          {{ t('dashboard.today.empty') }}
        </p>
        <ol v-else class="divide-y divide-line">
          <li
            v-for="(booking, index) in upcoming"
            :key="booking.id"
            class="grid grid-cols-[4.5rem_1fr_auto] items-baseline gap-3 py-3"
          >
            <span
              class="text-2xl font-black tabular font-condensed"
              :class="{ 'text-pole': index === 0 }"
            >
              {{ booking.starts_at_local.slice(11, 16) }}
            </span>
            <span class="min-w-0">
              <span class="block truncate font-semibold">{{
                booking.customer.name ?? booking.customer.phone
              }}</span>
              <span class="block truncate text-sm text-ink-soft"
                >{{ booking.service.name }} · {{ barberName(booking.barber_id) }}</span
              >
            </span>
            <span class="font-mono text-sm tabular">{{ format.price(booking.price_ore) }}</span>
          </li>
        </ol>
        <RouterLink
          :to="{ name: 'calendar' }"
          class="mt-6 inline-block border-2 border-ink px-4 py-2 font-mono text-xs tracking-wider uppercase hover:bg-ink hover:text-paper"
        >
          {{ t('dashboard.openCalendar') }}
        </RouterLink>
      </section>

      <section aria-labelledby="outcome-title" class="h-fit border-2 border-ink">
        <h2
          id="outcome-title"
          class="flex items-center justify-between border-b-2 border-ink px-4 py-3 font-mono text-xs tracking-widest uppercase"
        >
          {{ t('dashboard.toMark.title') }}
          <span v-if="toMark.length" class="bg-pole px-1.5 text-paper">{{ toMark.length }}</span>
        </h2>
        <p v-if="toMark.length === 0" class="px-4 py-4 text-sm text-ink-soft">
          {{ t('dashboard.toMark.empty') }}
        </p>
        <ul v-else class="divide-y divide-line">
          <li v-for="booking in toMark" :key="booking.id" class="px-4 py-3">
            <p class="font-semibold">{{ booking.customer.name ?? booking.customer.phone }}</p>
            <p class="font-mono text-xs text-ink-soft first-letter:uppercase">
              {{
                format.localDateTime(booking.starts_at_local, {
                  weekday: 'short',
                  day: 'numeric',
                  month: 'short',
                  hour: '2-digit',
                  minute: '2-digit',
                  hourCycle: 'h23',
                })
              }}
              · {{ booking.service.name }}
            </p>
            <div class="mt-2 flex gap-2">
              <AppButton size="sm" variant="ghost" @click="mark(booking, 'completed')">{{
                t('calendar.markCompleted')
              }}</AppButton>
              <AppButton size="sm" variant="ghost" @click="mark(booking, 'no_show')">{{
                t('calendar.markNoShow')
              }}</AppButton>
            </div>
          </li>
        </ul>
      </section>
    </div>
  </div>
</template>
