<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'

import ToastHost from '@/components/ui/ToastHost.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import NotFoundView from '@/features/errors/views/NotFoundView.vue'
import { ApiError } from '@/lib/apiError'
import { usePublicShop } from '@/features/booking/api'

import PublicFooter from '../components/PublicFooter.vue'
import PublicHeader from '../components/PublicHeader.vue'

const { t } = useI18n()
const route = useRoute()
const slug = computed(() => String(route.params.slug))
const shop = usePublicShop(slug)
const missing = computed(
  () => shop.error.value instanceof ApiError && shop.error.value.status === 404,
)
</script>

<template>
  <div class="flex min-h-dvh flex-col">
    <PublicHeader
      :name="shop.data.value?.name"
      :slug="slug"
      :logo-url="shop.data.value?.logo_url"
    />
    <main id="main" tabindex="-1" class="flex-1 focus:outline-none">
      <NotFoundView v-if="missing" />
      <RouterView v-else-if="shop.data.value" :shop="shop.data.value" />
      <div v-else-if="shop.isError.value" class="mx-auto max-w-5xl px-4 py-16 sm:px-8" role="alert">
        <p>{{ t('errors.generic') }}</p>
        <button type="button" class="mt-3 underline underline-offset-4" @click="shop.refetch()">
          {{ t('common.retry') }}
        </button>
      </div>
      <p v-else class="mx-auto max-w-5xl px-4 py-16 font-mono text-sm text-ink-soft sm:px-8">
        {{ t('common.loading') }}
      </p>
    </main>
    <PublicFooter :slug="slug" />
    <ToastHost />
    <ConfirmDialog />
  </div>
</template>
