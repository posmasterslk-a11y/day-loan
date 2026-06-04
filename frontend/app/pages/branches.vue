<script setup lang="ts">
import * as z from 'zod'

const config = useRuntimeConfig()
const toast = useToast()

const { data: branches, refresh, status } = await useApiFetch<any[]>(`${config.public.apiBase}/branches`)

const columns = [{ accessorKey: 'id', header: 'ID' }, { accessorKey: 'name', header: 'Name' }, { accessorKey: 'location', header: 'Location' }]

const schema = z.object({
  name: z.string().min(2, 'Name is required'),
  location: z.string().optional()
})

const state = reactive({ name: '', location: '' })
const isModalOpen = ref(false)

async function onSubmit(event: any) {
  try {
    await $fetch(`${config.public.apiBase}/branches`, {
      method: 'POST',
      body: event.data
    })
    toast.add({ title: 'Success', description: 'Branch added successfully', color: 'success' })
    isModalOpen.value = false
    state.name = ''
    state.location = ''
    refresh()
  } catch (e: any) {
    toast.add({ title: 'Error', description: 'Failed to add branch', color: 'error' })
  }
}
</script>

<template>
  <UDashboardPanel>
    <UDashboardNavbar title="Branches">
      <template #right>
        <UButton label="New Branch" icon="i-lucide-plus" @click="isModalOpen = true" />
      </template>
    </UDashboardNavbar>
    
    <UDashboardPanelContent>
      <UTable :data="branches" :columns="columns" :loading="status === 'pending'" />
    </UDashboardPanelContent>

    <UModal v-model:open="isModalOpen" title="Add Branch">
      <template #body>
        <UForm :schema="schema" :state="state" @submit="onSubmit" class="space-y-4">
          <UFormField label="Branch Name" name="name">
            <UInput v-model="state.name" class="w-full" />
          </UFormField>
          <UFormField label="Location" name="location">
            <UInput v-model="state.location" class="w-full" />
          </UFormField>
          <div class="flex justify-end">
            <UButton type="submit" label="Save Branch" />
          </div>
        </UForm>
      </template>
    </UModal>
  </UDashboardPanel>
</template>
