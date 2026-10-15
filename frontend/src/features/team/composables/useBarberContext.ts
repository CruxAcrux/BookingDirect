import { computed } from 'vue'
import { useRoute } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

import { useBarber } from '../api'

/** The barber in the current route, and what the signed-in user may do with them. */
export function useBarberContext() {
  const route = useRoute()
  const auth = useAuthStore()

  const barberId = computed(() => Number(route.params.barberId))
  const barber = useBarber(barberId)

  const isOwner = computed(() => auth.currentShop?.role === 'owner')
  const isSelf = computed(() => barber.data.value?.is_me === true)

  return {
    barberId,
    barber,
    isOwner,
    /** Name, bio, photo, portfolio and time off. */
    canEditOwn: computed(() => isOwner.value || isSelf.value),
  }
}
