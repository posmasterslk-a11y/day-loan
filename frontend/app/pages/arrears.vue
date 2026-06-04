<script setup lang="ts">
const config = useRuntimeConfig()
const toast = useToast()

const officerId = ref(undefined)
const { data: users } = await useApiFetch<any[]>(`${config.public.apiBase}/users`)

const { data: arrears, status } = await useApiFetch<any[]>(() => 
  `${config.public.apiBase}/arrears${officerId.value ? `?officer_id=${officerId.value}` : ''}`
)

const columns = [
  { accessorKey: 'customer', header: 'Customer' },
  { accessorKey: 'nic', header: 'NIC' },
  { accessorKey: 'phone', header: 'Phone' },
  { accessorKey: 'loan_number', header: 'Loan No' },
  { 
    accessorKey: 'days_in_arrears', 
    header: 'Days Overdue',
    cell: ({ row }: any) => h(resolveComponent('UBadge'), { color: 'error', variant: 'soft' }, () => `${row.original.days_in_arrears} Days`)
  },
  { 
    accessorKey: 'total_arrears_amount', 
    header: 'Total Arrears (Rs)',
    cell: ({ row }: any) => h('span', { class: 'text-error font-semibold' }, `Rs. ${row.original.total_arrears_amount}`)
  }
]
</script>

<template>
  <UDashboardPanel>
    <UDashboardNavbar title="Arrears Management">
      <template #leading>
        <UDashboardSidebarCollapse />
      </template>
      <template #right>
        <div class="flex items-center gap-2">
           <span class="text-sm text-muted-foreground">Officer View:</span>
           <USelect v-model="officerId" :items="users?.map(u => ({ label: u.name, value: u.id }))" placeholder="All Officers" class="w-48" clearable />
        </div>
      </template>
    </UDashboardNavbar>
    
    <UDashboardPanelContent>
      <div v-if="arrears?.length === 0" class="flex flex-col items-center justify-center min-h-[50vh] text-center border border-dashed border-gray-200 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-gray-900/50 m-4">
         <div class="p-4 bg-success/10 rounded-full mb-4">
            <UIcon name="i-lucide-shield-check" class="w-16 h-16 text-success" />
         </div>
         <h3 class="text-2xl font-semibold mb-2">Excellent!</h3>
         <p class="text-muted-foreground text-lg max-w-sm mx-auto">There are no customers currently in arrears. Everything is running smoothly!</p>
      </div>
      
      <div v-else>
         <UTable :data="arrears" :columns="columns" :loading="status === 'pending'" />
      </div>
    </UDashboardPanelContent>
  </UDashboardPanel>
</template>
