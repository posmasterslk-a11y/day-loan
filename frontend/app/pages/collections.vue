<script setup lang="ts">
import * as z from 'zod'

const config = useRuntimeConfig()
const toast = useToast()

const officerId = ref(undefined)
const searchQuery = ref('')
const { data: users } = await useApiFetch<any[]>(`${config.public.apiBase}/users`)

// Re-fetch schedules when officer or search changes
const { data: schedules, refresh, status } = await useApiFetch<any[]>(() => 
  `${config.public.apiBase}/collections?officer_id=${officerId.value || ''}&search=${searchQuery.value || ''}`
)

watch(searchQuery, () => refresh())

const columns = [
  { accessorKey: 'due_date', header: 'Due Date' },
  { accessorKey: 'loan.customer.full_name', header: 'Customer' },
  { accessorKey: 'loan.loan_number', header: 'Loan No' },
  { accessorKey: 'amount_due', header: 'Amount Due' },
  { 
    accessorKey: 'status', 
    header: 'Status',
    cell: ({ row }: any) => {
      const color = row.original.status === 'Partial' ? 'warning' : (row.original.status === 'Pending' ? 'error' : 'success');
      return h(resolveComponent('UBadge'), { color, variant: 'subtle' }, () => row.original.status)
    }
  },
  { 
    id: 'actions', 
    header: '',
    cell: ({ row }: any) => {
      return h(resolveComponent('UButton'), { 
        label: 'Collect Cash', 
        size: 'xs', 
        color: 'primary',
        onClick: () => openPaymentModal(row.original)
      })
    }
  }
]

const isPaymentModalOpen = ref(false)
const selectedSchedule = ref<any>(null)

const state = reactive({
  amount: 0,
  officer_id: undefined
})

function openPaymentModal(schedule: any) {
  selectedSchedule.value = schedule
  state.amount = schedule.amount_due
  state.officer_id = officerId.value
  isPaymentModalOpen.value = true
}

async function onSubmit(event: any) {
  if (!state.officer_id) {
    toast.add({ title: 'Error', description: 'Please select an officer first', color: 'error' })
    return
  }

  try {
    await useApi()(`${config.public.apiBase}/collections`, {
      method: 'POST',
      body: {
        loan_schedule_id: selectedSchedule.value.id,
        amount: state.amount,
        officer_id: state.officer_id
      }
    })
    toast.add({ title: 'Success', description: 'Payment collected successfully!', color: 'success' })
    isPaymentModalOpen.value = false
    refresh()
  } catch (error: any) {
    toast.add({ title: 'Error', description: error.data?.message || 'Failed to collect payment', color: 'error' })
  }
}
</script>

<template>
  <UDashboardPanel>
    <UDashboardNavbar title="Daily Collections">
      <template #leading>
        <UDashboardSidebarCollapse />
      </template>
      <template #right>
        <div class="flex items-center gap-4">
           <UInput v-model="searchQuery" icon="i-lucide-search" placeholder="Search to accept advance payments..." class="w-64" clearable />
           <div class="flex items-center gap-2">
              <span class="text-sm text-muted-foreground hidden sm:inline">Officer View:</span>
              <USelect v-model="officerId" :items="users?.map(u => ({ label: u.name, value: u.id }))" placeholder="All Officers" class="w-40 sm:w-48" clearable />
           </div>
        </div>
      </template>
    </UDashboardNavbar>
    
    <UDashboardPanelContent>
      <div v-if="schedules?.length === 0" class="flex flex-col items-center justify-center min-h-[50vh] text-center border border-dashed border-gray-200 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-gray-900/50 m-4">
         <div class="p-4 bg-success/10 rounded-full mb-4">
            <UIcon name="i-lucide-check-circle-2" class="w-16 h-16 text-success" />
         </div>
         <h3 class="text-2xl font-semibold mb-2">All Caught Up!</h3>
         <p class="text-muted-foreground text-lg max-w-sm mx-auto">There are no pending collections for today. Great job keeping everything on track!</p>
      </div>
      <UTable v-else :data="schedules" :columns="columns" :loading="status === 'pending'" />
    </UDashboardPanelContent>

    <UModal v-model:open="isPaymentModalOpen" title="Collect Payment" v-if="selectedSchedule">
      <template #body>
        <UForm :state="state" @submit="onSubmit" class="space-y-4">
          
          <div class="p-4 bg-muted/50 rounded-lg space-y-2 border border-default text-sm">
             <div class="flex justify-between">
                <span class="text-muted-foreground">Customer:</span>
                <span class="font-medium">{{ selectedSchedule.loan.customer.full_name }}</span>
             </div>
             <div class="flex justify-between">
                <span class="text-muted-foreground">Loan No:</span>
                <span class="font-medium">{{ selectedSchedule.loan.loan_number }}</span>
             </div>
             <div class="flex justify-between border-t border-default pt-2 mt-2">
                <span class="text-muted-foreground">Total Balance Remaining:</span>
                <span class="font-medium">Rs. {{ selectedSchedule.loan.total_payable }}</span>
             </div>
             <div class="flex justify-between font-bold border-t border-default pt-2 text-base">
                <span>Amount Due Today:</span>
                <span class="text-error">Rs. {{ selectedSchedule.amount_due }}</span>
             </div>
          </div>
          
          <UFormField label="Amount Received (Rs)" name="amount" help="Enter the actual cash amount received (Partial allowed).">
            <UInput v-model="state.amount" type="number" class="w-full text-xl" />
          </UFormField>

          <UFormField label="Collected By" name="officer_id">
            <USelect v-model="state.officer_id" :items="users?.map(u => ({ label: u.name, value: u.id }))" class="w-full" placeholder="Select acting officer..." />
          </UFormField>
          
          <div class="flex justify-end gap-2 mt-4">
            <UButton label="Cancel" color="neutral" variant="subtle" @click="isPaymentModalOpen = false" />
            <UButton type="submit" label="Confirm Payment" color="success" icon="i-lucide-banknote" />
          </div>
        </UForm>
      </template>
    </UModal>
  </UDashboardPanel>
</template>
