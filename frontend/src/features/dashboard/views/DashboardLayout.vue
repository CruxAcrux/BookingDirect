<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useQueryClient } from '@tanstack/vue-query'

import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import LocaleSwitcher from '@/components/ui/LocaleSwitcher.vue'
import ToastHost from '@/components/ui/ToastHost.vue'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const queryClient = useQueryClient()
const auth = useAuthStore()
const signingOut = ref(false)

interface NavItem {
  name: string
  label: string
  /** Route names (or prefixes ending in "-") that highlight this item. */
  matches: string[]
}

const navigation = computed(() => {
  const items: NavItem[] = []
  const shop = auth.currentShop

  if (shop) {
    items.push(
      { name: 'dashboard', label: t('nav.overview'), matches: ['dashboard'] },
      { name: 'team', label: t('nav.team'), matches: ['team', 'barber', 'barber-'] },
      { name: 'services', label: t('nav.services'), matches: ['services'] },
    )
    if (shop.role === 'owner') {
      items.push({ name: 'settings', label: t('nav.settings'), matches: ['settings', 'settings-'] })
    }
  }
  if (auth.user?.is_platform_admin) {
    items.push({ name: 'admin-shops', label: t('nav.adminShops'), matches: ['admin-shops'] })
  }

  return items
})

function isActive(item: NavItem): boolean {
  const current = String(route.name ?? '')
  return item.matches.some((match) =>
    match.endsWith('-') ? current.startsWith(match) : current === match,
  )
}

async function signOut() {
  signingOut.value = true
  try {
    await auth.logout()
    queryClient.clear()
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
          <button
            type="button"
            :disabled="signingOut"
            class="border-2 border-paper/40 px-3 py-1.5 font-mono text-xs tracking-wider uppercase hover:bg-paper/10 disabled:opacity-60"
            @click="signOut"
          >
            {{ t('dashboard.signOut') }}
          </button>
        </div>
      </div>
      <div class="h-1.5 pole-stripe" aria-hidden="true" />
    </header>

    <div class="flex flex-1 flex-col md:flex-row">
      <nav
        :aria-label="t('nav.label')"
        class="border-b border-line bg-paper-deep md:w-56 md:shrink-0 md:border-r md:border-b-0"
      >
        <ul class="flex gap-1 overflow-x-auto px-4 py-2 md:flex-col md:gap-0 md:px-0 md:py-6">
          <li v-for="item in navigation" :key="item.name">
            <RouterLink
              :to="{ name: item.name }"
              :aria-current="isActive(item) ? 'page' : undefined"
              :class="{
                'bg-ink text-paper md:border-pole md:bg-transparent md:text-ink': isActive(item),
              }"
              class="block border-transparent px-3 py-2 font-mono text-xs tracking-widest whitespace-nowrap uppercase hover:text-pole md:border-l-4 md:px-6 md:py-3"
            >
              {{ item.label }}
            </RouterLink>
          </li>
        </ul>
      </nav>

      <main id="main" tabindex="-1" class="min-w-0 flex-1 focus:outline-none">
        <RouterView />
      </main>
    </div>

    <ToastHost />
    <ConfirmDialog />
  </div>
</template>
