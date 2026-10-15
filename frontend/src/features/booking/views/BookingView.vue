<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useForm } from 'vee-validate'
import { z } from 'zod'

import AppButton from '@/components/ui/AppButton.vue'
import FormAlert from '@/components/ui/FormAlert.vue'
import TextareaField from '@/components/ui/TextareaField.vue'
import TextField from '@/components/ui/TextField.vue'
import { useFormat } from '@/composables/useFormat'
import { useToast } from '@/composables/useToast'
import { ApiError } from '@/lib/apiError'
import { optionalEmail, optionalString } from '@/lib/forms/schemas'
import { applyApiError } from '@/lib/forms/serverErrors'
import { toTypedSchema } from '@/lib/forms/toTypedSchema'
import { useErrorText } from '@/lib/forms/useErrorText'

import OtpFields from '../components/OtpFields.vue'
import SlotPicker from '../components/SlotPicker.vue'
import StepNav from '../components/StepNav.vue'
import { useBookingDraft, type BookingStep } from '../composables/useBookingDraft'
import { useOtpFlow } from '../composables/useOtpFlow'
import { useCreateBooking, useCustomer, useNextAvailable, type PublicShop, type Slot } from '../api'

const { shop } = defineProps<{ shop: PublicShop }>()

const { t } = useI18n()
const format = useFormat()
const errorText = useErrorText()
const toast = useToast()
const router = useRouter()

const { draft, step, update, goTo } = useBookingDraft()
const customer = useCustomer(() => shop.slug)
const otp = useOtpFlow(() => shop.slug)
const { phone: otpPhone, code: otpCode } = otp
const create = useCreateBooking(() => shop.slug)

const service = computed(
  () => shop.services.find((item) => item.id === draft.value.serviceId) ?? null,
)
const barbersForService = computed(() =>
  shop.barbers.filter((barber) =>
    barber.services.some((item) => item.service_id === draft.value.serviceId),
  ),
)
const barber = computed(() =>
  typeof draft.value.barber === 'number'
    ? (shop.barbers.find((item) => item.id === draft.value.barber) ?? null)
    : null,
)
const barberNames = computed(() =>
  Object.fromEntries(shop.barbers.map((item) => [item.id, item.display_name])),
)

// A link may name a barber who doesn't do the chosen service: ask again instead.
watch(
  [service, () => draft.value.barber],
  ([chosen, choice]) => {
    if (draft.value.serviceId !== null && chosen === null)
      void update({ serviceId: null, barber: null, startsAt: null }, { replace: true })
    else if (
      typeof choice === 'number' &&
      !barbersForService.value.some((item) => item.id === choice) &&
      chosen !== null
    )
      void update({ barber: null, startsAt: null }, { replace: true })
  },
  { immediate: true },
)

const next = useNextAvailable(
  () => shop.slug,
  () => draft.value.serviceId,
  () => null,
)

/** Price and duration for the chosen barber, or the service default. */
function offerFor(barberId: number | null) {
  const own = shop.barbers
    .find((item) => item.id === barberId)
    ?.services.find((item) => item.service_id === draft.value.serviceId)
  return {
    price: own?.price_ore ?? service.value?.price_ore ?? 0,
    duration: own?.duration_minutes ?? service.value?.duration_minutes ?? 0,
  }
}

const chosenSlotBarber = ref<number | null>(null)
const summaryBarberId = computed(() => barber.value?.id ?? chosenSlotBarber.value)

const timeLabel = computed(() =>
  draft.value.startsAt
    ? format.instant(draft.value.startsAt, shop.timezone, {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
      })
    : null,
)

const answers = computed(() => {
  const result: Partial<Record<BookingStep, string>> = {}
  if (service.value) result.service = service.value.name
  if (draft.value.barber === 'any') result.barber = t('booking.barber.any')
  else if (barber.value) result.barber = barber.value.display_name
  if (timeLabel.value) result.time = timeLabel.value
  return result
})

function chooseSlot(slot: Slot) {
  chosenSlotBarber.value = slot.barber_id
  void update({ startsAt: slot.starts_at, date: slot.date })
}

function bookSoonest(slot: Slot) {
  chosenSlotBarber.value = slot.barber_id
  void update({ barber: 'any', startsAt: slot.starts_at, date: slot.date })
}

// Details
const formError = ref<string | null>(null)
const { defineField, handleSubmit, errors, setErrors } = useForm({
  validationSchema: toTypedSchema(
    z.object({ name: optionalString(), email: optionalEmail(), note: optionalString() }),
  ),
  initialValues: { name: '', email: '', note: '' },
})
const [name] = defineField('name')
const [email] = defineField('email')
const [note] = defineField('note')

watch(
  () => customer.data.value,
  (signedIn) => {
    if (signedIn?.name && !name.value) name.value = signedIn.name
    if (signedIn?.email && !email.value) email.value = signedIn.email
  },
  { immediate: true },
)

const submitting = ref(false)

const onSubmit = handleSubmit(async (values) => {
  formError.value = null
  if (!values.name && !customer.data.value?.name) {
    setErrors({ name: t('validation.required') })
    return
  }

  submitting.value = true
  try {
    if (!customer.data.value) {
      if (!otp.sentTo.value) {
        formError.value = t('booking.otp.sendFirst')
        return
      }
      if (!(await otp.confirm())) return
    }

    const result = await create.mutateAsync({
      service_id: draft.value.serviceId ?? 0,
      barber_id: typeof draft.value.barber === 'number' ? draft.value.barber : null,
      starts_at: draft.value.startsAt ?? '',
      name: values.name,
      email: values.email,
      note: values.note,
    })

    await router.push({
      name: 'manage-booking',
      params: { token: result.manage_token },
      query: { new: '1' },
    })
  } catch (error) {
    if (error instanceof ApiError && error.status === 409) {
      toast.show(error.message, 'error')
      await update({ startsAt: null })
      return
    }
    formError.value = applyApiError(
      error,
      setErrors,
      ['name', 'email', 'note'],
      t('errors.generic'),
    )
  } finally {
    submitting.value = false
  }
})
</script>

<template>
  <div class="mx-auto max-w-5xl px-4 py-6 sm:px-8 md:py-10">
    <h1 class="text-4xl leading-none font-black uppercase font-condensed sm:text-5xl">
      {{ t('booking.title') }}
    </h1>
    <StepNav class="mt-6" :current="step" :answers="answers" @go="goTo" />

    <div class="mt-8 grid gap-10 md:grid-cols-[1fr_17rem]">
      <section class="min-w-0" aria-live="polite">
        <!-- 01 Service -->
        <template v-if="step === 'service'">
          <h2 class="text-2xl font-extrabold uppercase font-condensed">
            {{ t('booking.service.title') }}
          </h2>
          <ul class="mt-4 divide-y divide-line border-y-2 border-ink">
            <li v-for="item in shop.services" :key="item.id">
              <button
                type="button"
                class="group flex w-full items-baseline gap-3 py-4 text-left"
                @click="update({ serviceId: item.id })"
              >
                <span class="min-w-0">
                  <span class="block font-semibold group-hover:text-pole">{{ item.name }}</span>
                  <span v-if="item.description" class="block text-sm text-ink-soft">{{
                    item.description
                  }}</span>
                </span>
                <span
                  class="mb-1 flex-1 self-end border-b-2 border-dotted border-ink/30"
                  aria-hidden="true"
                />
                <span class="shrink-0 font-mono text-sm text-ink-soft tabular">{{
                  format.duration(item.duration_minutes)
                }}</span>
                <span class="shrink-0 font-mono text-lg tabular">{{
                  format.price(item.price_ore)
                }}</span>
              </button>
            </li>
          </ul>
        </template>

        <!-- 02 Barber -->
        <template v-else-if="step === 'barber'">
          <h2 class="text-2xl font-extrabold uppercase font-condensed">
            {{ t('booking.barber.title') }}
          </h2>
          <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <button
              type="button"
              class="border-2 border-ink bg-ink p-4 text-left text-paper hover:border-pole hover:bg-pole sm:col-span-2"
              @click="update({ barber: 'any' })"
            >
              <span class="block text-2xl font-black uppercase font-condensed">{{
                t('booking.barber.any')
              }}</span>
              <span class="mt-1 block text-sm text-paper/80">{{
                t('booking.barber.anyHint')
              }}</span>
            </button>
            <button
              v-if="next.data.value"
              type="button"
              class="border-2 border-dashed border-pole p-4 text-left hover:bg-paper-deep sm:col-span-2"
              @click="bookSoonest(next.data.value)"
            >
              <span class="block font-mono text-xs tracking-widest text-pole uppercase">{{
                t('booking.barber.soonest')
              }}</span>
              <span class="mt-1 block font-semibold first-letter:uppercase">
                {{
                  format.localDateTime(`${next.data.value.date}T${next.data.value.time}`, {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long',
                  })
                }}
                {{ next.data.value.time }} · {{ barberNames[next.data.value.barber_id] }}
              </span>
            </button>
            <button
              v-for="item in barbersForService"
              :key="item.id"
              type="button"
              class="flex items-center gap-3 border-2 border-ink p-4 text-left hover:bg-paper-deep"
              @click="update({ barber: item.id })"
            >
              <img
                v-if="item.avatar_url"
                :src="item.avatar_url"
                alt=""
                class="size-14 shrink-0 object-cover"
              />
              <span class="min-w-0">
                <span class="block text-xl font-black uppercase font-condensed">{{
                  item.display_name
                }}</span>
                <span class="block font-mono text-xs text-ink-soft tabular">
                  {{ format.price(offerFor(item.id).price) }} ·
                  {{ format.duration(offerFor(item.id).duration) }}
                </span>
              </span>
            </button>
          </div>
          <p v-if="barbersForService.length === 0" class="mt-4 text-ink-soft">
            {{ t('booking.barber.none') }}
          </p>
        </template>

        <!-- 03 Time -->
        <template v-else-if="step === 'time' && draft.serviceId !== null">
          <h2 class="text-2xl font-extrabold uppercase font-condensed">
            {{ t('booking.time.title') }}
          </h2>
          <SlotPicker
            class="mt-4"
            :slug="shop.slug"
            :timezone="shop.timezone"
            :service-id="draft.serviceId"
            :barber-id="barber?.id ?? null"
            :max-days-ahead="shop.max_days_ahead"
            :barber-names="draft.barber === 'any' ? barberNames : {}"
            :selected="draft.startsAt"
            :date="draft.date"
            @update:date="(date) => update({ date }, { replace: true })"
            @select="chooseSlot"
          />
        </template>

        <!-- 04 Details -->
        <template v-else-if="step === 'details'">
          <h2 class="text-2xl font-extrabold uppercase font-condensed">
            {{ t('booking.details.title') }}
          </h2>
          <form class="mt-4 max-w-lg space-y-5" novalidate @submit="onSubmit">
            <FormAlert v-if="formError">{{ formError }}</FormAlert>
            <p
              v-if="customer.data.value"
              class="border-l-4 border-ink bg-paper-deep px-4 py-3 text-sm"
            >
              {{ t('booking.details.signedInAs', { phone: customer.data.value.phone }) }}
            </p>
            <TextField
              v-model="name"
              autocomplete="name"
              :label="t('booking.details.name')"
              :error="errorText(errors.name)"
            />
            <TextField
              v-model="email"
              type="email"
              autocomplete="email"
              :label="t('booking.details.email')"
              :hint="t('booking.details.emailHint')"
              :error="errorText(errors.email)"
            />
            <TextareaField
              v-model="note"
              :rows="2"
              :label="t('booking.details.note')"
              :error="errorText(errors.note)"
            />
            <OtpFields
              v-if="!customer.data.value"
              v-model:phone="otpPhone"
              v-model:code="otpCode"
              :sent-to="otp.sentTo.value"
              :demo-code="otp.demoCode.value"
              :errors="otp.errors.value"
              :sending="otp.sending()"
              @send="otp.send()"
              @change="otp.changeNumber()"
            />
            <AppButton type="submit" class="w-full" :loading="submitting">{{
              t('booking.details.confirm')
            }}</AppButton>
            <p class="text-xs text-ink-soft">
              {{ t('booking.details.cancellation', { hours: shop.cancellation_window_hours }) }}
            </p>
          </form>
        </template>
      </section>

      <aside
        v-if="service"
        class="h-fit border-2 border-ink bg-paper-deep md:sticky md:top-6"
        :aria-label="t('booking.summary')"
      >
        <dl class="divide-y divide-ink/15 px-4">
          <div class="py-3">
            <dt class="font-mono text-[11px] tracking-widest text-ink-soft uppercase">
              {{ t('home.ticket.service') }}
            </dt>
            <dd class="font-semibold">{{ service.name }}</dd>
          </div>
          <div v-if="draft.barber !== null" class="py-3">
            <dt class="font-mono text-[11px] tracking-widest text-ink-soft uppercase">
              {{ t('home.ticket.barber') }}
            </dt>
            <dd class="font-semibold">
              {{
                summaryBarberId !== null && draft.startsAt
                  ? barberNames[summaryBarberId]
                  : (barber?.display_name ?? t('booking.barber.any'))
              }}
            </dd>
          </div>
          <div v-if="timeLabel" class="py-3">
            <dt class="font-mono text-[11px] tracking-widest text-ink-soft uppercase">
              {{ t('home.ticket.time') }}
            </dt>
            <dd class="font-semibold first-letter:uppercase">{{ timeLabel }}</dd>
          </div>
        </dl>
        <p class="flex justify-between bg-ink px-4 py-3 font-mono text-sm text-paper tabular">
          <span>{{ format.duration(offerFor(summaryBarberId).duration) }}</span>
          <span>{{ format.price(offerFor(summaryBarberId).price) }}</span>
        </p>
      </aside>
    </div>
  </div>
</template>
