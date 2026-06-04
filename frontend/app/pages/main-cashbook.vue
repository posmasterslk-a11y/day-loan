<script setup lang="ts">
const config = useRuntimeConfig()
const toast = useToast()

const fromDate = ref(new Date().toISOString().split('T')[0])
const toDate = ref(new Date().toISOString().split('T')[0])

const { data: transactions, refresh, status } = await useApiFetch<any[]>(`${config.public.apiBase}/ledger/main`)
const { data: banks } = await useApiFetch<any[]>(`${config.public.apiBase}/banks`)
const { data: users } = await useApiFetch<any[]>(`${config.public.apiBase}/users`)

const depositState = reactive({
  amount: 0,
  bank_id: undefined,
  date: new Date().toISOString().split('T')[0]
})

const columns = [
  { accessorKey: 'id', header: 'ID' },
  { accessorKey: 'date', header: 'Date' },
  { accessorKey: 'user.name', header: 'User' },
  { accessorKey: 'account_type', header: 'Account' },
  { accessorKey: 'description', header: 'Description' },
  { accessorKey: 'amount', header: 'Amount' },
  { accessorKey: 'dr', header: 'DR' },
  { accessorKey: 'cr', header: 'CR' }
]

const totalDr = computed(() => transactions.value?.reduce((sum, t) => sum + parseFloat(t.dr), 0) || 0)
const totalCr = computed(() => transactions.value?.reduce((sum, t) => sum + parseFloat(t.cr), 0) || 0)
const balance = computed(() => totalDr.value - totalCr.value)

const isDepositModalOpen = ref(false)

async function onDeposit() {
  if(!depositState.amount || !depositState.bank_id) {
    toast.add({ title: 'Error', description: 'Enter amount and select bank', color: 'error' })
    return
  }

  try {
    await useApi()(`${config.public.apiBase}/ledger/deposit`, {
      method: 'POST',
      body: {
        amount: depositState.amount,
        bank_id: depositState.bank_id,
        user_id: 1 // hardcoded admin for now
      }
    })
    toast.add({ title: 'Success', description: 'Deposited to bank successfully!', color: 'success' })
    depositState.amount = 0
    depositState.bank_id = undefined
    isDepositModalOpen.value = false
    refresh()
  } catch (error: any) {
    toast.add({ title: 'Error', description: error.data?.message || 'Failed to deposit', color: 'error' })
  }
}
</script>

<template>
  <UDashboardPanel>
    <UDashboardNavbar title="Main Cash Book">
      <template #leading>
        <UDashboardSidebarCollapse />
      </template>
      <template #right>
         <UButton label="Deposit to Bank" icon="i-lucide-landmark" color="primary" @click="isDepositModalOpen = true" />
      </template>
    </UDashboardNavbar>
    
    <UDashboardPanelContent class="bg-muted/30">
      
      <!-- Top Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
         <UCard class="bg-gradient-to-br from-success/10 to-transparent border-success/20 ring-0 shadow-sm">
            <div class="flex items-center gap-4">
               <div class="p-3 bg-success/20 rounded-xl">
                  <UIcon name="i-lucide-arrow-down-left" class="w-6 h-6 text-success" />
               </div>
               <div>
                  <p class="text-sm text-muted-foreground font-medium">Total In (DR)</p>
                  <p class="text-2xl font-bold text-success">Rs. {{ totalDr.toLocaleString(undefined, {minimumFractionDigits: 2}) }}</p>
               </div>
            </div>
         </UCard>
         
         <UCard class="bg-gradient-to-br from-error/10 to-transparent border-error/20 ring-0 shadow-sm">
            <div class="flex items-center gap-4">
               <div class="p-3 bg-error/20 rounded-xl">
                  <UIcon name="i-lucide-arrow-up-right" class="w-6 h-6 text-error" />
               </div>
               <div>
                  <p class="text-sm text-muted-foreground font-medium">Total Out (CR)</p>
                  <p class="text-2xl font-bold text-error">Rs. {{ totalCr.toLocaleString(undefined, {minimumFractionDigits: 2}) }}</p>
               </div>
            </div>
         </UCard>

         <UCard class="bg-gradient-to-br from-primary/10 to-primary/5 border-primary/20 ring-0 shadow-sm relative overflow-hidden">
            <div class="absolute right-0 top-0 opacity-10">
               <UIcon name="i-lucide-wallet" class="w-32 h-32 transform translate-x-8 -translate-y-8" />
            </div>
            <div class="flex items-center gap-4 relative z-10">
               <div class="p-3 bg-primary/20 rounded-xl">
                  <UIcon name="i-lucide-landmark" class="w-6 h-6 text-primary" />
               </div>
               <div>
                  <p class="text-sm text-primary/80 font-medium">Branch Safe Balance</p>
                  <p class="text-3xl font-black text-primary">Rs. {{ balance.toLocaleString(undefined, {minimumFractionDigits: 2}) }}</p>
               </div>
            </div>
         </UCard>
      </div>

      <div class="grid grid-cols-1 gap-6">
         <!-- Full Width Table -->
         <UCard class="flex flex-col shadow-sm ring-0 border border-default">
            <template #header>
               <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                  <h3 class="font-semibold text-lg flex items-center gap-2">
                     <UIcon name="i-lucide-list-collapse" class="w-5 h-5 text-primary" />
                     Ledger History
                  </h3>
                  
                  <div class="flex items-center gap-2">
                     <UInput type="date" size="sm" v-model="fromDate" icon="i-lucide-calendar" />
                     <span class="text-muted-foreground">to</span>
                     <UInput type="date" size="sm" v-model="toDate" icon="i-lucide-calendar" />
                     <UButton icon="i-lucide-refresh-cw" color="neutral" variant="ghost" @click="refresh" />
                  </div>
               </div>
            </template>

            <UTable :data="transactions" :columns="columns" :loading="status === 'pending'" class="w-full" />
         </UCard>
      </div>

    </UDashboardPanelContent>

    <!-- Deposit to Bank Modal -->
    <UModal v-model:open="isDepositModalOpen" title="Deposit to Bank" description="Move cash from the branch safe to a registered bank account.">
       <template #body>
         <div class="space-y-5">
            <UFormField label="Deposit Amount (Rs)" help="Amount of physical cash being moved">
               <UInput type="number" size="xl" v-model="depositState.amount" icon="i-lucide-banknote" />
            </UFormField>

            <UFormField label="Transaction Date">
               <UInput type="date" v-model="depositState.date" />
            </UFormField>

            <UFormField label="Select Destination Bank">
               <USelect 
                  v-model="depositState.bank_id" 
                  :items="banks?.map(b => ({ label: b.name, value: b.id })) || []" 
                  placeholder="Select a bank..." 
                  icon="i-lucide-building-2"
               />
            </UFormField>

            <div class="pt-4 border-t border-default flex justify-end gap-3">
               <UButton label="Cancel" color="neutral" variant="ghost" @click="isDepositModalOpen = false" />
               <UButton 
                  label="Confirm Deposit" 
                  color="primary" 
                  icon="i-lucide-check-circle" 
                  @click="onDeposit" 
               />
            </div>
         </div>
       </template>
    </UModal>
  </UDashboardPanel>
</template>
