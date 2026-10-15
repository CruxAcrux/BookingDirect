<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useForm } from 'vee-validate'
import { z } from 'zod'

import AppButton from '@/components/ui/AppButton.vue'
import FormAlert from '@/components/ui/FormAlert.vue'
import TextField from '@/components/ui/TextField.vue'
import { useToast } from '@/composables/useToast'
import { emailField } from '@/lib/forms/schemas'
import { applyApiError } from '@/lib/forms/serverErrors'
import { toTypedSchema } from '@/lib/forms/toTypedSchema'
import { useErrorText } from '@/lib/forms/useErrorText'

import { useInviteBarber } from '../api'

const { barberId } = defineProps<{ barberId: number }>()

const { t } = useI18n()
const errorText = useErrorText()
const toast = useToast()
const invite = useInviteBarber(() => barberId)
const formError = ref<string | null>(null)

const { defineField, handleSubmit, errors, setErrors } = useForm({
  validationSchema: toTypedSchema(z.object({ email: emailField() })),
  initialValues: { email: '' },
})
const [email] = defineField('email')

const onSubmit = handleSubmit(async (values) => {
  formError.value = null
  try {
    await invite.mutateAsync(values)
    toast.show(t('team.invited', { email: values.email }))
  } catch (error) {
    formError.value = applyApiError(error, setErrors, ['email'], t('errors.generic'))
  }
})
</script>

<template>
  <form class="space-y-3" novalidate @submit="onSubmit">
    <FormAlert v-if="formError">{{ formError }}</FormAlert>
    <TextField
      v-model="email"
      type="email"
      :label="t('team.inviteEmail')"
      :error="errorText(errors.email)"
    />
    <AppButton type="submit" size="sm" :loading="invite.isPending.value">{{
      t('team.invite')
    }}</AppButton>
  </form>
</template>
