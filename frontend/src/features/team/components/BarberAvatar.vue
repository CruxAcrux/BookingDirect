<script setup lang="ts">
import { computed } from 'vue'

import type { Barber } from '../api'

const { barber } = defineProps<{ barber: Pick<Barber, 'display_name' | 'avatar_url'> }>()

const initials = computed(() =>
  barber.display_name
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part.charAt(0))
    .join('')
    .toUpperCase(),
)
</script>

<template>
  <span
    class="grid shrink-0 place-items-center overflow-hidden bg-ink text-paper"
    aria-hidden="true"
  >
    <img v-if="barber.avatar_url" :src="barber.avatar_url" alt="" class="size-full object-cover" />
    <span v-else class="text-2xl font-black font-condensed">{{ initials }}</span>
  </span>
</template>
