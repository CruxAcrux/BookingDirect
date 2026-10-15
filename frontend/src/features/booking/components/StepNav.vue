<script setup lang="ts">
import { useI18n } from 'vue-i18n'

import { BOOKING_STEPS, type BookingStep } from '../composables/useBookingDraft'

const { current, answers } = defineProps<{
  current: BookingStep
  /** What was chosen in each completed step, shown in place of the step name. */
  answers: Partial<Record<BookingStep, string>>
}>()

const emit = defineEmits<{ go: [step: BookingStep] }>()

const { t } = useI18n()
</script>

<template>
  <nav :aria-label="t('booking.steps.label')">
    <ol class="grid grid-cols-4 border-2 border-ink">
      <li
        v-for="(step, index) in BOOKING_STEPS"
        :key="step"
        class="min-w-0 border-ink not-first:border-l-2"
        :class="step === current ? 'bg-ink text-paper' : ''"
      >
        <component
          :is="BOOKING_STEPS.indexOf(step) < BOOKING_STEPS.indexOf(current) ? 'button' : 'div'"
          type="button"
          class="block w-full px-2 py-2 text-left sm:px-3"
          :class="{
            'hover:bg-paper-deep': BOOKING_STEPS.indexOf(step) < BOOKING_STEPS.indexOf(current),
          }"
          :aria-current="step === current ? 'step' : undefined"
          @click="BOOKING_STEPS.indexOf(step) < BOOKING_STEPS.indexOf(current) && emit('go', step)"
        >
          <span
            class="block font-mono text-[11px] tabular"
            :class="step === current ? 'text-paper/70' : 'text-pole'"
          >
            {{ String(index + 1).padStart(2, '0') }}
          </span>
          <span class="block truncate font-mono text-[11px] tracking-wider uppercase sm:text-xs">
            {{ answers[step] ?? t(`booking.steps.${step}`) }}
          </span>
        </component>
      </li>
    </ol>
  </nav>
</template>
