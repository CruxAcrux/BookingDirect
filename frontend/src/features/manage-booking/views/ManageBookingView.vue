<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'

import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import ToastHost from '@/components/ui/ToastHost.vue'
import NotFoundView from '@/features/errors/views/NotFoundView.vue'
import BookingTicket from '@/features/booking/components/BookingTicket.vue'
import { useManagedBooking, usePublicShop } from '@/features/booking/api'
import PublicFooter from '@/features/public-shop/components/PublicFooter.vue'
import PublicHeader from '@/features/public-shop/components/PublicHeader.vue'
import { ApiError } from '@/lib/apiError'

import BookingActions from '../components/BookingActions.vue'

const { t } = useI18n()
const route = useRoute()
const token = computed(() => String(route.params.token))
const isNew = computed(() => route.query.new === '1')

const booking = useManagedBooking(token)
const slug = computed(() => booking.data.value?.shop.slug ?? '')
const shop = usePublicShop(slug)
const missing = computed(
  () => booking.error.value instanceof ApiError && booking.error.value.status === 404,
)

const copied = ref(false)
async function copyLink() {
  await navigator.clipboard.writeText(window.location.origin + route.path)
  copied.value = true
}
</script>

<template>
  <div class="flex min-h-dvh flex-col">
    <PublicHeader
      :name="booking.data.value?.shop.name"
      :slug="slug || undefined"
      :logo-url="shop.data.value?.logo_url"
    />
    <main id="main" tabindex="-1" class="flex-1 focus:outline-none">
      <NotFoundView v-if="missing" />
      <div
        v-else-if="booking.data.value"
        class="mx-auto grid max-w-5xl gap-10 px-4 py-8 sm:px-8 md:grid-cols-[1fr_auto] md:py-14"
      >
        <div>
          <p class="font-mono text-xs tracking-[0.2em] text-pole uppercase">
            {{ isNew ? t('manage.bookedKicker') : t('manage.kicker') }}
          </p>
          <h1
            class="mt-2 text-5xl leading-[0.92] font-black text-balance uppercase font-condensed sm:text-6xl"
          >
            {{ isNew ? t('manage.bookedTitle') : t(`manage.title.${booking.data.value.status}`) }}
          </h1>
          <p v-if="isNew" class="mt-4 max-w-md text-ink-soft">{{ t('manage.bookedBody') }}</p>

          <section class="mt-8 border-2 border-dashed border-ink p-4" aria-labelledby="link-title">
            <h2 id="link-title" class="font-mono text-xs tracking-widest uppercase">
              {{ t('manage.linkTitle') }}
            </h2>
            <p class="mt-1 text-sm text-ink-soft">{{ t('manage.linkBody') }}</p>
            <button
              type="button"
              class="mt-3 font-mono text-xs tracking-wider uppercase underline decoration-pole decoration-2 underline-offset-4"
              @click="copyLink"
            >
              {{ copied ? t('manage.copied') : t('manage.copy') }}
            </button>
          </section>

          <BookingActions
            class="mt-8"
            :booking="booking.data.value"
            :target="{ token }"
            :max-days-ahead="shop.data.value?.max_days_ahead ?? 60"
          />

          <p v-if="booking.data.value.shop.address" class="mt-10 text-sm">
            <span class="block font-mono text-xs tracking-widest text-ink-soft uppercase">{{
              t('manage.where')
            }}</span>
            {{ booking.data.value.shop.name }}, {{ booking.data.value.shop.address }}
          </p>
          <RouterLink
            :to="{
              name: 'book',
              params: { slug },
              query: {
                service: booking.data.value.service.id,
                barber: booking.data.value.barber.id,
              },
            }"
            class="mt-6 inline-block font-mono text-xs tracking-wider uppercase underline underline-offset-4"
          >
            {{ t('manage.bookAgain') }}
          </RouterLink>
        </div>
        <div class="flex justify-center md:justify-end">
          <BookingTicket :booking="booking.data.value" />
        </div>
      </div>
      <p v-else class="mx-auto max-w-5xl px-4 py-16 font-mono text-sm text-ink-soft sm:px-8">
        {{ t('common.loading') }}
      </p>
    </main>
    <PublicFooter :slug="slug || undefined" />
    <ToastHost />
    <ConfirmDialog />
  </div>
</template>
