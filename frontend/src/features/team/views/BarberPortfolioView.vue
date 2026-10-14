<script setup lang="ts">
import { ref, useId } from 'vue'
import { useI18n } from 'vue-i18n'

import AppButton from '@/components/ui/AppButton.vue'
import FormAlert from '@/components/ui/FormAlert.vue'
import ReorderButtons from '@/components/ui/ReorderButtons.vue'
import { useConfirm } from '@/composables/useConfirm'
import { useToast } from '@/composables/useToast'
import { ApiError } from '@/lib/apiError'
import { moveId } from '@/lib/reorder'

import { usePortfolio, usePortfolioMutations, type PortfolioImage } from '../api'
import { useBarberContext } from '../composables/useBarberContext'

const MAX_BYTES = 10 * 1024 * 1024

const { t } = useI18n()
const toast = useToast()
const { confirm } = useConfirm()
const { barberId, canEditOwn } = useBarberContext()
const portfolio = usePortfolio(barberId)
const { upload, updateCaption, remove, reorder } = usePortfolioMutations(barberId)

const inputId = useId()
const uploadError = ref<string | null>(null)
const captions = ref<Record<number, string>>({})

async function onFiles(event: Event) {
  const input = event.target as HTMLInputElement
  const files = [...(input.files ?? [])]
  input.value = ''
  uploadError.value = null

  for (const file of files) {
    if (file.size > MAX_BYTES) {
      uploadError.value = t('upload.tooLarge', { max: 10 })
      continue
    }
    try {
      await upload.mutateAsync(file)
    } catch (error) {
      uploadError.value =
        error instanceof ApiError && error.status === 422 ? error.message : t('errors.generic')
      break
    }
  }
}

function captionOf(image: PortfolioImage): string {
  return captions.value[image.id] ?? image.caption ?? ''
}

async function saveCaption(image: PortfolioImage) {
  const caption = captionOf(image).trim()
  if (caption === (image.caption ?? '')) return
  try {
    await updateCaption.mutateAsync({ imageId: image.id, caption: caption === '' ? null : caption })
    toast.show(t('common.saved'))
  } catch {
    toast.show(t('errors.generic'), 'error')
  }
}

async function destroy(image: PortfolioImage) {
  const confirmed = await confirm({
    title: t('team.portfolio.deleteTitle'),
    confirmLabel: t('common.delete'),
    tone: 'danger',
  })
  if (!confirmed) return
  try {
    await remove.mutateAsync(image.id)
  } catch {
    toast.show(t('errors.generic'), 'error')
  }
}

async function move(index: number, direction: -1 | 1) {
  try {
    await reorder.mutateAsync(moveId(portfolio.data.value ?? [], index, direction))
  } catch {
    toast.show(t('errors.generic'), 'error')
  }
}
</script>

<template>
  <div class="space-y-6">
    <div v-if="canEditOwn" class="flex flex-wrap items-center gap-4">
      <label
        :for="inputId"
        class="inline-flex cursor-pointer items-center gap-2 border-2 border-ink bg-ink px-5 py-3 font-mono text-sm tracking-wider text-paper uppercase focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-pole hover:border-pole hover:bg-pole"
      >
        + {{ upload.isPending.value ? t('upload.working') : t('team.portfolio.add') }}
        <input
          :id="inputId"
          type="file"
          accept="image/jpeg,image/png,image/webp"
          multiple
          class="sr-only"
          :disabled="upload.isPending.value"
          @change="onFiles"
        />
      </label>
      <p class="text-sm text-ink-soft">{{ t('team.portfolio.help') }}</p>
    </div>
    <FormAlert v-if="uploadError">{{ uploadError }}</FormAlert>

    <p v-if="portfolio.isPending.value" class="font-mono text-sm text-ink-soft">
      {{ t('common.loading') }}
    </p>
    <p v-else-if="portfolio.data.value?.length === 0" class="text-ink-soft">
      {{ t('team.portfolio.empty') }}
    </p>

    <ol v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
      <li
        v-for="(image, index) in portfolio.data.value"
        :key="image.id"
        class="border-2 border-ink bg-paper"
      >
        <a
          :href="image.url"
          target="_blank"
          rel="noopener"
          class="block aspect-square bg-paper-deep"
        >
          <img
            :src="image.thumbnail_url"
            :alt="image.caption ?? t('team.portfolio.imageAlt', { n: index + 1 })"
            class="size-full object-cover"
            loading="lazy"
          />
        </a>
        <div v-if="canEditOwn" class="space-y-2 p-2">
          <input
            :value="captionOf(image)"
            :aria-label="t('team.portfolio.caption', { n: index + 1 })"
            :placeholder="t('team.portfolio.captionPlaceholder')"
            maxlength="160"
            class="w-full border border-ink/40 bg-paper px-2 py-1 text-sm focus:border-ink"
            @input="captions[image.id] = ($event.target as HTMLInputElement).value"
            @blur="saveCaption(image)"
            @keydown.enter.prevent="saveCaption(image)"
          />
          <div class="flex items-center justify-between">
            <div class="flex gap-1">
              <ReorderButtons
                class="flex-row! [&>button]:size-7"
                :name="t('team.portfolio.imageAlt', { n: index + 1 })"
                :first="index === 0"
                :last="index === (portfolio.data.value?.length ?? 0) - 1"
                :disabled="reorder.isPending.value"
                @move="(direction) => move(index, direction)"
              />
            </div>
            <AppButton size="sm" variant="ghost" @click="destroy(image)">{{
              t('common.delete')
            }}</AppButton>
          </div>
        </div>
        <p v-else-if="image.caption" class="p-2 text-sm">{{ image.caption }}</p>
      </li>
    </ol>
  </div>
</template>
