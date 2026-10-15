<script setup lang="ts">
import { useI18n } from 'vue-i18n'

import ImageUpload from '@/components/ui/ImageUpload.vue'
import { useToast } from '@/composables/useToast'
import { ApiError } from '@/lib/apiError'

import { useShop, useShopImage } from '../api'

const { t } = useI18n()
const toast = useToast()
const shop = useShop()
const logo = useShopImage('logo')
const cover = useShopImage('cover')

function errorOf(error: unknown): string | null {
  if (!error) return null
  return error instanceof ApiError && error.status === 422 ? error.message : t('errors.generic')
}

async function run(action: () => Promise<unknown>) {
  try {
    await action()
    toast.show(t('common.saved'))
  } catch {
    // The upload component shows the mutation's error.
  }
}
</script>

<template>
  <div class="space-y-10">
    <ImageUpload
      :label="t('settings.logo')"
      :image-url="shop.data.value?.logo_url"
      :busy="logo.upload.isPending.value || logo.remove.isPending.value"
      :error="errorOf(logo.upload.error.value)"
      @upload="(file) => run(() => logo.upload.mutateAsync(file))"
      @remove="run(() => logo.remove.mutateAsync())"
    />
    <ImageUpload
      :label="t('settings.cover')"
      shape="wide"
      :image-url="shop.data.value?.cover_url"
      :busy="cover.upload.isPending.value || cover.remove.isPending.value"
      :error="errorOf(cover.upload.error.value)"
      @upload="(file) => run(() => cover.upload.mutateAsync(file))"
      @remove="run(() => cover.remove.mutateAsync())"
    />
    <p class="text-sm text-ink-soft">{{ t('upload.help') }}</p>
  </div>
</template>
