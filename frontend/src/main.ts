import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { QueryClient, VueQueryPlugin } from '@tanstack/vue-query'

import App from './App.vue'
import router from './router'
import { i18n } from './i18n'
import { onUnauthorized } from './lib/api'
import { useAuthStore } from './stores/auth'
import './assets/main.css'

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 30_000,
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
})

document.documentElement.lang = i18n.global.locale.value

const app = createApp(App)
  .use(createPinia())
  .use(router)
  .use(i18n)
  .use(VueQueryPlugin, { queryClient })

// An expired session surfaces as a 401 on any request; send the user back to sign in.
onUnauthorized(() => {
  useAuthStore().clear()
  queryClient.clear()
  const { currentRoute } = router
  if (currentRoute.value.meta.requiresAuth) {
    void router.replace({ name: 'login', query: { redirect: currentRoute.value.fullPath } })
  }
})

app.mount('#app')
