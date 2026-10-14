<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import AppButton from '@/components/ui/AppButton.vue'
import FormDialog from '@/components/ui/FormDialog.vue'
import PageHeader from '@/components/ui/PageHeader.vue'
import ReorderButtons from '@/components/ui/ReorderButtons.vue'
import { useConfirm } from '@/composables/useConfirm'
import { useFormat } from '@/composables/useFormat'
import { moveId } from '@/lib/reorder'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'

import ServiceForm from '../components/ServiceForm.vue'
import {
  useCreateService,
  useDeleteService,
  useReorderServices,
  useServices,
  useUpdateService,
  type Service,
  type ServiceInput,
} from '../api'

const { t } = useI18n()
const format = useFormat()
const toast = useToast()
const { confirm } = useConfirm()
const auth = useAuthStore()

const services = useServices()
const create = useCreateService()
const update = useUpdateService()
const remove = useDeleteService()
const reorder = useReorderServices()

const isOwner = computed(() => auth.currentShop?.role === 'owner')
const dialogOpen = ref(false)
const editing = ref<Service | undefined>()

function openForm(service?: Service) {
  editing.value = service
  dialogOpen.value = true
}

async function save(values: ServiceInput, done: (error?: unknown) => void) {
  try {
    if (editing.value) await update.mutateAsync({ id: editing.value.id, body: values })
    else await create.mutateAsync(values)
    done()
    dialogOpen.value = false
    toast.show(t('common.saved'))
  } catch (error) {
    done(error)
  }
}

async function destroy(service: Service) {
  const confirmed = await confirm({
    title: t('services.deleteTitle', { name: service.name }),
    body: t('services.deleteBody'),
    confirmLabel: t('common.delete'),
    tone: 'danger',
  })
  if (!confirmed) return

  try {
    await remove.mutateAsync(service.id)
    toast.show(t('services.deleted'))
  } catch {
    toast.show(t('errors.generic'), 'error')
  }
}

async function move(index: number, direction: -1 | 1) {
  const list = services.data.value ?? []
  try {
    await reorder.mutateAsync(moveId(list, index, direction))
  } catch {
    toast.show(t('errors.generic'), 'error')
  }
}
</script>

<template>
  <div class="mx-auto max-w-5xl px-4 py-8 sm:px-8 md:py-12">
    <PageHeader :kicker="t('services.kicker')" :title="t('services.title')">
      <template v-if="isOwner" #actions>
        <AppButton @click="openForm()">+ {{ t('services.add') }}</AppButton>
      </template>
    </PageHeader>

    <p v-if="services.isPending.value" class="mt-8 font-mono text-sm text-ink-soft">
      {{ t('common.loading') }}
    </p>

    <div
      v-else-if="services.data.value?.length === 0"
      class="mt-8 border-2 border-dashed border-line px-6 py-12 text-center"
    >
      <p class="text-2xl font-extrabold uppercase font-condensed">{{ t('services.emptyTitle') }}</p>
      <p class="mt-2 text-ink-soft">{{ t('services.emptyBody') }}</p>
    </div>

    <ol v-else class="mt-6 divide-y divide-line border-b border-line">
      <li
        v-for="(service, index) in services.data.value"
        :key="service.id"
        class="grid grid-cols-[auto_1fr] items-center gap-x-4 gap-y-2 py-4 sm:grid-cols-[auto_1fr_auto_auto]"
        :class="{ 'opacity-60': !service.is_active }"
      >
        <ReorderButtons
          v-if="isOwner"
          :name="service.name"
          :first="index === 0"
          :last="index === (services.data.value?.length ?? 0) - 1"
          :disabled="reorder.isPending.value"
          @move="(direction) => move(index, direction)"
        />
        <span v-else class="w-7 font-mono text-sm text-pole tabular" aria-hidden="true">
          {{ String(index + 1).padStart(2, '0') }}
        </span>
        <div class="min-w-0">
          <p class="flex flex-wrap items-center gap-2">
            <span class="text-xl font-extrabold uppercase font-condensed">{{ service.name }}</span>
            <span
              v-if="!service.is_active"
              class="border border-ink px-1.5 font-mono text-[11px] tracking-wider uppercase"
            >
              {{ t('common.inactive') }}
            </span>
          </p>
          <p class="mt-0.5 font-mono text-xs text-ink-soft">
            {{ format.duration(service.duration_minutes) }}
            <template v-if="service.buffer_minutes > 0">
              + {{ t('services.bufferShort', { n: service.buffer_minutes }) }}
            </template>
            <template v-if="service.rebook_after_days">
              · {{ t('services.rebookShort', { n: service.rebook_after_days }) }}
            </template>
          </p>
        </div>
        <p class="col-start-2 font-mono text-lg tabular sm:col-start-auto sm:text-right">
          {{ format.price(service.price_ore) }}
        </p>
        <div v-if="isOwner" class="col-start-2 flex gap-2 sm:col-start-auto">
          <AppButton size="sm" variant="ghost" @click="openForm(service)">
            {{ t('common.edit') }}
          </AppButton>
          <AppButton size="sm" variant="ghost" @click="destroy(service)">
            {{ t('common.delete') }}
          </AppButton>
        </div>
      </li>
    </ol>

    <FormDialog
      v-model:open="dialogOpen"
      :title="editing ? t('services.editTitle') : t('services.addTitle')"
    >
      <ServiceForm :key="editing?.id ?? 'new'" :service="editing" @submit="save" />
    </FormDialog>
  </div>
</template>
