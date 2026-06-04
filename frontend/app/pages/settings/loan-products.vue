<script setup lang="ts">
import * as z from 'zod'

const config = useRuntimeConfig()
const toast = useToast()

const { data: products, refresh, status } = await useApiFetch<any[]>(`${config.public.apiBase}/loan-products`)

const columns = [
  { accessorKey: 'id', header: 'ID' },
  { accessorKey: 'name', header: 'Product Name' },
  { accessorKey: 'min_amount', header: 'Min Amount' },
  { accessorKey: 'max_amount', header: 'Max Amount' },
  { accessorKey: 'interest_rate', header: 'Interest (%)' },
  { accessorKey: 'duration_days', header: 'Duration (Days)' }
]

const schema = z.object({
  name: z.string().min(2, 'Name is required'),
  min_amount: z.number().min(0),
  max_amount: z.number().min(0),
  interest_rate: z.number().min(0),
  duration_days: z.number().min(1)
}).refine(data => data.max_amount >= data.min_amount, {
  message: "Max amount must be greater than or equal to min amount",
  path: ["max_amount"]
})

const state = reactive({
  name: '',
  min_amount: 10000,
  max_amount: 50000,
  interest_rate: 20,
  duration_days: 60
})

const isModalOpen = ref(false)

async function onSubmit(event: any) {
  try {
    await useApi()(`${config.public.apiBase}/loan-products`, {
      method: 'POST',
      body: event.data
    })
    toast.add({ title: 'Success', description: 'Loan product added', color: 'success' })
    isModalOpen.value = false
    refresh()
  } catch (error: any) {
    toast.add({ title: 'Error', description: 'Failed to add product', color: 'error' })
  }
}
</script>

<template>
  <UDashboardPanel>
    <UDashboardNavbar title="Loan Products">
      <template #leading>
        <UDashboardSidebarCollapse />
      </template>
      <template #right>
        <UButton label="New Product" icon="i-lucide-plus" @click="isModalOpen = true" />
      </template>
    </UDashboardNavbar>
    
    <UDashboardPanelContent>
      <UTable :data="products" :columns="columns" :loading="status === 'pending'" />
    </UDashboardPanelContent>

    <UModal v-model:open="isModalOpen" title="New Loan Product">
      <template #body>
        <UForm :schema="schema" :state="state" @submit="onSubmit" class="space-y-4">
          <UFormField label="Product Name" name="name">
            <UInput v-model="state.name" class="w-full" placeholder="e.g. 60-Day Flex Loan" />
          </UFormField>
          
          <div class="grid grid-cols-2 gap-4">
            <UFormField label="Min Amount" name="min_amount">
              <UInput v-model="state.min_amount" type="number" class="w-full" />
            </UFormField>
            <UFormField label="Max Amount" name="max_amount">
              <UInput v-model="state.max_amount" type="number" class="w-full" />
            </UFormField>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <UFormField label="Interest Rate (%)" name="interest_rate">
              <UInput v-model="state.interest_rate" type="number" class="w-full" />
            </UFormField>
            <UFormField label="Duration (Days)" name="duration_days">
              <UInput v-model="state.duration_days" type="number" class="w-full" />
            </UFormField>
          </div>
          
          <div class="flex justify-end gap-2 mt-4">
            <UButton label="Cancel" color="neutral" variant="subtle" @click="isModalOpen = false" />
            <UButton type="submit" label="Save Product" />
          </div>
        </UForm>
      </template>
    </UModal>
  </UDashboardPanel>
</template>
