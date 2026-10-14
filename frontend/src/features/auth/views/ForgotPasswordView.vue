<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useForm } from 'vee-validate'
import { z } from 'zod'

import AppButton from '@/components/ui/AppButton.vue'
import FormAlert from '@/components/ui/FormAlert.vue'
import TextField from '@/components/ui/TextField.vue'
import { api } from '@/lib/api'
import { emailField } from '@/lib/forms/schemas'
import { firstFieldErrors } from '@/lib/forms/serverErrors'
import { toTypedSchema } from '@/lib/forms/toTypedSchema'
import { useErrorText } from '@/lib/forms/useErrorText'

const { t } = useI18n()
const errorText = useErrorText()

const { defineField, handleSubmit, errors, setErrors, isSubmitting } = useForm({
  validationSchema: toTypedSchema(z.object({ email: emailField() })),
  initialValues: { email: '' },
})

const [email] = defineField('email')
const sent = ref(false)
const formError = ref<string | null>(null)

const onSubmit = handleSubmit(async (values) => {
  formError.value = null
  const { error, response } = await api.POST('/auth/forgot-password', { body: values })

  if (response.ok) {
    sent.value = true
  } else if (response.status === 422) {
    setErrors(firstFieldErrors(error))
  } else {
    formError.value = t(response.status === 429 ? 'errors.throttled' : 'errors.generic')
  }
})
</script>

<template>
  <h1 class="text-4xl font-black uppercase font-condensed">{{ t('auth.forgot.title') }}</h1>

  <template v-if="sent">
    <FormAlert tone="success" class="mt-6">{{ t('auth.forgot.sent') }}</FormAlert>
  </template>
  <template v-else>
    <p class="mt-2 text-ink-soft">{{ t('auth.forgot.lead') }}</p>
    <FormAlert v-if="formError" class="mt-6">{{ formError }}</FormAlert>
    <form class="mt-8 space-y-5" novalidate @submit="onSubmit">
      <TextField
        v-model="email"
        :label="t('auth.fields.email')"
        type="email"
        autocomplete="email"
        :error="errorText(errors.email)"
      />
      <AppButton type="submit" :loading="isSubmitting" class="w-full">
        {{ t('auth.forgot.submit') }}
      </AppButton>
    </form>
  </template>

  <RouterLink
    :to="{ name: 'login' }"
    class="mt-8 self-start text-sm underline decoration-pole decoration-2 underline-offset-4"
  >
    {{ t('auth.backToLogin') }}
  </RouterLink>
</template>
