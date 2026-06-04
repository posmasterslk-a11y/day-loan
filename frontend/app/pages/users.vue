<script setup lang="ts">
import * as z from 'zod'

const config = useRuntimeConfig()
const toast = useToast()

const { data: users, refresh, status } = await useApiFetch<any[]>(`${config.public.apiBase}/users`)
const { data: branches } = await useApiFetch<any[]>(`${config.public.apiBase}/branches`)
const { data: roles } = await useApiFetch<any[]>(`${config.public.apiBase}/roles`)

const columns = [
  { accessorKey: 'id', header: 'ID' },
  { accessorKey: 'name', header: 'Name' },
  { accessorKey: 'email', header: 'Email' },
  { accessorKey: 'role.name', header: 'Role' },
  { accessorKey: 'branch.name', header: 'Branch' },
  { id: 'actions', header: '' }
]

const schema = z.object({
  name: z.string().min(2, 'Name is required'),
  email: z.string().email(),
  password: z.string().min(6),
  role_id: z.coerce.number(),
  branch_id: z.coerce.number().optional()
})

const state = reactive({ name: '', email: '', password: '', role_id: undefined, branch_id: undefined })
const isModalOpen = ref(false)
const isDeleteModalOpen = ref(false)
const userToDelete = ref<number | null>(null)

function confirmDelete(id: number) {
  userToDelete.value = id
  isDeleteModalOpen.value = true
}

async function deleteUser() {
  if (!userToDelete.value) return
  
  try {
    const { token } = useAuth()
    await useApi()(`${config.public.apiBase}/users/${userToDelete.value}`, {
      method: 'DELETE',
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })
    toast.add({ title: 'Success', description: 'User deleted successfully', color: 'success' })
    isDeleteModalOpen.value = false
    refresh()
  } catch (e: any) {
    toast.add({ title: 'Error', description: 'Failed to delete user', color: 'error' })
  }
}

async function onSubmit(event: any) {
  try {
    const { token } = useAuth()
    await useApi()(`${config.public.apiBase}/users`, {
      method: 'POST',
      body: event.data,
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })
    toast.add({ title: 'Success', description: 'User created successfully', color: 'success' })
    isModalOpen.value = false
    refresh()
  } catch (e: any) {
    toast.add({ title: 'Error', description: 'Failed to create user', color: 'error' })
  }
}
</script>

<template>
  <UDashboardPanel>
    <UDashboardNavbar title="System Users">
      <template #right>
        <UButton label="New User" icon="i-lucide-plus" @click="isModalOpen = true" />
      </template>
    </UDashboardNavbar>
    
    <UDashboardPanelContent>
      <UTable :data="users" :columns="columns" :loading="status === 'pending'">
        <template #actions-cell="{ row }">
          <div class="flex items-center justify-end gap-2">
            <UButton icon="i-lucide-trash" color="error" variant="ghost" size="sm" @click="confirmDelete(row.original.id)" />
          </div>
        </template>
      </UTable>
    </UDashboardPanelContent>

    <UModal v-model:open="isModalOpen" title="Add User">
      <template #body>
        <UForm :schema="schema" :state="state" @submit="onSubmit" class="space-y-4">
          <UFormField label="Name" name="name">
            <UInput v-model="state.name" class="w-full" />
          </UFormField>
          <UFormField label="Email" name="email">
            <UInput v-model="state.email" type="email" class="w-full" />
          </UFormField>
          <UFormField label="Password" name="password">
            <UInput v-model="state.password" type="password" class="w-full" />
          </UFormField>
          <UFormField label="Role" name="role_id">
            <USelect v-model="state.role_id" :items="roles?.map(r => ({ label: r.name, value: r.id }))" class="w-full" />
          </UFormField>
          <UFormField label="Branch" name="branch_id">
            <USelect v-model="state.branch_id" :items="branches?.map(b => ({ label: b.name, value: b.id }))" class="w-full" />
          </UFormField>
          <div class="flex justify-end">
            <UButton type="submit" label="Create User" />
          </div>
        </UForm>
      </template>
    </UModal>

    <UModal v-model:open="isDeleteModalOpen" title="Confirm Delete" description="Are you sure you want to delete this user? This action cannot be undone.">
      <template #body>
        <div class="flex justify-end gap-3 pt-4">
          <UButton label="Cancel" color="neutral" variant="ghost" @click="isDeleteModalOpen = false" />
          <UButton label="Delete" color="error" @click="deleteUser" />
        </div>
      </template>
    </UModal>
  </UDashboardPanel>
</template>
