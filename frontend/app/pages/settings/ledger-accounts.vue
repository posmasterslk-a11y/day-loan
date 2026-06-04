<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'

const config = useRuntimeConfig()
const toast = useToast()

const { data, pending, refresh } = await useApiFetch<any[]>(`${config.public.apiBase}/ledger-accounts`)

const isEditModalOpen = ref(false)
const isDeleting = ref(false)
const selectedAccount = ref<any>(null)

function openEdit(row: any) {
  selectedAccount.value = { ...row }
  isEditModalOpen.value = true
}

async function updateAccount() {
  if (!selectedAccount.value.narration) return

  try {
    await $fetch(`${config.public.apiBase}/ledger-accounts/${selectedAccount.value.id}`, {
      method: 'PUT',
      body: { narration: selectedAccount.value.narration, type: selectedAccount.value.type }
    })
    toast.add({ title: 'Account updated successfully', color: 'success' })
    isEditModalOpen.value = false
    refresh()
  } catch (e: any) {
    toast.add({ title: e.data?.message || 'Error updating account', color: 'error' })
  }
}

async function deleteAccount(id: number) {
  if (!confirm('Are you sure you want to delete this ledger account?')) return
  
  try {
    await $fetch(`${config.public.apiBase}/ledger-accounts/${id}`, {
      method: 'DELETE'
    })
    toast.add({ title: 'Account deleted', color: 'success' })
    refresh()
  } catch (e: any) {
    toast.add({ title: 'Error deleting account. It may be in use.', color: 'error' })
  }
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Ledger Accounts</h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage your narrations for the general ledger.</p>
    </div>

    <UCard :ui="{ ring: '', shadow: 'shadow-sm' }" class="ring-1 ring-gray-200 dark:ring-gray-800">
      <UTable
        :data="data || []"
        :loading="pending"
        :columns="[
          { accessorKey: 'id', header: 'ID' },
          { accessorKey: 'narration', header: 'Narration' },
          { accessorKey: 'type', header: 'Type' },
          { id: 'actions', header: '' }
        ]"
      >
        <template #type-cell="{ row }">
          <UBadge :color="row.original.type === 'DR' ? 'success' : 'error'" variant="subtle">
            {{ row.original.type === 'DR' ? 'DR (Income/In)' : 'CR (Expense/Out)' }}
          </UBadge>
        </template>
        <template #actions-cell="{ row }">
          <div class="flex items-center justify-end gap-2">
            <UButton icon="i-lucide-pencil" color="neutral" variant="ghost" size="sm" @click="openEdit(row.original)" />
            <UButton icon="i-lucide-trash" color="error" variant="ghost" size="sm" @click="deleteAccount(row.original.id)" />
          </div>
        </template>
      </UTable>
    </UCard>

    <!-- Edit Modal -->
    <UModal v-model:open="isEditModalOpen" title="Edit Ledger Account" description="Update the narration details">
      <template #body>
        <form @submit.prevent="updateAccount" class="space-y-4" v-if="selectedAccount">
          <UFormField label="Narration" name="narration" required>
            <UInput v-model="selectedAccount.narration" class="w-full" required />
          </UFormField>
          <UFormField label="Type" name="type" required>
            <USelect v-model="selectedAccount.type" :items="[{label: 'Dr (Income / Cash In)', value: 'DR'}, {label: 'Cr (Expense / Cash Out)', value: 'CR'}]" class="w-full" required />
          </UFormField>

          <div class="flex justify-end gap-3 pt-4">
            <UButton label="Cancel" color="neutral" variant="ghost" @click="isEditModalOpen = false" />
            <UButton type="submit" label="Save Changes" color="primary" />
          </div>
        </form>
      </template>
    </UModal>
  </div>
</template>
