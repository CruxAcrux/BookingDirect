<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useForm } from 'vee-validate'
import { z } from 'zod'

import AppButton from '@/components/ui/AppButton.vue'
import FormAlert from '@/components/ui/FormAlert.vue'
import TextField from '@/components/ui/TextField.vue'
import { emailField, requiredString } from '@/lib/forms/schemas'
import { firstFieldErrors } from '@/lib/forms/serverErrors'
import { toTypedSchema } from '@/lib/forms/toTypedSchema'
import { useErrorText } from '@/lib/forms/useErrorText'
import { safeRedirect } from '@/router/guards'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const errorText = useErrorText()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const { defineField, handleSubmit, errors, setErrors, isSubmitting } = useForm({
  validationSchema: toTypedSchema(
    z.object({ email: emailField(), password: requiredString(), remember: z.boolean() }),
  ),
  initialValues: { email: '', password: '', remember: false },
})

const [email] = defineField('email')
const [password] = defineField('password')
const [remember] = defineField('remember')
const formError = ref<string | null>(null)

const onSubmit = handleSubmit(async (values) => {
  formError.value = null
  const { error, response } = await auth.login(values)

  if (response.ok) {
    await router.replace(safeRedirect(route.query.redirect) ?? { name: 'dashboard' })
  } else if (response.status === 422) {
    setErrors(firstFieldErrors(error))
  } else if (response.status === 429) {
    formError.value = t('auth.login.throttled')
  } else {
    formError.value = t('errors.generic')
  }
})
</script>

<template>
  <h1 class="text-4xl font-black uppercase font-condensed">{{ t('auth.login.title') }}</h1>
  <p class="mt-2 text-ink-soft">{{ t('auth.login.lead') }}</p>

  <FormAlert v-if="route.query.reset === '1'" tone="success" class="mt-6">
    {{ t('auth.reset.done') }}
  </FormAlert>
  <FormAlert v-if="formError" class="mt-6">{{ formError }}</FormAlert>

  <form class="mt-8 space-y-5" novalidate @submit="onSubmit">
    <TextField
      v-model="email"
      :label="t('auth.fields.email')"
      type="email"
      autocomplete="username"
      :error="errorText(errors.email)"
    />
    <TextField
      v-model="password"
      :label="t('auth.fields.password')"
      type="password"
      autocomplete="current-password"
      :error="errorText(errors.password)"
    />
    <div class="flex items-center justify-between gap-4">
      <label class="flex items-center gap-2 text-sm">
        <input v-model="remember" type="checkbox" class="size-4 accent-ink" />
        {{ t('auth.login.remember') }}
      </label>
      <RouterLink
        :to="{ name: 'forgot-password' }"
        class="text-sm underline decoration-pole decoration-2 underline-offset-4"
      >
        {{ t('auth.login.forgot') }}
      </RouterLink>
    </div>
    <AppButton type="submit" :loading="isSubmitting" class="w-full">
      {{ t('auth.login.submit') }}
    </AppButton>
  </form>
</template>
