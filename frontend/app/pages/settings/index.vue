<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'

const { user, setUser } = useAuth()

const profileSchema = z.object({
  name: z.string().min(2, 'Name is required'),
  email: z.string().email('Invalid email'),
  password: z.string().min(6, 'Password must be at least 6 characters').optional().or(z.literal(''))
})

type ProfileSchema = z.output<typeof profileSchema>

const profile = reactive<Partial<ProfileSchema>>({
  name: user.value?.name || '',
  email: user.value?.email || '',
  password: ''
})

const toast = useToast()
const config = useRuntimeConfig()
const api = useApi()

async function onSubmit(event: FormSubmitEvent<ProfileSchema>) {
  try {
    const data = await api(`${config.public.apiBase}/profile`, {
      method: 'PUT',
      body: event.data
    })
    
    // Update local user state
    setUser(data)
    
    toast.add({
      title: 'Success',
      description: 'Your profile has been updated.',
      icon: 'i-lucide-check',
      color: 'success'
    })
    
    // Clear password field after successful update
    profile.password = ''
  } catch (error: any) {
    toast.add({
      title: 'Error',
      description: error.data?.message || 'Failed to update profile',
      icon: 'i-lucide-alert-circle',
      color: 'error'
    })
  }
}
</script>

<template>
  <UForm
    id="settings"
    :schema="profileSchema"
    :state="profile"
    @submit="onSubmit"
  >
    <UPageCard
      title="Profile"
      description="These informations will be displayed publicly."
      variant="naked"
      orientation="horizontal"
      class="mb-4"
    >
      <UButton
        form="settings"
        label="Save changes"
        color="neutral"
        type="submit"
        class="w-fit lg:ms-auto"
      />
    </UPageCard>

    <UPageCard variant="subtle">
      <UFormField
        name="name"
        label="Name"
        description="Will appear on receipts, invoices, and other communication."
        required
        class="flex max-sm:flex-col justify-between items-start gap-4"
      >
        <UInput
          v-model="profile.name"
          autocomplete="off"
        />
      </UFormField>
      <USeparator />
      <UFormField
        name="email"
        label="Email"
        description="Used to sign in, for email receipts and product updates."
        required
        class="flex max-sm:flex-col justify-between items-start gap-4"
      >
        <UInput
          v-model="profile.email"
          type="email"
          autocomplete="off"
        />
      </UFormField>
      <USeparator />
      <UFormField
        name="password"
        label="New Password"
        description="Leave blank if you don't want to change your password."
        class="flex max-sm:flex-col justify-between items-start gap-4"
      >
        <UInput
          v-model="profile.password"
          type="password"
          autocomplete="new-password"
        />
      </UFormField>
    </UPageCard>
  </UForm>
</template>
