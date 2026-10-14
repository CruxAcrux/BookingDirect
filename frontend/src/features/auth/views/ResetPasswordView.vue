<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useForm } from 'vee-validate'
import { z } from 'zod'

import AppButton from '@/components/ui/AppButton.vue'
import FormAlert from '@/components/ui/FormAlert.vue'
import TextField from '@/components/ui/TextField.vue'
import { api } from '@/lib/api'
import { firstFieldErrors } from '@/lib/forms/serverErrors'
import { toTypedSchema } from '@/lib/forms/toTypedSchema'
import { useErrorText } from '@/lib/forms/useErrorText'

const MIN_PASSWORD_LENGTH = 10

const { t } = useI18n()
const errorText = useErrorText()
const route = useRoute()
const router = useRouter()

const token = computed(() => (typeof route.query.token === 'string' ? route.query.token : ''))
const email = computed(() => (typeof route.query.email === 'string' ? route.query.email : ''))

const { defineField, handleSubmit, errors, setErrors, isSubmitting } = useForm({
  validationSchema: toTypedSchema(
    z
      .object({
        password: z.string().min(MIN_PASSWORD_LENGTH, { error: 'validation.passwordLength' }),
        password_confirmation: z.string(),
      })
      .refine((values) => values.password === values.password_confirmation, {
        error: 'validation.passwordMismatch',
        path: ['password_confirmation'],
      }),
  ),
  initialValues: { password: '', password_confirmation: '' },
})

const [password] = defineField('password')
const [passwordConfirmation] = defineField('password_confirmation')
const formError = ref<string | null>(null)

const onSubmit = handleSubmit(async (values) => {
  formError.value = null
  const { error, response } = await api.POST('/auth/reset-password', {
    body: { ...values, token: token.value, email: email.value },
  })

  if (response.ok) {
    await router.replace({ name: 'login', query: { reset: '1' } })
    return
  }

  const fieldErrors = firstFieldErrors(error)
  // An expired or reused token is reported on `email`, which has no input here.
  formError.value = fieldErrors.email ?? (response.status === 422 ? null : t('errors.generic'))
  setErrors(fieldErrors)
})
</script>

<template>
  <h1 class="text-4xl font-black uppercase font-condensed">{{ t('auth.reset.title') }}</h1>

  <FormAlert v-if="!token || !email" class="mt-6">{{ t('auth.reset.invalidLink') }}</FormAlert>
  <template v-else>
    <p class="mt-2 text-ink-soft">{{ t('auth.reset.lead', { email }) }}</p>
    <FormAlert v-if="formError" class="mt-6">{{ formError }}</FormAlert>
    <form class="mt-8 space-y-5" novalidate @submit="onSubmit">
      <TextField
        v-model="password"
        :label="t('auth.fields.newPassword')"
        type="password"
        autocomplete="new-password"
        :hint="t('auth.reset.hint', { min: MIN_PASSWORD_LENGTH })"
        :error="errorText(errors.password)"
      />
      <TextField
        v-model="passwordConfirmation"
        :label="t('auth.fields.confirmPassword')"
        type="password"
        autocomplete="new-password"
        :error="errorText(errors.password_confirmation)"
      />
      <AppButton type="submit" :loading="isSubmitting" class="w-full">
        {{ t('auth.reset.submit') }}
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
