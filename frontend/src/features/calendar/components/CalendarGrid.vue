<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { clockIn, todayIn } from '@/lib/dates'

import type { StaffBooking } from '../api'
import type { Column } from '../lib/columns'
import { PX_PER_MINUTE, clockOf, minutesAt, minutesOf, snap, topOf, type Window } from '../lib/grid'
import BookingBlock from './BookingBlock.vue'

const { columns, window, timezone, headerFor, canEdit } = defineProps<{
  columns: Column[]
  window: Window
  timezone: string
  headerFor: (column: Column) => { title: string; subtitle?: string }
  /** Whether the signed-in user may move or add bookings in a column. */
  canEdit: (column: Column) => boolean
}>()

const emit = defineEmits<{
  open: [booking: StaffBooking]
  create: [column: Column, minutes: number]
  move: [booking: StaffBooking, column: Column, minutes: number]
}>()

const { t } = useI18n()

const height = computed(() => (window.end - window.start) * PX_PER_MINUTE)
const hours = computed(() => {
  const list: number[] = []
  for (let minutes = window.start; minutes < window.end; minutes += 60) list.push(minutes)
  return list
})

const today = todayIn(timezone)
const now = clockIn(timezone)
const nowMinutes = minutesOf(now.time)

const shade = (range: { start: number; end: number }) => ({
  top: `${String(topOf(Math.max(range.start, window.start), window))}px`,
  height: `${String((Math.min(range.end, window.end) - Math.max(range.start, window.start)) * PX_PER_MINUTE)}px`,
})

// Dragging
interface Drag {
  booking: StaffBooking
  pointerId: number
  startY: number
  startX: number
  originMinutes: number
  column: Column
  minutes: number
  moved: boolean
}
const drag = ref<Drag | null>(null)
/** Set after a drag ends, so the click event that follows does not also open the booking. */
let suppressClick = false

function columnAt(x: number, y: number): Column | undefined {
  const element = document
    .elementsFromPoint(x, y)
    .find((el) => el instanceof HTMLElement && el.dataset.column)
  const key = element instanceof HTMLElement ? element.dataset.column : undefined
  return columns.find((column) => column.key === key)
}

function onPointerDown(event: PointerEvent, booking: StaffBooking, column: Column) {
  if (event.button !== 0) return
  if (
    !canEdit(column) ||
    booking.status !== 'confirmed' ||
    Date.parse(booking.starts_at) < Date.now()
  ) {
    return
  }
  ;(event.currentTarget as HTMLElement).setPointerCapture(event.pointerId)
  const origin = minutesOf(booking.starts_at_local)
  drag.value = {
    booking,
    pointerId: event.pointerId,
    startY: event.clientY,
    startX: event.clientX,
    originMinutes: origin,
    column,
    minutes: origin,
    moved: false,
  }
}

function onPointerMove(event: PointerEvent) {
  const state = drag.value
  if (!state || event.pointerId !== state.pointerId) return

  const dy = event.clientY - state.startY
  const dx = event.clientX - state.startX
  if (!state.moved && Math.hypot(dx, dy) < 6) return

  state.moved = true
  const target = columnAt(event.clientX, event.clientY)
  if (target && canEdit(target)) state.column = target
  const latestStart = window.end - state.booking.duration_minutes
  state.minutes = Math.min(
    Math.max(snap(state.originMinutes + dy / PX_PER_MINUTE), window.start),
    latestStart,
  )
}

function onPointerUp(event: PointerEvent) {
  const state = drag.value
  if (!state || event.pointerId !== state.pointerId) return
  drag.value = null

  if (!state.moved) return
  suppressClick = true
  const unchanged =
    state.minutes === state.originMinutes && state.column.key === columnOf(state.booking)
  if (!unchanged) emit('move', state.booking, state.column, state.minutes)
}

function onBlockClick(booking: StaffBooking) {
  if (suppressClick) {
    suppressClick = false
    return
  }
  emit('open', booking)
}

function columnOf(booking: StaffBooking): string {
  return `${String(booking.barber_id)}:${booking.starts_at_local.slice(0, 10)}`
}

function onColumnClick(event: MouseEvent, column: Column) {
  if (!canEdit(column) || event.target !== event.currentTarget) return
  const offset = event.clientY - (event.currentTarget as HTMLElement).getBoundingClientRect().top
  emit('create', column, minutesAt(offset, window, 15))
}

function onBlockKey(event: KeyboardEvent, booking: StaffBooking) {
  if (event.key === 'Enter' || event.key === ' ') {
    event.preventDefault()
    emit('open', booking)
  }
}
</script>

<template>
  <div
    class="relative max-h-[calc(100dvh-14rem)] overflow-auto border-2 border-ink bg-paper"
    :class="{ 'cursor-grabbing select-none': drag?.moved }"
    @keydown.escape="drag = null"
  >
    <div
      class="grid min-w-full"
      :style="{ gridTemplateColumns: `3.5rem repeat(${columns.length}, minmax(9.5rem, 1fr))` }"
    >
      <!-- Header row -->
      <div class="sticky top-0 left-0 z-30 border-b-2 border-ink bg-paper" />
      <div
        v-for="column in columns"
        :key="`head-${column.key}`"
        class="sticky top-0 z-20 border-b-2 border-l border-ink bg-paper px-2 py-2"
        :class="{ 'bg-paper-deep': column.date === today }"
      >
        <p class="truncate text-lg leading-none font-extrabold uppercase font-condensed">
          {{ headerFor(column).title }}
        </p>
        <p
          v-if="headerFor(column).subtitle"
          class="mt-0.5 truncate font-mono text-[11px] text-ink-soft first-letter:uppercase"
        >
          {{ headerFor(column).subtitle }}
        </p>
      </div>

      <!-- Time axis -->
      <div
        class="sticky left-0 z-10 bg-paper"
        :style="{ height: `${height}px` }"
        aria-hidden="true"
      >
        <span
          v-for="minutes in hours"
          :key="minutes"
          class="absolute right-1.5 -translate-y-1/2 font-mono text-[11px] text-ink-soft tabular"
          :style="{ top: `${topOf(minutes, window)}px` }"
        >
          {{ minutes === window.start ? '' : clockOf(minutes) }}
        </span>
      </div>

      <!-- Columns -->
      <div
        v-for="column in columns"
        :key="column.key"
        :data-column="column.key"
        role="group"
        :aria-label="`${headerFor(column).title} ${headerFor(column).subtitle ?? ''}`"
        class="relative border-l border-ink/40 bg-paper-deep/40 closed-hatch"
        :class="{ 'cursor-copy': canEdit(column) }"
        :style="{ height: `${height}px` }"
        @click="onColumnClick($event, column)"
      >
        <div
          v-for="(range, index) in column.open"
          :key="`open-${index}`"
          class="pointer-events-none absolute inset-x-0 bg-paper"
          :style="shade(range)"
        />
        <div
          v-for="minutes in hours"
          :key="`line-${minutes}`"
          class="pointer-events-none absolute inset-x-0 border-t border-line/70"
          :style="{ top: `${topOf(minutes, window)}px` }"
        />
        <div
          v-for="(range, index) in column.blocked"
          :key="`blocked-${index}`"
          class="pointer-events-none absolute inset-x-0 blocked-hatch"
          :style="shade(range)"
        />
        <div
          v-if="column.date === today && nowMinutes >= window.start && nowMinutes <= window.end"
          class="pointer-events-none absolute inset-x-0 z-10 border-t-2 border-pole"
          :style="{ top: `${topOf(nowMinutes, window)}px` }"
          aria-hidden="true"
        />

        <BookingBlock
          v-for="booking in column.bookings"
          :key="booking.id"
          :booking="booking"
          :window="window"
          role="button"
          tabindex="0"
          :aria-label="
            t('calendar.openBooking', {
              time: booking.starts_at_local.slice(11, 16),
              name: booking.customer.name ?? booking.customer.phone,
            })
          "
          class="cursor-pointer touch-none focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-pole"
          :class="{ 'opacity-30': drag?.moved && drag.booking.id === booking.id }"
          @pointerdown="onPointerDown($event, booking, column)"
          @pointermove="onPointerMove"
          @pointerup="onPointerUp"
          @pointercancel="drag = null"
          @click.stop="onBlockClick(booking)"
          @keydown="onBlockKey($event, booking)"
        />

        <BookingBlock
          v-if="drag?.moved && drag.column.key === column.key"
          :booking="drag.booking"
          :window="window"
          :ghost="{ minutes: drag.minutes }"
          class="pointer-events-none"
        />
      </div>
    </div>
  </div>
</template>
