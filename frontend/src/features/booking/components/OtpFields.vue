<script setup lang="ts">
import { useI18n } from 'vue-i18n'

import AppButton from '@/components/ui/AppButton.vue'
import FormAlert from '@/components/ui/FormAlert.vue'
import TextField from '@/components/ui/TextField.vue'

const { sentTo, demoCode, errors, sending } = defineProps<{
  sentTo: string | null
  demoCode: string | null
  errors: { phone?: string; code?: string; general?: string }
  sending: boolean
}>()

const emit = defineEmits<{ send: []; change: [] }>()
const phone = defineModel<string>('phone', { required: true })
const code = defineModel<string>('code', { required: true })
const { t } = useI18n()
</script>

<template>
  <div class="space-y-4">
    <FormAlert v-if="errors.general">{{ errors.general }}</FormAlert>

    <div v-if="!sentTo" class="flex flex-wrap items-end gap-3">
      <TextField
        v-model="phone"
        type="tel"
        autocomplete="tel"
        class="min-w-48 flex-1"
        :label="t('booking.details.phone')"
        :hint="t('booking.details.phoneHint')"
        :error="errors.phone"
        @keydown.enter.prevent="emit('send')"
      />
      <AppButton variant="ghost" :loading="sending" @click="emit('send')">
        {{ t('booking.otp.send') }}
      </AppButton>
    </div>

    <template v-else>
      <p class="text-sm">
        {{ t('booking.otp.sentTo', { phone: sentTo }) }}
        <button
          type="button"
          class="ml-1 underline decoration-pole underline-offset-4"
          @click="emit('change')"
        >
          {{ t('booking.otp.change') }}
        </button>
      </p>
      <p
        v-if="demoCode"
        class="inline-flex items-center gap-3 border-2 border-dashed border-brass px-3 py-2 font-mono text-sm"
        role="status"
      >
        <span class="text-xs tracking-widest text-brass-deep uppercase">{{
          t('booking.otp.demo')
        }}</span>
        <span class="text-lg tracking-[0.3em] tabular">{{ demoCode }}</span>
      </p>
      <TextField
        v-model="code"
        inputmode="numeric"
        autocomplete="one-time-code"
        maxlength="6"
        class="max-w-48 [&_input]:font-mono [&_input]:text-xl [&_input]:tracking-[0.4em] [&_input]:tabular"
        :label="t('booking.otp.code')"
        :error="errors.code"
      />
      <button type="button" class="text-sm underline underline-offset-4" @click="emit('send')">
        {{ t('booking.otp.resend') }}
      </button>
    </template>
  </div>
</template>
