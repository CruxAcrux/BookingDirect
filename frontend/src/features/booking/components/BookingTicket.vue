<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

import { useFormat } from '@/composables/useFormat'

import type { PublicBooking } from '../api'

const { booking } = defineProps<{ booking: PublicBooking }>()

const { t } = useI18n()
const format = useFormat()

const time = computed(() => booking.starts_at_local.slice(11, 16))
const date = computed(() =>
  format.localDateTime(booking.starts_at_local, { weekday: 'long', day: 'numeric', month: 'long' }),
)
const stamp = computed(() =>
  booking.status === 'confirmed' || booking.status === 'pending'
    ? 'bg-pole text-paper'
    : 'border-2 border-ink text-ink',
)
</script>

<template>
  <div class="w-full max-w-sm drop-shadow-[6px_6px_0_var(--color-ink)]">
    <article :aria-label="t('booking.ticket.label')" class="ticket bg-paper-deep text-ink">
      <div
        class="flex items-baseline justify-between border-b border-dashed border-ink/40 px-5 pt-5 pb-3"
      >
        <span class="font-mono text-xs tracking-widest uppercase">
          {{ t('home.ticket.number') }} {{ String(booking.id).padStart(4, '0') }}
        </span>
        <span class="px-1.5 py-0.5 font-mono text-[11px] tracking-wider uppercase" :class="stamp">
          {{ t(`booking.status.${booking.status}`) }}
        </span>
      </div>
      <dl class="space-y-4 px-5 py-5">
        <div>
          <dt class="font-mono text-[11px] tracking-widest text-ink-soft uppercase">
            {{ t('home.ticket.time') }}
          </dt>
          <dd
            class="text-6xl leading-none font-black tabular font-condensed"
            :class="{ 'line-through decoration-pole decoration-4': booking.status === 'cancelled' }"
          >
            {{ time }}
          </dd>
          <dd class="mt-1 font-mono text-xs first-letter:uppercase">{{ date }}</dd>
        </div>
        <div class="grid grid-cols-2 gap-4 border-t border-ink/15 pt-4">
          <div>
            <dt class="font-mono text-[11px] tracking-widest text-ink-soft uppercase">
              {{ t('home.ticket.service') }}
            </dt>
            <dd class="mt-0.5 leading-tight font-semibold">{{ booking.service.name }}</dd>
          </div>
          <div>
            <dt class="font-mono text-[11px] tracking-widest text-ink-soft uppercase">
              {{ t('home.ticket.barber') }}
            </dt>
            <dd class="mt-0.5 leading-tight font-semibold">{{ booking.barber.display_name }}</dd>
          </div>
        </div>
      </dl>
      <div class="flex justify-between bg-ink px-5 py-3 font-mono text-sm text-paper tabular">
        <span>{{ format.duration(booking.duration_minutes) }}</span>
        <span>{{ format.price(booking.price_ore) }}</span>
      </div>
    </article>
  </div>
</template>

<style scoped>
.ticket {
  mask:
    radial-gradient(circle 9px at 0 3.4rem, transparent 98%, black) left / 51% 100% no-repeat,
    radial-gradient(circle 9px at 100% 3.4rem, transparent 98%, black) right / 51% 100% no-repeat;
}
</style>
