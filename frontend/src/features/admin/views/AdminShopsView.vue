<script setup lang="ts">
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useForm } from 'vee-validate'
import { z } from 'zod'

import AppButton from '@/components/ui/AppButton.vue'
import FormAlert from '@/components/ui/FormAlert.vue'
import FormDialog from '@/components/ui/FormDialog.vue'
import PageHeader from '@/components/ui/PageHeader.vue'
import TextField from '@/components/ui/TextField.vue'
import { useConfirm } from '@/composables/useConfirm'
import { useToast } from '@/composables/useToast'
import { emailField, optionalString, requiredString } from '@/lib/forms/schemas'
import { applyApiError } from '@/lib/forms/serverErrors'
import { toTypedSchema } from '@/lib/forms/toTypedSchema'
import { useErrorText } from '@/lib/forms/useErrorText'
import { refDebounced } from '@/lib/refDebounced'

import { useAdminShops, useCreateShop, useSetShopActive, type AdminShop } from '../api'

const { t, d } = useI18n()
const errorText = useErrorText()
const toast = useToast()
const { confirm } = useConfirm()

const search = ref('')
const debouncedSearch = refDebounced(search, 300)
const page = ref(1)
watch(debouncedSearch, () => {
  page.value = 1
})

const shops = useAdminShops(debouncedSearch, page)
const create = useCreateShop()
const setActive = useSetShopActive()

const dialogOpen = ref(false)
const formError = ref<string | null>(null)

const { defineField, handleSubmit, errors, setErrors, resetForm } = useForm({
  validationSchema: toTypedSchema(
    z.object({
      name: requiredString(),
      slug: optionalString().pipe(
        z
          .string()
          .regex(/^[a-z0-9]+(?:-[a-z0-9]+)*$/, { error: 'validation.slug' })
          .nullable(),
      ),
      owner_name: requiredString(),
      owner_email: emailField(),
    }),
  ),
  initialValues: { name: '', slug: '', owner_name: '', owner_email: '' },
})
const [name] = defineField('name')
const [slug] = defineField('slug')
const [ownerName] = defineField('owner_name')
const [ownerEmail] = defineField('owner_email')

function openForm() {
  resetForm()
  formError.value = null
  dialogOpen.value = true
}

const onSubmit = handleSubmit(async (values) => {
  formError.value = null
  try {
    const shop = await create.mutateAsync({
      name: values.name,
      slug: values.slug,
      owner: { name: values.owner_name, email: values.owner_email },
    })
    dialogOpen.value = false
    toast.show(t('admin.created', { name: shop.name }))
  } catch (error) {
    formError.value = applyApiError(
      error,
      (fieldErrors) => {
        setErrors({
          ...fieldErrors,
          owner_name: fieldErrors['owner.name'],
          owner_email: fieldErrors['owner.email'],
        })
      },
      ['name', 'slug', 'owner.name', 'owner.email'],
      t('errors.generic'),
    )
  }
})

async function toggle(shop: AdminShop) {
  if (shop.is_active) {
    const confirmed = await confirm({
      title: t('admin.deactivateTitle', { name: shop.name }),
      body: t('admin.deactivateBody'),
      confirmLabel: t('admin.deactivate'),
      tone: 'danger',
    })
    if (!confirmed) return
  }
  try {
    await setActive.mutateAsync({ id: shop.id, active: !shop.is_active })
  } catch {
    toast.show(t('errors.generic'), 'error')
  }
}
</script>

<template>
  <div class="mx-auto max-w-6xl px-4 py-8 sm:px-8 md:py-12">
    <PageHeader :kicker="t('admin.kicker')" :title="t('admin.title')">
      <template #actions>
        <AppButton @click="openForm">+ {{ t('admin.add') }}</AppButton>
      </template>
    </PageHeader>

    <div class="mt-6 max-w-sm">
      <TextField v-model="search" type="text" :label="t('admin.search')" />
    </div>

    <p v-if="shops.isPending.value" class="mt-8 font-mono text-sm text-ink-soft">
      {{ t('common.loading') }}
    </p>
    <template v-else-if="shops.data.value">
      <div class="mt-6 overflow-x-auto">
        <table class="w-full min-w-[40rem] text-left">
          <thead class="border-b-2 border-ink font-mono text-xs tracking-widest uppercase">
            <tr>
              <th scope="col" class="py-2 pr-4">{{ t('admin.columns.shop') }}</th>
              <th scope="col" class="py-2 pr-4">{{ t('admin.columns.owners') }}</th>
              <th scope="col" class="py-2 pr-4">{{ t('admin.columns.created') }}</th>
              <th scope="col" class="py-2 pr-4">{{ t('admin.columns.status') }}</th>
              <th scope="col" class="py-2">
                <span class="sr-only">{{ t('admin.columns.actions') }}</span>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-line">
            <tr
              v-for="shop in shops.data.value.data"
              :key="shop.id"
              :class="{ 'text-ink-soft': !shop.is_active }"
            >
              <td class="py-3 pr-4">
                <p class="font-medium">{{ shop.name }}</p>
                <p class="font-mono text-xs text-ink-soft">/s/{{ shop.slug }}</p>
              </td>
              <td class="py-3 pr-4 text-sm">
                <p v-for="owner in shop.owners" :key="owner.id">
                  {{ owner.name }} · <span class="font-mono text-xs">{{ owner.email }}</span>
                </p>
              </td>
              <td class="py-3 pr-4 font-mono text-sm tabular">
                {{ d(new Date(shop.created_at), 'date') }}
              </td>
              <td class="py-3 pr-4">
                <span
                  class="px-1.5 py-0.5 font-mono text-[11px] tracking-wider uppercase"
                  :class="shop.is_active ? 'bg-ink text-paper' : 'border border-pole text-pole'"
                >
                  {{ shop.is_active ? t('admin.active') : t('admin.inactive') }}
                </span>
              </td>
              <td class="py-3 text-right">
                <AppButton size="sm" variant="ghost" @click="toggle(shop)">
                  {{ shop.is_active ? t('admin.deactivate') : t('admin.activate') }}
                </AppButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-if="shops.data.value.data.length === 0" class="mt-6 text-ink-soft">
        {{ t('admin.empty') }}
      </p>
      <nav
        v-if="shops.data.value.meta.last_page > 1"
        :aria-label="t('admin.pagination')"
        class="mt-6 flex items-center gap-4 font-mono text-sm"
      >
        <AppButton size="sm" variant="ghost" :disabled="page <= 1" @click="page--"
          >← {{ t('admin.previous') }}</AppButton
        >
        <span>{{ t('admin.page', { page, total: shops.data.value.meta.last_page }) }}</span>
        <AppButton
          size="sm"
          variant="ghost"
          :disabled="page >= shops.data.value.meta.last_page"
          @click="page++"
        >
          {{ t('admin.next') }} →
        </AppButton>
      </nav>
    </template>

    <FormDialog v-model:open="dialogOpen" :title="t('admin.addTitle')">
      <form class="space-y-5" novalidate @submit="onSubmit">
        <FormAlert v-if="formError">{{ formError }}</FormAlert>
        <TextField v-model="name" :label="t('admin.fields.name')" :error="errorText(errors.name)" />
        <TextField
          v-model="slug"
          :label="t('admin.fields.slug')"
          :hint="t('admin.slugHint')"
          :error="errorText(errors.slug)"
          autocapitalize="off"
        />
        <div class="grid gap-5 sm:grid-cols-2">
          <TextField
            v-model="ownerName"
            :label="t('admin.fields.ownerName')"
            :error="errorText(errors.owner_name)"
          />
          <TextField
            v-model="ownerEmail"
            type="email"
            :label="t('admin.fields.ownerEmail')"
            :error="errorText(errors.owner_email)"
          />
        </div>
        <p class="text-sm text-ink-soft">{{ t('admin.inviteHint') }}</p>
        <div class="flex justify-end">
          <AppButton type="submit" :loading="create.isPending.value">{{
            t('admin.add')
          }}</AppButton>
        </div>
      </form>
    </FormDialog>
  </div>
</template>
