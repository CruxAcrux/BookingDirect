<script setup lang="ts">
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useForm } from 'vee-validate'
import { z } from 'zod'

import AppButton from '@/components/ui/AppButton.vue'
import FormAlert from '@/components/ui/FormAlert.vue'
import SelectField from '@/components/ui/SelectField.vue'
import TextField from '@/components/ui/TextField.vue'
import { useToast } from '@/composables/useToast'
import { optionalEmail, optionalString, requiredString } from '@/lib/forms/schemas'
import { applyApiError } from '@/lib/forms/serverErrors'
import { toTypedSchema } from '@/lib/forms/toTypedSchema'
import { useErrorText } from '@/lib/forms/useErrorText'

import { useShop, useUpdateShop, type Shop } from '../api'

const FIELDS = ['name', 'slug', 'address', 'phone', 'email', 'locale', 'timezone'] as const
const TIMEZONES = [
  'Europe/Stockholm',
  'Europe/Oslo',
  'Europe/Copenhagen',
  'Europe/Helsinki',
  'Europe/London',
]

const { t } = useI18n()
const errorText = useErrorText()
const toast = useToast()
const shop = useShop()
const update = useUpdateShop()
const formError = ref<string | null>(null)

const { defineField, handleSubmit, errors, setErrors, resetForm, meta } = useForm({
  validationSchema: toTypedSchema(
    z.object({
      name: requiredString(),
      slug: requiredString().regex(/^[a-z0-9]+(?:-[a-z0-9]+)*$/, { error: 'validation.slug' }),
      address: optionalString(),
      phone: optionalString(),
      email: optionalEmail(),
      locale: z.enum(['sv', 'en']),
      timezone: requiredString(),
    }),
  ),
})

const [name] = defineField('name')
const [slug] = defineField('slug')
const [address] = defineField('address')
const [phone] = defineField('phone')
const [email] = defineField('email')
const [locale] = defineField('locale')
const [timezone] = defineField('timezone')

function fill(data: Shop) {
  resetForm({
    values: {
      name: data.name,
      slug: data.slug,
      address: data.address ?? '',
      phone: data.phone ?? '',
      email: data.email ?? '',
      locale: data.locale === 'en' ? 'en' : 'sv',
      timezone: data.timezone,
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
  <form v-else class="max-w-xl space-y-5" novalidate @submit="onSubmit">
    <FormAlert v-if="formError">{{ formError }}</FormAlert>
    <TextField v-model="name" :label="t('settings.fields.name')" :error="errorText(errors.name)" />
    <TextField
      v-model="slug"
      :label="t('settings.fields.slug')"
      :hint="t('settings.slugHint', { slug: slug || '…' })"
      :error="errorText(errors.slug)"
      autocapitalize="off"
      spellcheck="false"
    />
    <TextField
      v-model="address"
      :label="t('settings.fields.address')"
      autocomplete="street-address"
      :error="errorText(errors.address)"
    />
    <div class="grid gap-5 sm:grid-cols-2">
      <TextField
        v-model="phone"
        type="tel"
        :label="t('settings.fields.phone')"
        :error="errorText(errors.phone)"
      />
      <TextField
        v-model="email"
        type="email"
        :label="t('settings.fields.email')"
        :error="errorText(errors.email)"
      />
    </div>
    <div class="grid gap-5 sm:grid-cols-2">
      <SelectField
        v-if="locale"
        v-model="locale"
        :label="t('settings.fields.locale')"
        :options="[
          { value: 'sv', label: t('locale.sv') },
          { value: 'en', label: t('locale.en') },
        ]"
      />
      <SelectField
        v-if="timezone"
        v-model="timezone"
        :label="t('settings.fields.timezone')"
        :options="TIMEZONES.map((zone) => ({ value: zone, label: zone }))"
        :error="errorText(errors.timezone)"
      />
    </div>
    <AppButton type="submit" :loading="update.isPending.value" :disabled="!meta.dirty">
      {{ t('common.save') }}
    </AppButton>
  </form>
</template>
