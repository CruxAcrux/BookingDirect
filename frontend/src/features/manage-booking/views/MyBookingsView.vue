<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

import AppButton from '@/components/ui/AppButton.vue'
import { useFormat } from '@/composables/useFormat'
import BookingTicket from '@/features/booking/components/BookingTicket.vue'
import OtpFields from '@/features/booking/components/OtpFields.vue'
import { useOtpFlow } from '@/features/booking/composables/useOtpFlow'
import {
  useCustomer,
  useCustomerSignIn,
  useMyBookings,
  type PublicShop,
} from '@/features/booking/api'

import BookingActions from '../components/BookingActions.vue'

const { shop } = defineProps<{ shop: PublicShop }>()

const { t } = useI18n()
const format = useFormat()
const customer = useCustomer(() => shop.slug)
const signedIn = computed(() => customer.data.value != null)
const bookings = useMyBookings(() => shop.slug, signedIn)
const otp = useOtpFlow(() => shop.slug)
const { phone: otpPhone, code: otpCode } = otp
const { signOut } = useCustomerSignIn(() => shop.slug)

const now = Date.now()
const upcoming = computed(() =>
  (bookings.data.value ?? []).filter(
    (booking) => Date.parse(booking.starts_at) >= now && booking.status !== 'cancelled',
  ),
)
const history = computed(() =>
  (bookings.data.value ?? []).filter(
    (booking) => Date.parse(booking.starts_at) < now || booking.status === 'cancelled',
  ),
)
</script>

<template>
  <div class="mx-auto max-w-5xl px-4 py-8 sm:px-8 md:py-12">
    <h1 class="text-5xl leading-none font-black uppercase font-condensed">
      {{ t('myBookings.title') }}
    </h1>

    <p v-if="customer.isPending.value" class="mt-8 font-mono text-sm text-ink-soft">
      {{ t('common.loading') }}
    </p>

    <section v-else-if="!signedIn" class="mt-8 max-w-md" aria-labelledby="sign-in-title">
      <h2 id="sign-in-title" class="text-2xl font-extrabold uppercase font-condensed">
        {{ t('myBookings.signIn') }}
      </h2>
      <p class="mt-1 text-ink-soft">{{ t('myBookings.signInBody') }}</p>
      <form class="mt-6" novalidate @submit.prevent="otp.sentTo.value ? otp.confirm() : otp.send()">
        <OtpFields
          v-model:phone="otpPhone"
          v-model:code="otpCode"
          :sent-to="otp.sentTo.value"
          :demo-code="otp.demoCode.value"
          :errors="otp.errors.value"
          :sending="otp.sending()"
          @send="otp.send()"
          @change="otp.changeNumber()"
        />
        <AppButton
          v-if="otp.sentTo.value"
          type="submit"
          class="mt-4 w-full"
          :loading="otp.verifying()"
        >
          {{ t('myBookings.signInButton') }}
        </AppButton>
      </form>
    </section>

    <template v-else>
      <p class="mt-3 text-sm text-ink-soft">
        {{ t('booking.details.signedInAs', { phone: customer.data.value?.phone ?? '' }) }}
        <button type="button" class="ml-2 underline underline-offset-4" @click="signOut.mutate()">
          {{ t('myBookings.signOut') }}
        </button>
      </p>

      <section class="mt-10" aria-labelledby="upcoming-title">
        <h2
          id="upcoming-title"
          class="border-b-2 border-ink pb-2 text-3xl font-black uppercase font-condensed"
        >
          {{ t('myBookings.upcoming') }}
        </h2>
        <p v-if="upcoming.length === 0" class="mt-4 text-ink-soft">
          {{ t('myBookings.noneUpcoming') }}
          <RouterLink
            :to="{ name: 'book', params: { slug: shop.slug } }"
            class="underline decoration-pole underline-offset-4"
          >
            {{ t('shop.book') }}
          </RouterLink>
        </p>
        <ul class="mt-6 grid gap-10 md:grid-cols-2">
          <li v-for="booking in upcoming" :key="booking.id" class="space-y-5">
            <BookingTicket :booking="booking" />
            <BookingActions
              :booking="booking"
              :target="{ slug: shop.slug, id: booking.id }"
              :max-days-ahead="shop.max_days_ahead"
            />
          </li>
        </ul>
      </section>

      <section v-if="history.length" class="mt-14" aria-labelledby="history-title">
        <h2
          id="history-title"
          class="border-b-2 border-ink pb-2 text-3xl font-black uppercase font-condensed"
        >
          {{ t('myBookings.history') }}
        </h2>
        <ul class="divide-y divide-line">
          <li
            v-for="booking in history"
            :key="booking.id"
            class="flex flex-wrap items-center justify-between gap-3 py-3"
          >
            <div>
              <p class="font-semibold">
                {{ booking.service.name }} · {{ booking.barber.display_name }}
              </p>
              <p class="font-mono text-xs text-ink-soft first-letter:uppercase">
                {{
                  format.localDateTime(booking.starts_at_local, {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                  })
                }}
                · {{ t(`booking.status.${booking.status}`) }}
              </p>
            </div>
            <RouterLink
              :to="{
                name: 'book',
                params: { slug: shop.slug },
                query: { service: booking.service.id, barber: booking.barber.id },
              }"
              class="border-2 border-ink px-3 py-1.5 font-mono text-xs tracking-wider uppercase hover:bg-ink hover:text-paper"
            >
              {{ t('myBookings.bookAgain') }}
            </RouterLink>
          </li>
        </ul>
      </section>
    </template>
  </div>
</template>
