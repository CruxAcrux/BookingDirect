<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

import AppButton from '@/components/ui/AppButton.vue'
import FormAlert from '@/components/ui/FormAlert.vue'
import { useFormat } from '@/composables/useFormat'
import { useToast } from '@/composables/useToast'
import { useServices } from '@/features/services/api'
import { kronorField, oreToKronorInput } from '@/lib/forms/schemas'

import { useSyncBarberServices } from '../api'
import { useBarberContext } from '../composables/useBarberContext'

interface Row {
  offered: boolean
  price: string
  duration: string
}

const { t } = useI18n()
const format = useFormat()
const toast = useToast()
const { barberId, barber, isOwner } = useBarberContext()
const services = useServices()
const sync = useSyncBarberServices(barberId)

const rows = ref<Record<number, Row>>({})
const formError = ref<string | null>(null)

watch(
  [() => services.data.value, () => barber.data.value],
  ([allServices, current]) => {
    if (!allServices || !current) return
    const offered = new Map((current.services ?? []).map((item) => [item.service_id, item]))
    rows.value = Object.fromEntries(
      allServices.map((service) => {
        const item = offered.get(service.id)
        return [
          service.id,
          {
            offered: item !== undefined,
            price: item?.price_ore != null ? oreToKronorInput(item.price_ore) : '',
            duration: item?.duration_minutes != null ? String(item.duration_minutes) : '',
          },
        ]
      }),
    )
  },
  { immediate: true },
)

const priceSchema = kronorField()

const invalid = computed(() =>
  Object.entries(rows.value).filter(([, row]) => {
    if (!row.offered) return false
    const badPrice = row.price !== '' && !priceSchema.safeParse(row.price).success
    const minutes = Number(row.duration)
    const badDuration =
      row.duration !== '' &&
      (!/^\d+$/.test(row.duration) || minutes < 5 || minutes > 480 || minutes % 5 !== 0)
    return badPrice || badDuration
  }),
)

async function save() {
  formError.value = null
  if (invalid.value.length > 0) {
    formError.value = t('team.services.invalid')
    return
  }

  const payload = Object.entries(rows.value)
    .filter(([, row]) => row.offered)
    .map(([id, row]) => ({
      id: Number(id),
      price_ore: row.price === '' ? null : priceSchema.parse(row.price),
      duration_minutes: row.duration === '' ? null : Number(row.duration),
    }))

  try {
    await sync.mutateAsync(payload)
    toast.show(t('common.saved'))
  } catch {
    formError.value = t('errors.generic')
  }
}
</script>

<template>
  <p v-if="services.isPending.value" class="font-mono text-sm text-ink-soft">
    {{ t('common.loading') }}
  </p>
  <form v-else class="space-y-6" novalidate @submit.prevent="save">
    <p class="max-w-2xl text-ink-soft">
      {{ isOwner ? t('team.services.help') : t('team.services.readonlyHelp') }}
    </p>
    <FormAlert v-if="formError">{{ formError }}</FormAlert>

    <table class="w-full text-left">
      <thead class="border-b-2 border-ink font-mono text-xs tracking-widest uppercase">
        <tr>
          <th scope="col" class="py-2 pr-3">{{ t('team.services.service') }}</th>
          <th scope="col" class="hidden py-2 pr-3 sm:table-cell">
            {{ t('team.services.default') }}
          </th>
          <th scope="col" class="py-2 pr-3">{{ t('team.services.price') }}</th>
          <th scope="col" class="py-2">{{ t('team.services.duration') }}</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-line">
        <tr v-for="service in services.data.value" :key="service.id" class="align-middle">
          <td class="py-3 pr-3">
            <label class="flex items-center gap-3">
              <input
                v-if="rows[service.id]"
                v-model="rows[service.id]!.offered"
                type="checkbox"
                :disabled="!isOwner"
                class="size-5 accent-ink"
              />
              <span class="font-medium">{{ service.name }}</span>
            </label>
          </td>
          <td class="hidden py-3 pr-3 font-mono text-sm text-ink-soft tabular sm:table-cell">
            {{ format.price(service.price_ore) }} · {{ format.duration(service.duration_minutes) }}
          </td>
          <td class="py-3 pr-3">
            <input
              v-if="rows[service.id]"
              v-model="rows[service.id]!.price"
              inputmode="decimal"
              :disabled="!isOwner || !rows[service.id]!.offered"
              :placeholder="oreToKronorInput(service.price_ore)"
              :aria-label="t('team.services.priceFor', { name: service.name })"
              class="w-24 border-2 border-ink bg-paper px-2 py-1 font-mono tabular disabled:border-line disabled:text-ink-soft"
            />
          </td>
          <td class="py-3">
            <input
              v-if="rows[service.id]"
              v-model="rows[service.id]!.duration"
              inputmode="numeric"
              :disabled="!isOwner || !rows[service.id]!.offered"
              :placeholder="String(service.duration_minutes)"
              :aria-label="t('team.services.durationFor', { name: service.name })"
              class="w-20 border-2 border-ink bg-paper px-2 py-1 font-mono tabular disabled:border-line disabled:text-ink-soft"
            />
          </td>
        </tr>
      </tbody>
    </table>

    <AppButton v-if="isOwner" type="submit" :loading="sync.isPending.value">{{
      t('common.save')
    }}</AppButton>
  </form>
</template>
