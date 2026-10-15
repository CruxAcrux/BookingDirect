<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useForm } from 'vee-validate'
import { z } from 'zod'

import AppButton from '@/components/ui/AppButton.vue'
import FormAlert from '@/components/ui/FormAlert.vue'
import TextareaField from '@/components/ui/TextareaField.vue'
import TextField from '@/components/ui/TextField.vue'
import ToggleField from '@/components/ui/ToggleField.vue'
import {
  integerField,
  kronorField,
  optionalIntegerField,
  optionalString,
  oreToKronorInput,
  requiredString,
} from '@/lib/forms/schemas'
import { applyApiError } from '@/lib/forms/serverErrors'
import { toTypedSchema } from '@/lib/forms/toTypedSchema'
import { useErrorText } from '@/lib/forms/useErrorText'

import type { Service, ServiceInput } from '../api'

const { service } = defineProps<{ service?: Service }>()

const emit = defineEmits<{ submit: [values: ServiceInput, done: (error?: unknown) => void] }>()

const FIELDS = [
  'name',
  'description',
  'duration_minutes',
  'buffer_minutes',
  'price_ore',
  'rebook_after_days',
  'is_active',
] as const

const { t } = useI18n()
const errorText = useErrorText()
const formError = ref<string | null>(null)

const durationField = () =>
  integerField(5, 480).refine((value) => value % 5 === 0, { error: 'validation.stepOfFive' })

const { defineField, handleSubmit, errors, setErrors, isSubmitting } = useForm({
  validationSchema: toTypedSchema(
    z.object({
      name: requiredString(),
      description: optionalString(),
      duration_minutes: durationField(),
      buffer_minutes: integerField(0, 120).refine((value) => value % 5 === 0, {
        error: 'validation.stepOfFive',
      }),
      price_ore: kronorField(),
      rebook_after_days: optionalIntegerField(1, 365),
      is_active: z.boolean(),
    }),
  ),
  initialValues: {
    name: service?.name ?? '',
    description: service?.description ?? '',
    duration_minutes: String(service?.duration_minutes ?? 30),
    buffer_minutes: String(service?.buffer_minutes ?? 0),
    price_ore: service ? oreToKronorInput(service.price_ore) : '',
    rebook_after_days: service?.rebook_after_days ? String(service.rebook_after_days) : '',
    is_active: service?.is_active ?? true,
  },
})

const [name] = defineField('name')
const [description] = defineField('description')
const [duration] = defineField('duration_minutes')
const [buffer] = defineField('buffer_minutes')
const [price] = defineField('price_ore')
const [rebook] = defineField('rebook_after_days')
const [active] = defineField('is_active')

const onSubmit = handleSubmit(
  (values) =>
    new Promise<void>((resolve) => {
      formError.value = null
      emit('submit', values, (error) => {
        if (error) formError.value = applyApiError(error, setErrors, FIELDS, t('errors.generic'))
        resolve()
      })
    }),
)
</script>

<template>
  <form class="space-y-5" novalidate @submit="onSubmit">
    <FormAlert v-if="formError">{{ formError }}</FormAlert>
    <TextField v-model="name" :label="t('services.fields.name')" :error="errorText(errors.name)" />
    <TextareaField
      v-model="description"
      :rows="3"
      :label="t('services.fields.description')"
      :error="errorText(errors.description)"
    />
    <div class="grid gap-5 sm:grid-cols-3">
      <TextField
        v-model="duration"
        inputmode="numeric"
        :label="t('services.fields.duration')"
        :suffix="t('common.minShort')"
        :error="errorText(errors.duration_minutes)"
      />
      <TextField
        v-model="buffer"
        inputmode="numeric"
        :label="t('services.fields.buffer')"
        :suffix="t('common.minShort')"
        :error="errorText(errors.buffer_minutes)"
      />
      <TextField
        v-model="price"
        inputmode="decimal"
        :label="t('services.fields.price')"
        suffix="kr"
        :error="errorText(errors.price_ore)"
      />
    </div>
    <TextField
      v-model="rebook"
      inputmode="numeric"
      :label="t('services.fields.rebookAfter')"
      :suffix="t('common.daysShort')"
      :hint="t('services.rebookHint')"
      :error="errorText(errors.rebook_after_days)"
    />
    <ToggleField
      v-model="active"
      :label="t('services.fields.active')"
      :description="t('services.activeHint')"
    />
    <div class="flex justify-end">
      <AppButton type="submit" :loading="isSubmitting">{{ t('common.save') }}</AppButton>
    </div>
  </form>
</template>
