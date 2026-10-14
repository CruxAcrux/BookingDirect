<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

import AppButton from '@/components/ui/AppButton.vue'
import FormAlert from '@/components/ui/FormAlert.vue'
import WeekScheduleEditor from '@/components/ui/WeekScheduleEditor.vue'
import { useToast } from '@/composables/useToast'
import { useOpeningHours } from '@/features/settings/api'
import { ApiError } from '@/lib/apiError'
import { hasBlockingIssues, weeklyIssues, type WeeklyRange } from '@/lib/schedule/weekly'

import { useBarberSchedule, useUpdateBarberSchedule } from '../api'
import { useBarberContext } from '../composables/useBarberContext'

const { t } = useI18n()
const toast = useToast()
const { barberId, isOwner } = useBarberContext()

const opening = useOpeningHours()
const workingHours = useBarberSchedule(barberId, 'working-hours')
const breaks = useBarberSchedule(barberId, 'breaks')
const saveWorkingHours = useUpdateBarberSchedule(barberId, 'working-hours')
const saveBreaks = useUpdateBarberSchedule(barberId, 'breaks')

const hoursDraft = ref<WeeklyRange[]>([])
const breaksDraft = ref<WeeklyRange[]>([])
const formError = ref<string | null>(null)

const copy = (ranges: WeeklyRange[]) => ranges.map((range) => ({ ...range }))
watch(
  () => workingHours.data.value,
  (data) => data && (hoursDraft.value = copy(data)),
  { immediate: true },
)
watch(
  () => breaks.data.value,
  (data) => data && (breaksDraft.value = copy(data)),
  { immediate: true },
)

const blocked = computed(
  () =>
    hasBlockingIssues(weeklyIssues(hoursDraft.value)) ||
    hasBlockingIssues(weeklyIssues(breaksDraft.value)),
)

async function save() {
  formError.value = null
  try {
    await Promise.all([
      saveWorkingHours.mutateAsync(hoursDraft.value),
      saveBreaks.mutateAsync(breaksDraft.value),
    ])
    toast.show(t('common.saved'))
  } catch (error) {
    formError.value =
      error instanceof ApiError && error.status === 422 ? error.message : t('errors.generic')
  }
}
</script>

<template>
  <p
    v-if="workingHours.isPending.value || breaks.isPending.value"
    class="font-mono text-sm text-ink-soft"
  >
    {{ t('common.loading') }}
  </p>
  <form v-else class="space-y-10" novalidate @submit.prevent="save">
    <FormAlert v-if="formError">{{ formError }}</FormAlert>
    <section aria-labelledby="hours-title">
      <h2 id="hours-title" class="text-2xl font-extrabold uppercase font-condensed">
        {{ t('team.schedule.workingHours') }}
      </h2>
      <p class="mt-1 mb-4 max-w-2xl text-sm text-ink-soft">
        {{ t('team.schedule.workingHoursHelp') }}
      </p>
      <WeekScheduleEditor
        v-model="hoursDraft"
        :label="t('team.schedule.workingHours')"
        :reference="opening.data.value"
        :reference-warning="t('team.schedule.outsideOpening')"
        :readonly="!isOwner"
      />
    </section>
    <section aria-labelledby="breaks-title">
      <h2 id="breaks-title" class="text-2xl font-extrabold uppercase font-condensed">
        {{ t('team.schedule.breaks') }}
      </h2>
      <p class="mt-1 mb-4 max-w-2xl text-sm text-ink-soft">{{ t('team.schedule.breaksHelp') }}</p>
      <WeekScheduleEditor
        v-model="breaksDraft"
        :label="t('team.schedule.breaks')"
        :reference="hoursDraft"
        :reference-warning="t('team.schedule.breakOutsideHours')"
        :default-range="{ starts_at: '12:00', ends_at: '12:30' }"
        :readonly="!isOwner"
      />
    </section>
    <AppButton
      v-if="isOwner"
      type="submit"
      :loading="saveWorkingHours.isPending.value"
      :disabled="blocked"
    >
      {{ t('common.save') }}
    </AppButton>
  </form>
</template>
