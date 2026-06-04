<script setup lang="ts">
import type { FormSubmitEvent } from '@nuxt/ui'
import * as z from 'zod'

const config = useRuntimeConfig()
const toast = useToast()

const { data: banks, refresh, status } = await useApiFetch<any[]>(`${config.public.apiBase}/banks`)

const columns = [
  { accessorKey: 'name', header: 'Bank Name' },
  { accessorKey: 'account_number', header: 'Account Number' },
  { accessorKey: 'branch', header: 'Branch' },
  {
    id: 'actions',
    header: '',
    cell: ({ row }: any) => {
      return h(resolveComponent('UButton'), {
        icon: 'i-lucide-trash-2',
        color: 'error',
        variant: 'ghost',
        onClick: () => deleteBank(row.original.id)
      })
    }
  }
]

const schema = z.object({
  name: z.string().min(2, 'Name is required'),
  account_number: z.string().optional(),
  branch: z.string().optional()
})

type Schema = z.output<typeof schema>
const state = reactive<Partial<Schema>>({ name: '', account_number: '', branch: '' })
const isModalOpen = ref(false)

async function onSubmit(event: FormSubmitEvent<Schema>) {
  try {
    await useApi()(`${config.public.apiBase}/banks`, {
      method: 'POST',
      body: event.data
    })
    toast.add({ title: 'Success', description: 'Bank registered successfully', color: 'success' })
    isModalOpen.value = false
    refresh()
    state.name = ''
    state.account_number = ''
    state.branch = ''
  } catch (error: any) {
    toast.add({ title: 'Error', description: error.data?.message || 'Failed to save', color: 'error' })
  }
}

async function deleteBank(id: number) {
  if (!confirm('Are you sure you want to delete this bank?')) return;
  try {
    await useApi()(`${config.public.apiBase}/banks/${id}`, { method: 'DELETE' })
    toast.add({ title: 'Deleted', description: 'Bank removed.', color: 'success' })
    refresh()
  } catch (error) {
    toast.add({ title: 'Error', description: 'Failed to delete.', color: 'error' })
  }
}
</script>

<template>
  <UDashboardPanelContent class="p-0 pb-24 sm:pb-24">
    <UDashboardSection
      title="Registered Banks"
      description="Manage bank accounts for cash deposits."
    >
      <div class="flex justify-end mb-4">
        <UButton label="Add Bank" color="primary" icon="i-lucide-plus" @click="isModalOpen = true" />
      </div>
      
      <UTable :data="banks" :columns="columns" :loading="status === 'pending'" class="border rounded-md" />
    </UDashboardSection>

    <UModal v-model:open="isModalOpen" title="Register New Bank">
      <template #body>
        <UForm :schema="schema" :state="state" @submit="onSubmit" class="space-y-4">
          <UFormField label="Bank Name" name="name">
            <UInput v-model="state.name" class="w-full" placeholder="e.g. Commercial Bank" />
          </UFormField>
          <UFormField label="Account Number" name="account_number">
            <UInput v-model="state.account_number" class="w-full" />
          </UFormField>
          <UFormField label="Branch" name="branch">
            <UInput v-model="state.branch" class="w-full" />
          </UFormField>
          
          <div class="flex justify-end gap-2 mt-4">
            <UButton label="Cancel" color="neutral" variant="subtle" @click="isModalOpen = false" />
            <UButton type="submit" label="Save Bank" color="primary" />
          </div>
        </UForm>
      </template>
    </UModal>
  </UDashboardPanelContent>
</template>
