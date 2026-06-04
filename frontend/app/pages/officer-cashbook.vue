<script setup lang="ts">
const config = useRuntimeConfig()
const toast = useToast()

const officerId = ref<string | number>('all') 
const { data: users } = await useApiFetch<any[]>(`${config.public.apiBase}/users`)

const { data: transactions, refresh, status } = await useApiFetch<any[]>(() => 
  `${config.public.apiBase}/ledger/officer?officer_id=${officerId.value}`
)

const submitState = reactive({
  amount: 0,
})

const columns = [
  { accessorKey: 'date', header: 'Date' },
  { accessorKey: 'account_type', header: 'Account' },
  { accessorKey: 'description', header: 'Description' },
  { accessorKey: 'amount', header: 'Amount' },
  { accessorKey: 'dr', header: 'DR (In)' },
  { accessorKey: 'cr', header: 'CR (Out)' }
]

const summaryColumns = [
  { accessorKey: 'name', header: 'Officer Name' },
  { accessorKey: 'total_collected', header: 'Total Collected (DR)' },
  { accessorKey: 'total_submitted', header: 'Total Submitted (CR)' },
  { accessorKey: 'balance', header: 'Cash on Hand (Balance)' }
]

const officerSummary = computed(() => {
  if (officerId.value !== 'all' || !transactions.value) return []
  
  const map = new Map()
  transactions.value.forEach(t => {
    const uid = t.user_id
    if (!map.has(uid)) {
      map.set(uid, { name: t.user?.name || 'Unknown', dr: 0, cr: 0 })
    }
    const data = map.get(uid)
    data.dr += parseFloat(t.dr)
    data.cr += parseFloat(t.cr)
  })

  return Array.from(map.values()).map(d => ({
    name: d.name,
    total_collected: d.dr.toFixed(2),
    total_submitted: d.cr.toFixed(2),
    balance: (d.dr - d.cr).toFixed(2)
  }))
})

const totalDr = computed(() => transactions.value?.reduce((sum, t) => sum + parseFloat(t.dr), 0) || 0)
const totalCr = computed(() => transactions.value?.reduce((sum, t) => sum + parseFloat(t.cr), 0) || 0)
const balance = computed(() => totalDr.value - totalCr.value)

async function onSubmitCash() {
  if(!submitState.amount || submitState.amount > balance.value) {
    toast.add({ title: 'Error', description: 'Invalid amount. Cannot exceed balance.', color: 'error' })
    return
  }

  try {
    await $fetch(`${config.public.apiBase}/ledger/submit`, {
      method: 'POST',
      body: {
        amount: submitState.amount,
        officer_id: officerId.value
      }
    })
    toast.add({ title: 'Success', description: 'Cash submitted to Main Branch!', color: 'success' })
    submitState.amount = 0
    refresh()
  } catch (error: any) {
    toast.add({ title: 'Error', description: error.data?.message || 'Failed to submit', color: 'error' })
  }
}
</script>

<template>
  <UDashboardPanel>
    <UDashboardNavbar title="Officer Cash Book">
      <template #leading>
        <UDashboardSidebarCollapse />
      </template>
      <template #right>
        <div class="flex items-center gap-2 bg-muted/50 px-3 py-1.5 rounded-full border border-default">
           <UIcon name="i-lucide-users" class="w-4 h-4 text-muted-foreground" />
           <span class="text-sm text-muted-foreground">View:</span>
           <USelect 
              v-model="officerId" 
              :items="[{label: 'All Officers Summary', value: 'all'}, ...(users?.map(u => ({ label: u.name, value: u.id })) || [])]" 
              variant="none" 
              class="w-48 !p-0 font-medium" 
           />
        </div>
      </template>
    </UDashboardNavbar>
    
    <UDashboardPanelContent class="bg-muted/30">
      
      <!-- ALL OFFICERS SUMMARY VIEW -->
      <div v-if="officerId === 'all'">
         <UCard class="shadow-sm ring-0 border border-default">
            <template #header>
               <h3 class="font-semibold text-lg flex items-center gap-2">
                  <UIcon name="i-lucide-users" class="w-5 h-5 text-primary" />
                  All Officers Cash Summary
               </h3>
               <p class="text-sm text-muted-foreground mt-1">Overview of how much cash each officer is currently holding on hand.</p>
            </template>
            <UTable :data="officerSummary" :columns="summaryColumns" :loading="status === 'pending'" class="w-full">
               <template #balance-data="{ row }">
                  <span :class="parseFloat(row.balance) > 0 ? 'text-primary font-bold' : 'text-muted-foreground'">
                     Rs. {{ row.balance }}
                  </span>
               </template>
            </UTable>
         </UCard>
      </div>

      <!-- SINGLE OFFICER WALLET VIEW -->
      <div v-else>
         <!-- Wallet Card & Action -->
         <div class="mb-8">
            <div class="bg-gradient-to-r from-primary to-primary/80 rounded-2xl p-8 text-primary-content shadow-lg relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-8">
               <div class="absolute right-0 top-0 opacity-10 pointer-events-none">
                  <UIcon name="i-lucide-banknote" class="w-64 h-64 transform translate-x-12 -translate-y-12 rotate-12" />
               </div>
               
               <div class="relative z-10">
                  <p class="text-primary-content/80 font-medium uppercase tracking-wider text-sm mb-2 flex items-center gap-2">
                     <UIcon name="i-lucide-wallet" class="w-5 h-5" />
                     Available Cash on Hand
                  </p>
                  <h2 class="text-5xl font-black tracking-tight">Rs. {{ balance.toLocaleString(undefined, {minimumFractionDigits: 2}) }}</h2>
                  <p class="text-primary-content/70 text-sm mt-3 flex items-center gap-1">
                     <UIcon name="i-lucide-info" class="w-4 h-4" />
                     This cash must be handed over to the branch manager daily.
                  </p>
               </div>

               <!-- Submit Form inside Wallet -->
               <div class="bg-background/10 backdrop-blur-md rounded-xl p-6 border border-white/20 relative z-10 w-full md:w-auto">
                  <h3 class="text-lg font-semibold mb-4">Handover Cash</h3>
                  <div class="flex flex-col sm:flex-row gap-3 items-end">
                     <UFormField label="Amount to Submit (Rs)" class="w-full sm:w-64" :ui="{ label: 'text-primary-content/90' }">
                        <UInput 
                           type="number" 
                           size="xl" 
                           v-model="submitState.amount" 
                           icon="i-lucide-coins" 
                           class="bg-background text-foreground"
                        />
                     </UFormField>
                     <UButton 
                        label="Submit" 
                        color="white" 
                        variant="solid" 
                        size="xl" 
                        icon="i-lucide-arrow-right" 
                        @click="onSubmitCash" 
                        class="font-bold text-primary hover:bg-white/90 w-full sm:w-auto justify-center"
                     />
                  </div>
               </div>
            </div>
         </div>

         <!-- Ledger Table -->
         <UCard class="shadow-sm ring-0 border border-default">
            <template #header>
               <h3 class="font-semibold text-lg flex items-center gap-2">
                  <UIcon name="i-lucide-history" class="w-5 h-5 text-primary" />
                  Transaction History
               </h3>
            </template>
            <UTable :data="transactions" :columns="columns" :loading="status === 'pending'" class="w-full" />
         </UCard>
      </div>

    </UDashboardPanelContent>
  </UDashboardPanel>
</template>
