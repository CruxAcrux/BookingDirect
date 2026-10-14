<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const { t, d, n } = useI18n()

// Fixed sample values; the ticket is an illustration, not live data.
const startsAt = new Date(2026, 0, 16, 14, 30)
const price = 520
const minutes = 45
</script>

<template>
  <!-- The shadow sits on a wrapper because the mask on the ticket would clip it. -->
  <div class="w-full max-w-xs rotate-[1.5deg] drop-shadow-[6px_6px_0_var(--color-ink)]">
    <figure :aria-label="t('home.ticket.label')" class="ticket bg-paper-deep text-ink">
      <div
        class="flex items-baseline justify-between border-b border-dashed border-ink/40 px-5 pt-5 pb-3"
      >
        <span class="font-mono text-xs tracking-widest uppercase"
          >{{ t('home.ticket.number') }} 0427</span
        >
        <span
          class="bg-pole px-1.5 py-0.5 font-mono text-[11px] tracking-wider text-paper uppercase"
        >
          {{ t('home.ticket.status') }}
        </span>
      </div>

      <dl class="space-y-4 px-5 py-5">
        <div>
          <dt class="font-mono text-[11px] tracking-widest text-ink-soft uppercase">
            {{ t('home.ticket.time') }}
          </dt>
          <dd class="text-6xl leading-none font-black tabular font-condensed">
            {{ d(startsAt, 'time') }}
          </dd>
          <dd class="mt-1 font-mono text-xs">{{ d(startsAt, 'date') }}</dd>
        </div>
        <div class="grid grid-cols-2 gap-4 border-t border-ink/15 pt-4">
          <div>
            <dt class="font-mono text-[11px] tracking-widest text-ink-soft uppercase">
              {{ t('home.ticket.service') }}
            </dt>
            <dd class="mt-0.5 leading-tight font-semibold">{{ t('home.ticket.serviceValue') }}</dd>
          </div>
          <div>
            <dt class="font-mono text-[11px] tracking-widest text-ink-soft uppercase">
              {{ t('home.ticket.barber') }}
            </dt>
            <dd class="mt-0.5 leading-tight font-semibold">Samir</dd>
          </div>
        </div>
      </dl>

      <div class="flex justify-between bg-ink px-5 py-3 font-mono text-sm text-paper tabular">
        <span>{{ t('home.ticket.duration', { minutes }) }}</span>
        <span>{{ n(price, 'currency') }}</span>
      </div>
    </figure>
  </div>
</template>

<style scoped>
/* Punched notches on both sides, like a tear-off queue ticket. */
.ticket {
  mask:
    radial-gradient(circle 9px at 0 3.4rem, transparent 98%, black) left / 51% 100% no-repeat,
    radial-gradient(circle 9px at 100% 3.4rem, transparent 98%, black) right / 51% 100% no-repeat;
}
</style>
