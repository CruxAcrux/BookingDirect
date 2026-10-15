<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useForm } from 'vee-validate'
import { z } from 'zod'

import AppButton from '@/components/ui/AppButton.vue'
import FormAlert from '@/components/ui/FormAlert.vue'
import FormDialog from '@/components/ui/FormDialog.vue'
import PageHeader from '@/components/ui/PageHeader.vue'
import ReorderButtons from '@/components/ui/ReorderButtons.vue'
import TextareaField from '@/components/ui/TextareaField.vue'
import TextField from '@/components/ui/TextField.vue'
import { useToast } from '@/composables/useToast'
import { optionalString, requiredString } from '@/lib/forms/schemas'
import { applyApiError } from '@/lib/forms/serverErrors'
import { toTypedSchema } from '@/lib/forms/toTypedSchema'
import { useErrorText } from '@/lib/forms/useErrorText'
import { moveId } from '@/lib/reorder'
import { useAuthStore } from '@/stores/auth'

import BarberAvatar from '../components/BarberAvatar.vue'
import { useBarbers, useCreateBarber, useReorderBarbers } from '../api'

const { t } = useI18n()
const errorText = useErrorText()
const router = useRouter()
const toast = useToast()
const auth = useAuthStore()

const barbers = useBarbers()
const create = useCreateBarber()
const reorder = useReorderBarbers()

const isOwner = computed(() => auth.currentShop?.role === 'owner')
const dialogOpen = ref(false)
const formError = ref<string | null>(null)

const { defineField, handleSubmit, errors, setErrors, resetForm } = useForm({
  validationSchema: toTypedSchema(
    z.object({ display_name: requiredString(), bio: optionalString() }),
  ),
  initialValues: { display_name: '', bio: '' },
})
const [displayName] = defineField('display_name')
const [bio] = defineField('bio')

function openForm() {
  resetForm()
  formError.value = null
  dialogOpen.value = true
}

const onSubmit = handleSubmit(async (values) => {
  formError.value = null
  try {
    const barber = await create.mutateAsync(values)
    dialogOpen.value = false
    toast.show(t('team.created', { name: barber.display_name }))
    await router.push({ name: 'barber', params: { barberId: barber.id } })
  } catch (error) {
    formError.value = applyApiError(error, setErrors, ['display_name', 'bio'], t('errors.generic'))
  }
})

async function move(index: number, direction: -1 | 1) {
  try {
    await reorder.mutateAsync(moveId(barbers.data.value ?? [], index, direction))
  } catch {
    toast.show(t('errors.generic'), 'error')
  }
}
</script>

<template>
  <div class="mx-auto max-w-5xl px-4 py-8 sm:px-8 md:py-12">
    <PageHeader :kicker="t('team.kicker')" :title="t('team.title')">
      <template v-if="isOwner" #actions>
        <AppButton @click="openForm">+ {{ t('team.add') }}</AppButton>
      </template>
    </PageHeader>

    <p v-if="barbers.isPending.value" class="mt-8 font-mono text-sm text-ink-soft">
      {{ t('common.loading') }}
    </p>

    <div
      v-else-if="barbers.data.value?.length === 0"
      class="mt-8 border-2 border-dashed border-line px-6 py-12 text-center"
    >
      <p class="text-2xl font-extrabold uppercase font-condensed">{{ t('team.emptyTitle') }}</p>
      <p class="mt-2 text-ink-soft">{{ t('team.emptyBody') }}</p>
    </div>

    <ol v-else class="mt-6 grid gap-4 sm:grid-cols-2">
      <li
        v-for="(barber, index) in barbers.data.value"
        :key="barber.id"
        class="flex items-center gap-4 border-2 border-ink bg-paper p-4"
        :class="{ 'border-dashed opacity-70': !barber.is_active }"
      >
        <BarberAvatar :barber="barber" class="size-16" />
        <div class="min-w-0 flex-1">
          <RouterLink
            :to="{ name: 'barber', params: { barberId: barber.id } }"
            class="text-2xl font-extrabold uppercase font-condensed hover:text-pole"
          >
            {{ barber.display_name }}
          </RouterLink>
          <p class="mt-0.5 truncate font-mono text-xs text-ink-soft">
            {{ barber.user ? barber.user.email : t('team.noLogin') }}
          </p>
          <p class="mt-1 flex flex-wrap gap-1.5">
            <span
              v-if="barber.is_me"
              class="bg-ink px-1.5 font-mono text-[11px] text-paper uppercase"
            >
              {{ t('team.you') }}
            </span>
            <span
              v-if="!barber.is_active"
              class="border border-ink px-1.5 font-mono text-[11px] tracking-wider uppercase"
            >
              {{ t('common.inactive') }}
            </span>
          </p>
        </div>
        <ReorderButtons
          v-if="isOwner"
          :name="barber.display_name"
          :first="index === 0"
          :last="index === (barbers.data.value?.length ?? 0) - 1"
          :disabled="reorder.isPending.value"
          @move="(direction) => move(index, direction)"
        />
      </li>
    </ol>

    <FormDialog v-model:open="dialogOpen" :title="t('team.addTitle')">
      <form class="space-y-5" novalidate @submit="onSubmit">
        <FormAlert v-if="formError">{{ formError }}</FormAlert>
        <TextField
          v-model="displayName"
          :label="t('team.fields.displayName')"
          :error="errorText(errors.display_name)"
        />
        <TextareaField v-model="bio" :label="t('team.fields.bio')" :error="errorText(errors.bio)" />
        <p class="text-sm text-ink-soft">{{ t('team.addHint') }}</p>
        <div class="flex justify-end">
          <AppButton type="submit" :loading="create.isPending.value">{{ t('team.add') }}</AppButton>
        </div>
      </form>
    </FormDialog>
  </div>
</template>
