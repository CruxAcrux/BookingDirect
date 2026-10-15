<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

import type { StaffBooking } from '../api'
import { needsOutcome } from '../lib/columns'
import { PX_PER_MINUTE, minutesOf, topOf, type Window } from '../lib/grid'

const { booking, window, ghost } = defineProps<{
  booking: StaffBooking
  window: Window
  /** Drawn at a drag target instead of the booking's real place. */
  ghost?: { minutes: number }
}>()

const { t } = useI18n()

const start = computed(() => ghost?.minutes ?? minutesOf(booking.starts_at_local))
const style = computed(() => ({
  top: `${String(topOf(start.value, window))}px`,
  height: `${String(Math.max(booking.duration_minutes * PX_PER_MINUTE, 22))}px`,
}))
const bufferHeight = computed(() => `${String(booking.buffer_minutes * PX_PER_MINUTE)}px`)
const flagged = computed(() => !ghost && needsOutcome(booking))
/** Short visits have room for one line only. */
const compact = computed(() => booking.duration_minutes * PX_PER_MINUTE < 50)

const tone = {
  pending: 'border-2 border-dashed border-ink bg-paper text-ink',
  confirmed: 'bg-ink text-paper',
  completed: 'bg-brass/25 text-ink border-l-4 border-brass-deep',
  cancelled: 'bg-paper text-ink-soft line-through border border-line',
  no_show: 'bg-paper text-pole-deep border-2 border-pole',
}

const clock = (minutes: number) =>
  `${String(Math.floor(minutes / 60)).padStart(2, '0')}:${String(minutes % 60).padStart(2, '0')}`
</script>

<template>
  <div
    class="absolute inset-x-1"
    :style="style"
    :class="{ 'z-20 opacity-90 shadow-[4px_4px_0_var(--color-pole)]': ghost }"
  >
    <div
      class="flex h-full flex-col overflow-hidden px-2 py-1 text-left text-xs leading-tight"
      :class="tone[booking.status]"
    >
      <span v-if="compact" class="truncate">
        <span class="font-mono text-[11px] tabular opacity-80">{{ clock(start) }}</span>
        <span class="ml-1.5 font-semibold">{{
          booking.customer.name ?? booking.customer.phone
        }}</span>
      </span>
      <template v-else>
        <span class="font-mono text-[11px] tabular opacity-80">
          {{ clock(start) }}–{{ clock(start + booking.duration_minutes) }}
        </span>
        <span class="truncate font-semibold">{{
          booking.customer.name ?? booking.customer.phone
        }}</span>
        <span class="truncate opacity-80">{{ booking.service.name }}</span>
      </template>
    </div>
    <div
      v-if="booking.buffer_minutes > 0"
      class="pole-stripe opacity-25"
      :style="{ height: bufferHeight }"
      aria-hidden="true"
    />
    <span
      v-if="flagged"
      class="absolute -top-1.5 -right-1.5 grid size-5 place-items-center bg-pole font-mono text-[11px] font-bold text-paper"
      :title="t('calendar.needsOutcome')"
    >
      !
    </span>
  </div>
</template>
