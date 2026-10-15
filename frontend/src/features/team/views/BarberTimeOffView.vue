<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useForm } from 'vee-validate'
import { z } from 'zod'

import AppButton from '@/components/ui/AppButton.vue'
import FormAlert from '@/components/ui/FormAlert.vue'
import SelectField from '@/components/ui/SelectField.vue'
import TextField from '@/components/ui/TextField.vue'
import ToggleField from '@/components/ui/ToggleField.vue'
import { useConfirm } from '@/composables/useConfirm'
import { useFormat } from '@/composables/useFormat'
import { useToast } from '@/composables/useToast'
import { optionalString } from '@/lib/forms/schemas'
import { applyApiError } from '@/lib/forms/serverErrors'
import { toTypedSchema } from '@/lib/forms/toTypedSchema'
import { useErrorText } from '@/lib/forms/useErrorText'

import { useTimeOff, useTimeOffMutations, type TimeOff } from '../api'
import { useBarberContext } from '../composables/useBarberContext'
import { addDays } from '@/lib/dates'

import { toTimeOffRange } from '../lib/timeOff'

const REASONS = ['vacation', 'sick', 'other'] as const

const { t } = useI18n()
const errorText = useErrorText()
const format = useFormat()
const toast = useToast()
const { confirm } = useConfirm()
const { barberId, canEditOwn } = useBarberContext()

const timeOff = useTimeOff(barberId)
const { create, remove } = useTimeOffMutations(barberId)
const formError = ref<string | null>(null)

const today = new Date().toISOString().slice(0, 10)
const dateField = () => z.string().regex(/^\d{4}-\d{2}-\d{2}$/, { error: 'validation.required' })

const { defineField, handleSubmit, errors, setErrors, resetForm, values } = useForm({
  validationSchema: toTypedSchema(
    z
      .object({
        allDay: z.boolean(),
        startDate: dateField(),
        endDate: dateField(),
        startTime: z.string(),
        endTime: z.string(),
        reason: z.enum(REASONS),
        note: optionalString(),
      })
      .refine((v) => (!v.allDay ? v.startTime !== '' && v.endTime !== '' : true), {
        error: 'validation.required',
        path: ['endTime'],
      })
      .refine(
        (v) => {
          const range = toTimeOffRange(v)
          return range.ends_at > range.starts_at
        },
        { error: 'validation.endAfterStart', path: ['endDate'] },
      ),
  ),
  initialValues: {
    allDay: true,
    startDate: today,
    endDate: today,
    startTime: '09:00',
    endTime: '12:00',
    reason: 'vacation',
    note: '',
  },
})

const [allDay] = defineField('allDay')
const [startDate] = defineField('startDate')
const [endDate] = defineField('endDate')
const [startTime] = defineField('startTime')
const [endTime] = defineField('endTime')
const [reason] = defineField('reason')
const [note] = defineField('note')

const reasonOptions = computed(() =>
  REASONS.map((value) => ({ value, label: t(`team.timeOff.reasons.${value}`) })),
)

const onSubmit = handleSubmit(async (form) => {
  formError.value = null
  try {
    await create.mutateAsync({ ...toTimeOffRange(form), reason: form.reason, note: form.note })
    resetForm({ values: { ...values, note: '' } })
    toast.show(t('team.timeOff.added'))
  } catch (error) {
    formError.value = applyApiError(error, setErrors, [], t('errors.generic'))
  }
})

function describe(entry: TimeOff): string {
  const date = { day: 'numeric', month: 'short' } as const
  if (entry.all_day) {
    const lastDay = addDays(entry.ends_at_local.slice(0, 10), -1)
    const first = format.localDateTime(entry.starts_at_local, { weekday: 'short', ...date })
    return lastDay === entry.starts_at_local.slice(0, 10)
      ? first
      : `${first} – ${format.localDateTime(`${lastDay}T00:00`, { weekday: 'short', ...date })}`
  }
  const time = { hour: '2-digit', minute: '2-digit', hourCycle: 'h23' } as const
  return `${format.localDateTime(entry.starts_at_local, { ...date, ...time })} – ${format.localDateTime(entry.ends_at_local, entry.starts_at_local.slice(0, 10) === entry.ends_at_local.slice(0, 10) ? time : { ...date, ...time })}`
}

async function destroy(entry: TimeOff) {
  const confirmed = await confirm({
    title: t('team.timeOff.deleteTitle'),
    body: describe(entry),
    confirmLabel: t('common.delete'),
    tone: 'danger',
  })
  if (!confirmed) return
  try {
    await remove.mutateAsync(entry.id)
  } catch {
    toast.show(t('errors.generic'), 'error')
  }
}
</script>

<template>
  <div class="grid gap-10 lg:grid-cols-[1fr_20rem]">
    <section aria-labelledby="time-off-list">
      <h2 id="time-off-list" class="text-2xl font-extrabold uppercase font-condensed">
        {{ t('team.timeOff.upcoming') }}
      </h2>
      <p v-if="timeOff.isPending.value" class="mt-4 font-mono text-sm text-ink-soft">
        {{ t('common.loading') }}
      </p>
      <p v-else-if="timeOff.data.value?.length === 0" class="mt-4 text-ink-soft">
        {{ t('team.timeOff.empty') }}
      </p>
      <ul v-else class="mt-4 divide-y divide-line border-y border-line">
        <li
          v-for="entry in timeOff.data.value"
          :key="entry.id"
          class="flex items-center justify-between gap-4 py-3"
        >
          <div>
            <p class="font-medium first-letter:uppercase">{{ describe(entry) }}</p>
            <p class="font-mono text-xs text-ink-soft uppercase">
              {{ t(`team.timeOff.reasons.${entry.reason}`) }}
              <template v-if="entry.note"> · {{ entry.note }}</template>
            </p>
          </div>
          <AppButton v-if="canEditOwn" size="sm" variant="ghost" @click="destroy(entry)">
            {{ t('common.delete') }}
          </AppButton>
        </li>
      </ul>
    </section>

    <section v-if="canEditOwn" aria-labelledby="time-off-form" class="border-2 border-ink p-5">
      <h2 id="time-off-form" class="text-2xl font-extrabold uppercase font-condensed">
        {{ t('team.timeOff.add') }}
      </h2>
      <form class="mt-4 space-y-4" novalidate @submit="onSubmit">
        <FormAlert v-if="formError">{{ formError }}</FormAlert>
        <ToggleField v-model="allDay" :label="t('team.timeOff.allDay')" />
        <div class="grid grid-cols-2 gap-3">
          <TextField
            v-model="startDate"
            type="date"
            :label="t('team.timeOff.from')"
            :error="errorText(errors.startDate)"
          />
          <TextField
            v-model="endDate"
            type="date"
            :label="t('team.timeOff.to')"
            :error="errorText(errors.endDate)"
          />
          <template v-if="!allDay">
            <TextField v-model="startTime" type="time" :label="t('team.timeOff.startTime')" />
            <TextField
              v-model="endTime"
              type="time"
              :label="t('team.timeOff.endTime')"
              :error="errorText(errors.endTime)"
            />
          </template>
        </div>
        <SelectField
          v-if="reason"
          v-model="reason"
          :label="t('team.timeOff.reason')"
          :options="reasonOptions"
        />
        <TextField v-model="note" :label="t('team.timeOff.note')" :error="errorText(errors.note)" />
        <AppButton type="submit" class="w-full" :loading="create.isPending.value">{{
          t('team.timeOff.add')
        }}</AppButton>
      </form>
    </section>
  </div>
</template>
