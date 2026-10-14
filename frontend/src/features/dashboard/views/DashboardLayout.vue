<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

import AppButton from '@/components/ui/AppButton.vue'
import LocaleSwitcher from '@/components/ui/LocaleSwitcher.vue'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()
const signingOut = ref(false)

async function signOut() {
  signingOut.value = true
  try {
    await auth.logout()
    await router.replace({ name: 'login' })
  } finally {
    signingOut.value = false
  }
}
</script>

<template>
  <div class="flex min-h-dvh flex-col">
    <header class="bg-ink text-paper">
      <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2 px-4 py-3 sm:px-8">
        <RouterLink
          :to="{ name: 'dashboard' }"
          class="text-2xl font-extrabold tracking-tight uppercase font-condensed"
        >
          Booking<span class="text-pole">Direct</span>
        </RouterLink>
        <p
          v-if="auth.currentShop"
          class="order-last flex w-full min-w-0 items-center gap-2 font-mono text-xs tracking-widest uppercase sm:order-none sm:w-auto sm:flex-1"
        >
          <span class="hidden text-paper/60 sm:inline" aria-hidden="true">/</span>
          <span class="truncate">{{ auth.currentShop.name }}</span>
          <span class="shrink-0 bg-paper/15 px-1.5 py-0.5 text-[11px]">
            {{ t(`dashboard.roles.${auth.currentShop.role}`) }}
          </span>
        </p>
        <div class="flex items-center gap-3">
          <span class="hidden text-sm text-paper/80 md:inline">{{ auth.user?.name }}</span>
          <LocaleSwitcher
            class="border-paper/40 [&_[aria-pressed=true]]:bg-paper [&_[aria-pressed=true]]:text-ink"
          />
          <AppButton
            variant="ghost"
            :loading="signingOut"
            class="border-paper/40 px-3 py-1.5 text-xs text-paper hover:bg-paper/10"
            @click="signOut"
          >
            {{ t('dashboard.signOut') }}
          </AppButton>
        </div>
      </div>
      <div class="h-1.5 pole-stripe" aria-hidden="true" />
    </header>

    <main id="main" tabindex="-1" class="flex-1 focus:outline-none">
      <RouterView />
    </main>
  </div>
</template>
