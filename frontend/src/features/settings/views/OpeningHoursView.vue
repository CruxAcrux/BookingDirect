<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

import AppButton from '@/components/ui/AppButton.vue'
import FormAlert from '@/components/ui/FormAlert.vue'
import WeekScheduleEditor from '@/components/ui/WeekScheduleEditor.vue'
import { useToast } from '@/composables/useToast'
import { ApiError } from '@/lib/apiError'
import { hasBlockingIssues, weeklyIssues, type WeeklyRange } from '@/lib/schedule/weekly'

import { useOpeningHours, useUpdateOpeningHours } from '../api'

const { t } = useI18n()
const toast = useToast()
const hours = useOpeningHours()
const update = useUpdateOpeningHours()

const draft = ref<WeeklyRange[]>([])
const formError = ref<string | null>(null)

watch(
  () => hours.data.value,
  (data) => {
    if (data) draft.value = data.map((range) => ({ ...range }))
  },
  { immediate: true },
)

const blocked = computed(() => hasBlockingIssues(weeklyIssues(draft.value)))

async function save() {
  formError.value = null
  try {
    await update.mutateAsync(draft.value)
    toast.show(t('common.saved'))
  } catch (error) {
    formError.value =
      error instanceof ApiError && error.status === 422 ? error.message : t('errors.generic')
  }
}
</script>

<template>
  <p v-if="hours.isPending.value" class="font-mono text-sm text-ink-soft">
    {{ t('common.loading') }}
  </p>
  <form v-else class="space-y-6" novalidate @submit.prevent="save">
    <p class="max-w-xl text-ink-soft">{{ t('settings.hoursHelp') }}</p>
    <FormAlert v-if="formError">{{ formError }}</FormAlert>
    <WeekScheduleEditor
      v-model="draft"
      :label="t('settings.tabs.hours')"
      :default-range="{ starts_at: '10:00', ends_at: '19:00' }"
    />
    <AppButton type="submit" :loading="update.isPending.value" :disabled="blocked">
      {{ t('common.save') }}
    </AppButton>
  </form>
</template>
