<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'

const schema = z.object({
  default_interest_rate: z.number().min(0, 'Interest rate cannot be negative').max(100, 'Interest rate cannot exceed 100')
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  default_interest_rate: 5.0
})

const toast = useToast()
const config = useRuntimeConfig()

// Fetch existing settings
const { data, status } = await useApiFetch<Record<string, string>>(`${config.public.apiBase}/settings`)
if (data.value && data.value.default_interest_rate) {
  state.default_interest_rate = parseFloat(data.value.default_interest_rate)
}

async function onSubmit(event: FormSubmitEvent<Schema>) {
  try {
    await $fetch(`${config.public.apiBase}/settings`, {
      method: 'POST',
      body: {
        settings: {
          default_interest_rate: event.data.default_interest_rate.toString()
        }
      }
    })
    
    toast.add({
      title: 'Success',
      description: 'System settings have been updated.',
      icon: 'i-lucide-check',
      color: 'success'
    })
  } catch (error: any) {
    toast.add({
      title: 'Error',
      description: error.data?.message || 'Failed to update settings',
      color: 'error'
    })
  }
}
</script>

<template>
  <UForm
    id="system-settings"
    :schema="schema"
    :state="state"
    @submit="onSubmit"
  >
    <UPageCard
      title="System Settings"
      description="Configure global configurations for the application."
      variant="naked"
      orientation="horizontal"
      class="mb-4"
    >
      <UButton
        form="system-settings"
        label="Save changes"
        color="neutral"
        type="submit"
        class="w-fit lg:ms-auto"
      />
    </UPageCard>

    <UPageCard variant="subtle">
      <UFormField
        name="default_interest_rate"
        label="Default Interest Rate (%)"
        description="The global default interest rate applied to new loans."
        required
        class="flex max-sm:flex-col justify-between items-start gap-4"
      >
        <UInput
          v-model="state.default_interest_rate"
          type="number"
          step="0.01"
          class="w-full sm:w-64"
        />
      </UFormField>
    </UPageCard>
  </UForm>
</template>
