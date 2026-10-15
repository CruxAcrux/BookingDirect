<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

import FormDialog from '@/components/ui/FormDialog.vue'
import BarberAvatar from '@/features/team/components/BarberAvatar.vue'

import type { PublicShop } from '@/features/booking/api'

type PublicBarber = PublicShop['barbers'][number]

const { barber, slug } = defineProps<{ barber: PublicBarber; slug: string }>()

const { t } = useI18n()
const viewing = ref<PublicBarber['portfolio'][number] | null>(null)
const open = ref(false)

function show(image: PublicBarber['portfolio'][number]) {
  viewing.value = image
  open.value = true
}
</script>

<template>
  <article class="border-2 border-ink bg-paper">
    <div class="flex items-center gap-4 p-4">
      <BarberAvatar :barber="barber" class="size-20" />
      <div class="min-w-0">
        <h3 class="text-3xl leading-none font-black uppercase font-condensed">
          {{ barber.display_name }}
        </h3>
        <p v-if="barber.bio" class="mt-1 text-sm text-ink-soft">{{ barber.bio }}</p>
      </div>
    </div>

    <ul
      v-if="barber.portfolio.length"
      class="grid grid-cols-3 gap-1 px-4"
      :aria-label="t('shop.portfolio', { name: barber.display_name })"
    >
      <li v-for="(image, index) in barber.portfolio.slice(0, 6)" :key="image.id">
        <button
          type="button"
          class="block aspect-square w-full overflow-hidden bg-paper-deep"
          :aria-label="image.caption ?? t('team.portfolio.imageAlt', { n: index + 1 })"
          @click="show(image)"
        >
          <img
            :src="image.thumbnail_url"
            alt=""
            loading="lazy"
            class="size-full object-cover transition-transform hover:scale-105"
          />
        </button>
      </li>
    </ul>

    <div class="p-4">
      <RouterLink
        :to="{ name: 'book', params: { slug }, query: { barber: barber.id } }"
        class="inline-block border-2 border-ink px-4 py-2 font-mono text-xs tracking-wider uppercase hover:bg-ink hover:text-paper"
      >
        {{ t('shop.bookWith', { name: barber.display_name }) }}
      </RouterLink>
    </div>

    <FormDialog v-model:open="open" :title="barber.display_name">
      <figure v-if="viewing">
        <img
          :src="viewing.url"
          :alt="viewing.caption ?? ''"
          class="max-h-[70dvh] w-full object-contain"
        />
        <figcaption v-if="viewing.caption" class="mt-3 text-sm">{{ viewing.caption }}</figcaption>
      </figure>
    </FormDialog>
  </article>
</template>
