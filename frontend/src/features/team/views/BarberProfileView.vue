<script setup lang="ts">
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useForm } from 'vee-validate'
import { z } from 'zod'

import AppButton from '@/components/ui/AppButton.vue'
import FormAlert from '@/components/ui/FormAlert.vue'
import ImageUpload from '@/components/ui/ImageUpload.vue'
import TextareaField from '@/components/ui/TextareaField.vue'
import TextField from '@/components/ui/TextField.vue'
import ToggleField from '@/components/ui/ToggleField.vue'
import { useConfirm } from '@/composables/useConfirm'
import { useToast } from '@/composables/useToast'
import { ApiError } from '@/lib/apiError'
import { optionalString, requiredString } from '@/lib/forms/schemas'
import { applyApiError } from '@/lib/forms/serverErrors'
import { toTypedSchema } from '@/lib/forms/toTypedSchema'
import { useErrorText } from '@/lib/forms/useErrorText'

import InviteForm from '../components/InviteForm.vue'
import { useBarberAvatar, useDeleteBarber, useUpdateBarber, type Barber } from '../api'
import { useBarberContext } from '../composables/useBarberContext'

const { t } = useI18n()
const errorText = useErrorText()
const toast = useToast()
const router = useRouter()
const { confirm } = useConfirm()
const { barberId, barber, isOwner, canEditOwn } = useBarberContext()

const update = useUpdateBarber(barberId)
const avatar = useBarberAvatar(barberId)
const remove = useDeleteBarber()
const formError = ref<string | null>(null)

const { defineField, handleSubmit, errors, setErrors, resetForm, meta } = useForm({
  validationSchema: toTypedSchema(
    z.object({ display_name: requiredString(), bio: optionalString(), is_active: z.boolean() }),
  ),
})
const [displayName] = defineField('display_name')
const [bio] = defineField('bio')
const [active] = defineField('is_active')

function fill(data: Barber) {
  resetForm({
    values: { display_name: data.display_name, bio: data.bio ?? '', is_active: data.is_active },
  })
}

watch(
  () => barber.data.value,
  (data) => {
    if (data) fill(data)
  },
  { immediate: true },
)

const onSubmit = handleSubmit(async (values) => {
  formError.value = null
  // Barbers may only change their own name and bio.
  const body = isOwner.value ? values : { display_name: values.display_name, bio: values.bio }
  try {
    fill(await update.mutateAsync(body))
    toast.show(t('common.saved'))
  } catch (error) {
    formError.value = applyApiError(
      error,
      setErrors,
      ['display_name', 'bio', 'is_active'],
      t('errors.generic'),
    )
  }
})

async function runAvatar(action: () => Promise<unknown>) {
  try {
    await action()
  } catch {
    // Shown by the upload component.
  }
}

function avatarError(error: unknown): string | null {
  if (!error) return null
  return error instanceof ApiError && error.status === 422 ? error.message : t('errors.generic')
}

async function destroy() {
  const name = barber.data.value?.display_name ?? ''
  const confirmed = await confirm({
    title: t('team.deleteTitle', { name }),
    body: t('team.deleteBody'),
    confirmLabel: t('common.delete'),
    tone: 'danger',
  })
  if (!confirmed) return

  try {
    await remove.mutateAsync(barberId.value)
    toast.show(t('team.deleted', { name }))
    await router.replace({ name: 'team' })
  } catch {
    toast.show(t('errors.generic'), 'error')
  }
}
</script>

<template>
  <div v-if="barber.data.value" class="grid gap-10 lg:grid-cols-[1fr_18rem]">
    <form class="space-y-5" novalidate @submit="onSubmit">
      <FormAlert v-if="formError">{{ formError }}</FormAlert>
      <TextField
        v-model="displayName"
        :label="t('team.fields.displayName')"
        :readonly="!canEditOwn"
        :error="errorText(errors.display_name)"
      />
      <TextareaField
        v-model="bio"
        :label="t('team.fields.bio')"
        :readonly="!canEditOwn"
        :error="errorText(errors.bio)"
      />
      <ToggleField
        v-if="isOwner"
        v-model="active"
        :label="t('team.fields.active')"
        :description="t('team.activeHint')"
      />
      <AppButton
        v-if="canEditOwn"
        type="submit"
        :loading="update.isPending.value"
        :disabled="!meta.dirty"
      >
        {{ t('common.save') }}
      </AppButton>
    </form>

    <aside class="space-y-8">
      <ImageUpload
        :label="t('team.photo')"
        :image-url="barber.data.value.avatar_url"
        :readonly="!canEditOwn"
        :busy="avatar.upload.isPending.value || avatar.remove.isPending.value"
        :error="avatarError(avatar.upload.error.value)"
        @upload="(file) => runAvatar(() => avatar.upload.mutateAsync(file))"
        @remove="runAvatar(() => avatar.remove.mutateAsync())"
      />

      <section class="border-t-2 border-ink pt-5" aria-labelledby="login-title">
        <h2 id="login-title" class="font-mono text-xs tracking-widest uppercase">
          {{ t('team.login') }}
        </h2>
        <p v-if="barber.data.value.user" class="mt-2">
          <span class="block font-medium">{{ barber.data.value.user.name }}</span>
          <span class="font-mono text-sm text-ink-soft">{{ barber.data.value.user.email }}</span>
        </p>
        <template v-else>
          <p class="mt-2 text-sm text-ink-soft">{{ t('team.noLoginHint') }}</p>
          <InviteForm v-if="isOwner" :barber-id="barberId" class="mt-4" />
        </template>
      </section>

      <section v-if="isOwner" class="border-t-2 border-ink pt-5" aria-labelledby="danger-title">
        <h2 id="danger-title" class="font-mono text-xs tracking-widest uppercase">
          {{ t('team.remove') }}
        </h2>
        <p class="mt-2 text-sm text-ink-soft">{{ t('team.deleteBody') }}</p>
        <AppButton
          variant="danger"
          size="sm"
          class="mt-3"
          :loading="remove.isPending.value"
          @click="destroy"
        >
          {{ t('team.removeButton') }}
        </AppButton>
      </section>
    </aside>
  </div>
</template>
