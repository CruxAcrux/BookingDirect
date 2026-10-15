<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useForm } from 'vee-validate'
import { z } from 'zod'

import AppButton from '@/components/ui/AppButton.vue'
import FormAlert from '@/components/ui/FormAlert.vue'
import FormDialog from '@/components/ui/FormDialog.vue'
import SelectField from '@/components/ui/SelectField.vue'
import TextareaField from '@/components/ui/TextareaField.vue'
import TextField from '@/components/ui/TextField.vue'
import { useToast } from '@/composables/useToast'
import { useServices } from '@/features/services/api'
import { ApiError } from '@/lib/apiError'
import { optionalEmail, optionalString, requiredString } from '@/lib/forms/schemas'
import { applyApiError } from '@/lib/forms/serverErrors'
import { toTypedSchema } from '@/lib/forms/toTypedSchema'
import { useErrorText } from '@/lib/forms/useErrorText'
import { refDebounced } from '@/lib/refDebounced'

import {
  useCreateStaffBooking,
  useCustomerSearch,
  type CalendarBarber,
  type CustomerMatch,
} from '../api'
import { clockOf, localToUtc, minutesOf } from '../lib/grid'

const { barbers, timezone, initial } = defineProps<{
  barbers: CalendarBarber[]
  timezone: string
  initial: { barberId: number; date: string; minutes: number }
}>()

const open = defineModel<boolean>('open', { required: true })

const { t } = useI18n()
const errorText = useErrorText()
const toast = useToast()
const services = useServices()
const create = useCreateStaffBooking()

const activeServices = computed(() =>
  (services.data.value ?? []).filter((service) => service.is_active),
)
const formError = ref<string | null>(null)

const { defineField, handleSubmit, errors, setErrors, resetForm, setValues } = useForm({
  validationSchema: toTypedSchema(
    z.object({
      service_id: z.number({ error: 'validation.required' }),
      barber_id: z.number(),
      date: requiredString(),
      time: requiredString(),
      name: requiredString(),
      phone: requiredString(),
      email: optionalEmail(),
      staff_note: optionalString(),
    }),
  ),
})

const [serviceId] = defineField('service_id')
const [barberId] = defineField('barber_id')
const [date] = defineField('date')
const [time] = defineField('time')
const [name] = defineField('name')
const [phone] = defineField('phone')
const [email] = defineField('email')
const [staffNote] = defineField('staff_note')

watch(
  open,
  (isOpen) => {
    if (!isOpen) return
    formError.value = null
    search.value = ''
    resetForm({
      values: {
        service_id: activeServices.value[0]?.id,
        barber_id: initial.barberId,
        date: initial.date,
        time: clockOf(initial.minutes),
        name: '',
        phone: '',
        email: '',
        staff_note: '',
      },
    })
  },
  { immediate: true },
)

// Customer search
const search = ref('')
const debouncedSearch = refDebounced(search, 250)
const matches = useCustomerSearch(debouncedSearch)

function pick(customer: CustomerMatch) {
  setValues({ name: customer.name ?? '', phone: customer.phone, email: customer.email ?? '' })
  search.value = ''
}

const onSubmit = handleSubmit(async (values) => {
  formError.value = null
  try {
    await create.mutateAsync({
      barber_id: values.barber_id,
      service_id: values.service_id,
      starts_at: localToUtc(values.date, minutesOf(values.time), timezone),
      customer: { name: values.name, phone: values.phone, email: values.email },
      staff_note: values.staff_note,
    })
    open.value = false
    toast.show(t('calendar.created'))
  } catch (error) {
    if (error instanceof ApiError && error.status === 409) {
      formError.value = error.message
      return
    }
    formError.value = applyApiError(
      error,
      (fieldErrors) => {
        setErrors({
          ...fieldErrors,
          name: fieldErrors['customer.name'],
          phone: fieldErrors['customer.phone'],
          email: fieldErrors['customer.email'],
          date: fieldErrors.starts_at,
        })
      },
      [
        'service_id',
        'barber_id',
        'starts_at',
        'customer.name',
        'customer.phone',
        'customer.email',
        'staff_note',
      ],
      t('errors.generic'),
    )
  }
})
</script>

<template>
  <FormDialog v-model:open="open" :title="t('calendar.newBooking')">
    <form class="space-y-5" novalidate @submit="onSubmit">
      <FormAlert v-if="formError">{{ formError }}</FormAlert>
      <div class="grid gap-4 sm:grid-cols-2">
        <SelectField
          v-if="serviceId !== undefined"
          v-model="serviceId"
          :label="t('calendar.fields.service')"
          :options="activeServices.map((service) => ({ value: service.id, label: service.name }))"
          :error="errorText(errors.service_id)"
        />
        <SelectField
          v-if="barberId !== undefined"
          v-model="barberId"
          :label="t('calendar.fields.barber')"
          :options="barbers.map((barber) => ({ value: barber.id, label: barber.display_name }))"
        />
        <TextField
          v-model="date"
          type="date"
          :label="t('calendar.fields.date')"
          :error="errorText(errors.date)"
        />
        <TextField
          v-model="time"
          type="time"
          step="300"
          :label="t('calendar.fields.time')"
          :error="errorText(errors.time)"
        />
      </div>

      <fieldset class="space-y-4 border-t-2 border-ink pt-4">
        <legend class="bg-paper pr-2 font-mono text-xs tracking-widest uppercase">
          {{ t('calendar.fields.customer') }}
        </legend>
        <div class="relative">
          <TextField
            v-model="search"
            type="text"
            autocomplete="off"
            :label="t('calendar.searchCustomer')"
            :hint="t('calendar.searchHint')"
          />
          <ul
            v-if="search.trim().length >= 2 && matches.data.value?.length"
            class="absolute inset-x-0 top-full z-10 mt-1 border-2 border-ink bg-paper shadow-[4px_4px_0_var(--color-ink)]"
          >
            <li v-for="customer in matches.data.value" :key="customer.id">
              <button
                type="button"
                class="flex w-full justify-between gap-3 px-3 py-2 text-left hover:bg-paper-deep"
                @click="pick(customer)"
              >
                <span>{{ customer.name ?? '—' }}</span>
                <span class="font-mono text-xs text-ink-soft tabular">{{ customer.phone }}</span>
              </button>
            </li>
          </ul>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <TextField
            v-model="name"
            :label="t('calendar.fields.name')"
            :error="errorText(errors.name)"
          />
          <TextField
            v-model="phone"
            type="tel"
            :label="t('calendar.fields.phone')"
            :error="errorText(errors.phone)"
          />
        </div>
        <TextField
          v-model="email"
          type="email"
          :label="t('calendar.fields.email')"
          :error="errorText(errors.email)"
        />
      </fieldset>

      <TextareaField
        v-model="staffNote"
        :rows="2"
        :label="t('calendar.fields.staffNote')"
        :error="errorText(errors.staff_note)"
      />
      <p class="text-sm text-ink-soft">{{ t('calendar.staffRules') }}</p>
      <div class="flex justify-end">
        <AppButton type="submit" :loading="create.isPending.value">{{
          t('calendar.book')
        }}</AppButton>
      </div>
    </form>
  </FormDialog>
</template>
