<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

import AppButton from '@/components/ui/AppButton.vue'
import FormAlert from '@/components/ui/FormAlert.vue'
import FormDialog from '@/components/ui/FormDialog.vue'
import SelectField from '@/components/ui/SelectField.vue'
import TextareaField from '@/components/ui/TextareaField.vue'
import TextField from '@/components/ui/TextField.vue'
import { useFormat } from '@/composables/useFormat'
import { useToast } from '@/composables/useToast'
import { ApiError } from '@/lib/apiError'

import { useBookingActions, useMoveBooking, type CalendarBarber, type StaffBooking } from '../api'
import { needsOutcome } from '../lib/columns'
import { clockOf, localToUtc, minutesOf } from '../lib/grid'

const { booking, barbers, timezone, canEdit } = defineProps<{
  booking: StaffBooking
  barbers: CalendarBarber[]
  timezone: string
  canEdit: boolean
}>()

const open = defineModel<boolean>('open', { required: true })

const { t } = useI18n()
const format = useFormat()
const toast = useToast()
const { saveNote, cancel, outcome } = useBookingActions()
const move = useMoveBooking()

const note = ref(booking.staff_note ?? '')
const cancelling = ref(false)
const cancelMessage = ref('')
const moving = ref(false)
const moveDate = ref(booking.starts_at_local.slice(0, 10))
const moveTime = ref(booking.starts_at_local.slice(11, 16))
const moveBarber = ref(booking.barber_id)
const error = ref<string | null>(null)

watch(
  () => booking.id,
  () => {
    note.value = booking.staff_note ?? ''
    cancelling.value = false
    moving.value = false
    moveDate.value = booking.starts_at_local.slice(0, 10)
    moveTime.value = booking.starts_at_local.slice(11, 16)
    moveBarber.value = booking.barber_id
    error.value = null
  },
)

const started = computed(() => Date.parse(booking.starts_at) <= Date.now())
const active = computed(() => booking.status === 'confirmed' || booking.status === 'pending')
const barberName = computed(
  () => barbers.find((barber) => barber.id === booking.barber_id)?.display_name ?? '',
)

async function run(action: () => Promise<unknown>, done: string) {
  error.value = null
  try {
    await action()
    toast.show(done)
    return true
  } catch (failure) {
    error.value =
      failure instanceof ApiError && [409, 422].includes(failure.status)
        ? failure.message
        : t('errors.generic')
    return false
  }
}

async function confirmCancel() {
  const message = cancelMessage.value.trim()
  if (
    await run(
      () => cancel.mutateAsync({ id: booking.id, message: message === '' ? null : message }),
      t('calendar.cancelled'),
    )
  ) {
    cancelling.value = false
  }
}

async function confirmMove() {
  const minutes = minutesOf(moveTime.value)
  const ok = await run(
    () =>
      move.mutateAsync({
        booking,
        startsAt: localToUtc(moveDate.value, minutes, timezone),
        barberId: moveBarber.value,
        preview: {
          startsAtLocal: `${moveDate.value}T${moveTime.value}`,
          endsAtLocal: `${moveDate.value}T${clockOf(minutes + booking.duration_minutes)}`,
        },
      }),
    t('calendar.moved'),
  )
  if (ok) moving.value = false
}
</script>

<template>
  <FormDialog v-model:open="open" :title="booking.customer.name ?? booking.customer.phone">
    <div class="space-y-6">
      <div class="flex flex-wrap items-center gap-2">
        <span
          class="border-2 border-ink px-1.5 py-0.5 font-mono text-[11px] tracking-wider uppercase"
        >
          {{ t(`booking.status.${booking.status}`) }}
        </span>
        <span class="font-mono text-[11px] tracking-wider text-ink-soft uppercase">{{
          t(`calendar.source.${booking.source}`)
        }}</span>
        <span
          v-if="needsOutcome(booking)"
          class="bg-pole px-1.5 py-0.5 font-mono text-[11px] text-paper uppercase"
        >
          {{ t('calendar.needsOutcome') }}
        </span>
      </div>

      <dl class="grid grid-cols-2 gap-4">
        <div class="col-span-2">
          <dt class="font-mono text-[11px] tracking-widest text-ink-soft uppercase">
            {{ t('home.ticket.time') }}
          </dt>
          <dd class="font-semibold first-letter:uppercase">
            {{
              format.localDateTime(booking.starts_at_local, {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
              })
            }}, {{ booking.starts_at_local.slice(11, 16) }}–{{
              booking.ends_at_local.slice(11, 16)
            }}
          </dd>
        </div>
        <div>
          <dt class="font-mono text-[11px] tracking-widest text-ink-soft uppercase">
            {{ t('home.ticket.service') }}
          </dt>
          <dd>{{ booking.service.name }}</dd>
        </div>
        <div>
          <dt class="font-mono text-[11px] tracking-widest text-ink-soft uppercase">
            {{ t('home.ticket.barber') }}
          </dt>
          <dd>{{ barberName }}</dd>
        </div>
        <div>
          <dt class="font-mono text-[11px] tracking-widest text-ink-soft uppercase">
            {{ t('calendar.fields.phone') }}
          </dt>
          <dd>
            <a
              :href="`tel:${booking.customer.phone}`"
              class="font-mono tabular underline underline-offset-4"
              >{{ booking.customer.phone }}</a
            >
          </dd>
        </div>
        <div>
          <dt class="font-mono text-[11px] tracking-widest text-ink-soft uppercase">
            {{ t('calendar.price') }}
          </dt>
          <dd class="font-mono tabular">{{ format.price(booking.price_ore) }}</dd>
        </div>
        <div v-if="booking.customer.email" class="col-span-2">
          <dt class="font-mono text-[11px] tracking-widest text-ink-soft uppercase">
            {{ t('calendar.fields.email') }}
          </dt>
          <dd>
            <a :href="`mailto:${booking.customer.email}`" class="underline underline-offset-4">{{
              booking.customer.email
            }}</a>
          </dd>
        </div>
        <div
          v-if="booking.customer_note"
          class="col-span-2 border-l-4 border-ink bg-paper-deep px-3 py-2"
        >
          <dt class="font-mono text-[11px] tracking-widest text-ink-soft uppercase">
            {{ t('calendar.customerNote') }}
          </dt>
          <dd>{{ booking.customer_note }}</dd>
        </div>
        <div v-if="booking.cancellation_message" class="col-span-2">
          <dt class="font-mono text-[11px] tracking-widest text-ink-soft uppercase">
            {{ t('calendar.cancellationMessage') }}
          </dt>
          <dd>{{ booking.cancellation_message }}</dd>
        </div>
      </dl>

      <FormAlert v-if="error">{{ error }}</FormAlert>

      <template v-if="canEdit">
        <div
          v-if="started && booking.status !== 'cancelled' && booking.status !== 'pending'"
          class="flex flex-wrap gap-2"
        >
          <AppButton
            size="sm"
            :variant="booking.status === 'completed' ? 'primary' : 'ghost'"
            :loading="outcome.isPending.value"
            @click="
              run(
                () => outcome.mutateAsync({ id: booking.id, status: 'completed' }),
                t('calendar.marked'),
              )
            "
          >
            {{ t('calendar.markCompleted') }}
          </AppButton>
          <AppButton
            size="sm"
            :variant="booking.status === 'no_show' ? 'danger' : 'ghost'"
            :loading="outcome.isPending.value"
            @click="
              run(
                () => outcome.mutateAsync({ id: booking.id, status: 'no_show' }),
                t('calendar.marked'),
              )
            "
          >
            {{ t('calendar.markNoShow') }}
          </AppButton>
        </div>

        <div v-if="active && !started" class="flex flex-wrap gap-2">
          <AppButton size="sm" variant="ghost" @click="moving = !moving">{{
            t('calendar.move')
          }}</AppButton>
          <AppButton size="sm" variant="ghost" @click="cancelling = !cancelling">{{
            t('calendar.cancel')
          }}</AppButton>
        </div>

        <form
          v-if="moving"
          class="space-y-3 border-2 border-dashed border-ink p-3"
          @submit.prevent="confirmMove"
        >
          <div class="grid grid-cols-2 gap-3">
            <TextField v-model="moveDate" type="date" :label="t('calendar.fields.date')" />
            <TextField
              v-model="moveTime"
              type="time"
              step="300"
              :label="t('calendar.fields.time')"
            />
          </div>
          <SelectField
            v-model="moveBarber"
            :label="t('calendar.fields.barber')"
            :options="barbers.map((barber) => ({ value: barber.id, label: barber.display_name }))"
          />
          <AppButton type="submit" size="sm" :loading="move.isPending.value">{{
            t('calendar.confirmMove')
          }}</AppButton>
        </form>

        <form
          v-if="cancelling"
          class="space-y-3 border-2 border-dashed border-pole p-3"
          @submit.prevent="confirmCancel"
        >
          <TextareaField v-model="cancelMessage" :rows="2" :label="t('calendar.cancelMessage')" />
          <AppButton type="submit" size="sm" variant="danger" :loading="cancel.isPending.value">
            {{ t('calendar.confirmCancel') }}
          </AppButton>
        </form>

        <form
          class="space-y-2"
          @submit.prevent="
            run(
              () => saveNote.mutateAsync({ id: booking.id, note: note.trim() || null }),
              t('common.saved'),
            )
          "
        >
          <TextareaField v-model="note" :rows="2" :label="t('calendar.fields.staffNote')" />
          <AppButton type="submit" size="sm" variant="ghost" :loading="saveNote.isPending.value">{{
            t('common.save')
          }}</AppButton>
        </form>
      </template>
    </div>
  </FormDialog>
</template>
