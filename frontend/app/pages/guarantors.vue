<script setup lang="ts">
import * as z from 'zod'
import { upperFirst } from 'scule'

const config = useRuntimeConfig()
const toast = useToast()

const { data: guarantors, refresh, status } = await useApiFetch<any[]>(`${config.public.apiBase}/guarantors`)

const columns = [
  { accessorKey: 'id', header: 'ID' },
  { accessorKey: 'full_name', header: 'Name' },
  { accessorKey: 'nic', header: 'NIC' },
  { accessorKey: 'phone', header: 'Phone' },
  { accessorKey: 'relationship', header: 'Relationship' }
]

const schema = z.object({
  full_name: z.string().min(2, 'Name is required'),
  nic: z.string().min(10, 'NIC is too short'),
  phone: z.string().min(10, 'Phone is too short'),
  address: z.string().optional(),
  relationship: z.string().optional()
})

const state = reactive({
  full_name: '',
  nic: '',
  phone: '',
  address: '',
  relationship: ''
})

const isModalOpen = ref(false)
const fileInputs = ref({ photo: null as File | null })

function handleFileChange(event: Event) {
  const target = event.target as HTMLInputElement
  if (target.files?.length) {
    fileInputs.value.photo = target.files[0]
  }
}

async function onSubmit(event: any) {
  try {
    const formData = new FormData()
    Object.entries(event.data).forEach(([key, value]) => {
      if (value !== undefined && value !== null) {
        formData.append(key, value.toString())
      }
    })
    
    if (fileInputs.value.photo) formData.append('photo', fileInputs.value.photo)

    await useApi()(`${config.public.apiBase}/guarantors`, {
      method: 'POST',
      body: formData
    })

    toast.add({ title: 'Success', description: 'Guarantor added successfully', color: 'success' })
    isModalOpen.value = false
    refresh()
  } catch (error: any) {
    toast.add({ title: 'Error', description: error.data?.message || 'Failed to add guarantor', color: 'error' })
  }
}
</script>

<template>
  <UDashboardPanel>
    <UDashboardNavbar title="Guarantors">
      <template #leading>
        <UDashboardSidebarCollapse />
      </template>
      <template #right>
        <UButton label="New Guarantor" icon="i-lucide-plus" @click="isModalOpen = true" />
      </template>
    </UDashboardNavbar>
    
    <UDashboardPanelContent>
      <UTable :data="guarantors" :columns="columns" :loading="status === 'pending'" />
    </UDashboardPanelContent>

    <UModal v-model:open="isModalOpen" title="New Guarantor" description="Register a new guarantor">
      <template #body>
        <UForm :schema="schema" :state="state" @submit="onSubmit" class="space-y-4">
          <UFormField label="Full Name" name="full_name">
            <UInput v-model="state.full_name" class="w-full" />
          </UFormField>
          <UFormField label="NIC" name="nic">
            <UInput v-model="state.nic" class="w-full" />
          </UFormField>
          <UFormField label="Phone" name="phone">
            <UInput v-model="state.phone" class="w-full" />
          </UFormField>
          <UFormField label="Relationship (to borrower)" name="relationship">
            <UInput v-model="state.relationship" class="w-full" placeholder="e.g. Brother, Friend" />
          </UFormField>
          <UFormField label="Address" name="address">
            <UTextarea v-model="state.address" class="w-full" />
          </UFormField>
          <UFormField label="Photo" name="photo">
            <input type="file" accept="image/*" @change="handleFileChange" />
          </UFormField>
          
          <div class="flex justify-end gap-2 mt-4">
            <UButton label="Cancel" color="neutral" variant="subtle" @click="isModalOpen = false" />
            <UButton type="submit" label="Save Guarantor" />
          </div>
        </UForm>
      </template>
    </UModal>
  </UDashboardPanel>
</template>
