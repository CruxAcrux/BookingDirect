<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

import AppButton from '@/components/ui/AppButton.vue'
import PageHeader from '@/components/ui/PageHeader.vue'
import SelectField from '@/components/ui/SelectField.vue'
import TextField from '@/components/ui/TextField.vue'
import { refDebounced } from '@/lib/refDebounced'

import { SMS_TYPES, useSentSms, type SentSms, type SmsType } from '../api'
import { linkify } from '../lib/linkify'

const { t, d } = useI18n()

const type = ref<SmsType | ''>('')
const phone = ref('')
const debouncedPhone = refDebounced(phone, 300)
const page = ref(1)
watch([type, debouncedPhone], () => {
  page.value = 1
})

const messages = useSentSms(() => ({
  type: type.value,
  phone: debouncedPhone.value,
  page: page.value,
}))

const typeOptions = computed(() => [
  { value: '' as const, label: t('sms.allTypes') },
  ...SMS_TYPES.map((value) => ({ value, label: t(`sms.types.${value}`) })),
])

/** Messages grouped under a heading per day, keeping the newest-first order. */
const days = computed(() => {
  const groups: { label: string; messages: SentSms[] }[] = []
  for (const message of messages.data.value?.data ?? []) {
    const label = d(new Date(message.sent_at), 'long')
    const group = groups.at(-1)
    if (group?.label === label) group.messages.push(message)
    else groups.push({ label, messages: [message] })
  }
  return groups
})

const tone: Record<SmsType, string> = {
  booking_confirmed: 'bg-ink text-paper',
  booking_rescheduled: 'border border-ink',
  booking_cancelled: 'border border-pole text-pole-deep',
  reminder_24h: 'bg-brass/30',
  reminder_2h: 'bg-brass/30',
  otp: 'border border-line text-ink-soft',
}
</script>

<template>
  <div class="mx-auto max-w-4xl px-4 py-8 sm:px-8 md:py-12">
    <PageHeader :kicker="t('sms.kicker')" :title="t('sms.title')">
      <template #description>
        <p class="mt-3 max-w-xl text-ink-soft">{{ t('sms.lead') }}</p>
      </template>
    </PageHeader>

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
      <SelectField v-model="type" :label="t('sms.type')" :options="typeOptions" />
      <TextField v-model="phone" type="tel" inputmode="tel" :label="t('sms.phone')" />
    </div>

    <p v-if="messages.isPending.value" class="mt-8 font-mono text-sm text-ink-soft">
      {{ t('common.loading') }}
    </p>
    <p v-else-if="messages.isError.value" class="mt-8 text-pole-deep">{{ t('errors.generic') }}</p>
    <template v-else-if="messages.data.value">
      <p
        v-if="days.length === 0"
        class="mt-10 border-2 border-dashed border-line p-6 text-ink-soft"
      >
        {{ type || debouncedPhone ? t('sms.noMatches') : t('sms.empty') }}
      </p>

      <section v-for="day in days" :key="day.label" class="mt-10">
        <h2
          class="sticky top-0 z-10 border-b-2 border-ink bg-paper py-2 text-xl font-extrabold uppercase font-condensed first-letter:uppercase"
        >
          {{ day.label }}
        </h2>
        <ol class="divide-y divide-line">
          <li
            v-for="message in day.messages"
            :key="message.id"
            class="grid gap-x-6 gap-y-2 py-4 sm:grid-cols-[9rem_1fr]"
          >
            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 sm:flex-col">
              <time :datetime="message.sent_at" class="font-mono text-sm tabular">
                {{ d(new Date(message.sent_at), 'time') }}
              </time>
              <span
                class="px-1.5 py-0.5 font-mono text-[11px] tracking-wider uppercase"
                :class="tone[message.type]"
              >
                {{ t(`sms.types.${message.type}`) }}
              </span>
              <a
                :href="`tel:${message.to}`"
                class="font-mono text-xs text-ink-soft tabular hover:text-pole"
              >
                {{ message.to }}
              </a>
            </div>
            <p
              class="relative max-w-prose border-2 border-ink bg-white px-4 py-3 text-[15px] leading-snug break-words whitespace-pre-line shadow-[3px_3px_0_var(--color-ink)]"
            >
              <template v-for="(part, index) in linkify(message.body)" :key="index">
                <a
                  v-if="part.href"
                  :href="part.href"
                  target="_blank"
                  rel="noopener"
                  class="break-all text-pole-deep underline underline-offset-2"
                  >{{ part.text }}</a
                >
                <template v-else>{{ part.text }}</template>
              </template>
            </p>
          </li>
        </ol>
      </section>

      <nav
        v-if="messages.data.value.meta.last_page > 1"
        :aria-label="t('sms.pagination')"
        class="mt-8 flex items-center gap-4 font-mono text-sm"
      >
        <AppButton size="sm" variant="ghost" :disabled="page <= 1" @click="page--">
          ← {{ t('sms.newer') }}
        </AppButton>
        <span>{{ t('admin.page', { page, total: messages.data.value.meta.last_page }) }}</span>
        <AppButton
          size="sm"
          variant="ghost"
          :disabled="page >= messages.data.value.meta.last_page"
          @click="page++"
        >
          {{ t('sms.older') }} →
        </AppButton>
      </nav>
    </template>
  </div>
</template>
