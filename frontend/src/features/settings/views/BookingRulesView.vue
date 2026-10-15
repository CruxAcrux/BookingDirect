<script setup lang="ts">
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useForm } from 'vee-validate'
import { z } from 'zod'

import AppButton from '@/components/ui/AppButton.vue'
import FormAlert from '@/components/ui/FormAlert.vue'
import SelectField from '@/components/ui/SelectField.vue'
import TextField from '@/components/ui/TextField.vue'
import ToggleField from '@/components/ui/ToggleField.vue'
import { useToast } from '@/composables/useToast'
import { integerField } from '@/lib/forms/schemas'
import { applyApiError } from '@/lib/forms/serverErrors'
import { toTypedSchema } from '@/lib/forms/toTypedSchema'
import { useErrorText } from '@/lib/forms/useErrorText'

import { useShop, useUpdateShop, type Shop } from '../api'

const SLOT_INTERVALS = [5, 10, 15, 20, 30, 60] as const
const FIELDS = [
  'min_notice_minutes',
  'max_days_ahead',
  'cancellation_window_hours',
  'slot_interval_minutes',
  'walk_ins_enabled',
] as const

const { t } = useI18n()
const errorText = useErrorText()
const toast = useToast()
const shop = useShop()
const update = useUpdateShop()
const formError = ref<string | null>(null)

const { defineField, handleSubmit, errors, setErrors, resetForm } = useForm({
  validationSchema: toTypedSchema(
    z.object({
      min_notice_minutes: integerField(0, 10080),
      max_days_ahead: integerField(1, 365),
      cancellation_window_hours: integerField(0, 168),
      slot_interval_minutes: z.literal(SLOT_INTERVALS),
      walk_ins_enabled: z.boolean(),
    }),
  ),
})

const [minNotice] = defineField('min_notice_minutes')
const [maxDays] = defineField('max_days_ahead')
const [cancellation] = defineField('cancellation_window_hours')
const [slotInterval] = defineField('slot_interval_minutes')
const [walkIns] = defineField('walk_ins_enabled')

function fill(data: Shop) {
  resetForm({
    values: {
      min_notice_minutes: String(data.min_notice_minutes),
      max_days_ahead: String(data.max_days_ahead),
      cancellation_window_hours: String(data.cancellation_window_hours),
      slot_interval_minutes:
        SLOT_INTERVALS.find((value) => value === data.slot_interval_minutes) ?? 15,
      walk_ins_enabled: data.walk_ins_enabled,
    },
  })
}

watch(
  () => shop.data.value,
  (data) => {
    if (data) fill(data)
  },
  { immediate: true },
)

const onSubmit = handleSubmit(async (values) => {
  formError.value = null
  try {
    fill(await update.mutateAsync(values))
    toast.show(t('common.saved'))
  } catch (error) {
    formError.value = applyApiError(error, setErrors, FIELDS, t('errors.generic'))
  }
})
</script>

<template>
  <p v-if="shop.isPending.value" class="font-mono text-sm text-ink-soft">
    {{ t('common.loading') }}
  </p>
  <form v-else class="max-w-xl space-y-6" novalidate @submit="onSubmit">
    <FormAlert v-if="formError">{{ formError }}</FormAlert>
    <SelectField
      v-if="slotInterval !== undefined"
      v-model="slotInterval"
      :label="t('settings.fields.slotInterval')"
      :options="
        SLOT_INTERVALS.map((value) => ({ value, label: t('common.minutes', { n: value }) }))
      "
    />
    <div class="grid gap-5 sm:grid-cols-3">
      <TextField
        v-model="minNotice"
        inputmode="numeric"
        :label="t('settings.fields.minNotice')"
        :suffix="t('common.minShort')"
        :error="errorText(errors.min_notice_minutes)"
      />
      <TextField
        v-model="maxDays"
        inputmode="numeric"
        :label="t('settings.fields.maxDaysAhead')"
        :suffix="t('common.daysShort')"
        :error="errorText(errors.max_days_ahead)"
      />
      <TextField
        v-model="cancellation"
        inputmode="numeric"
        :label="t('settings.fields.cancellationWindow')"
        :suffix="t('common.hoursShort')"
        :error="errorText(errors.cancellation_window_hours)"
      />
    </div>
    <p class="text-sm text-ink-soft">{{ t('settings.bookingRulesHelp') }}</p>
    <div class="border-t border-line pt-5">
      <ToggleField
        v-model="walkIns"
        :label="t('settings.fields.walkIns')"
        :description="t('settings.walkInsHelp')"
      />
    </div>
    <AppButton type="submit" :loading="update.isPending.value">{{ t('common.save') }}</AppButton>
  </form>
</template>
