<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

import AppButton from '@/components/ui/AppButton.vue'
import FormDialog from '@/components/ui/FormDialog.vue'
import { useConfirm } from '@/composables/useConfirm'
import { useFormat } from '@/composables/useFormat'
import { useToast } from '@/composables/useToast'
import SlotPicker from '@/features/booking/components/SlotPicker.vue'
import { ApiError } from '@/lib/apiError'

import { useBookingChanges, type PublicBooking, type Slot } from '@/features/booking/api'

const { booking, target, maxDaysAhead } = defineProps<{
  booking: PublicBooking
  target: { token: string } | { slug: string; id: number }
  maxDaysAhead: number
}>()

const { t } = useI18n()
const format = useFormat()
const toast = useToast()
const { confirm } = useConfirm()
const { cancel, reschedule } = useBookingChanges(() => target)

const moving = ref(false)
const date = ref<string | null>(booking.starts_at_local.slice(0, 10))
const keepBarber = ref(true)

function failed(error: unknown) {
  toast.show(
    error instanceof ApiError && error.status === 409 ? error.message : t('errors.generic'),
    'error',
  )
}

async function cancelBooking() {
  const confirmed = await confirm({
    title: t('manage.cancelTitle'),
    body: t('manage.cancelBody'),
    confirmLabel: t('manage.cancel'),
    tone: 'danger',
  })
  if (!confirmed) return

  try {
    await cancel.mutateAsync()
    toast.show(t('manage.cancelled'))
  } catch (error) {
    failed(error)
  }
}

async function move(slot: Slot) {
  try {
    await reschedule.mutateAsync({
      starts_at: slot.starts_at,
      barber_id: keepBarber.value ? booking.barber.id : null,
    })
    moving.value = false
    toast.show(t('manage.moved'))
  } catch (error) {
    failed(error)
  }
}
</script>

<template>
  <div>
    <div v-if="booking.can_change" class="flex flex-wrap gap-3">
      <AppButton @click="moving = true">{{ t('manage.move') }}</AppButton>
      <AppButton variant="ghost" :loading="cancel.isPending.value" @click="cancelBooking">
        {{ t('manage.cancel') }}
      </AppButton>
    </div>
    <p class="mt-3 text-sm text-ink-soft">
      <template v-if="booking.can_change">
        {{
          t('manage.deadline', {
            when: format.instant(booking.change_deadline, booking.shop.timezone, {
              weekday: 'long',
              day: 'numeric',
              month: 'long',
              hour: '2-digit',
              minute: '2-digit',
              hourCycle: 'h23',
            }),
          })
        }}
      </template>
      <template v-else-if="booking.status === 'confirmed'">
        {{ t('manage.tooLate', { phone: booking.shop.phone ?? '' }) }}
      </template>
    </p>

    <FormDialog v-model:open="moving" :title="t('manage.moveTitle')">
      <label class="flex items-center gap-2 text-sm">
        <input v-model="keepBarber" type="checkbox" class="size-4 accent-ink" />
        {{ t('manage.keepBarber', { name: booking.barber.display_name }) }}
      </label>
      <SlotPicker
        v-model:date="date"
        class="mt-4"
        :slug="booking.shop.slug"
        :timezone="booking.shop.timezone"
        :service-id="booking.service.id"
        :barber-id="keepBarber ? booking.barber.id : null"
        :max-days-ahead="maxDaysAhead"
        :selected="booking.starts_at"
        @select="move"
      />
    </FormDialog>
  </div>
</template>
