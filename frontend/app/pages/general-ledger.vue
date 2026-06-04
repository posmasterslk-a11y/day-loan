<script setup lang="ts">
const config = useRuntimeConfig()
const toast = useToast()

const fromDate = ref(new Date().toISOString().split('T')[0])
const toDate = ref(new Date().toISOString().split('T')[0])
const selectedNarration = ref('all')

const { data: accountsData, refresh: refreshAccounts } = await useApiFetch<any[]>(`${config.public.apiBase}/ledger-accounts`)
const accounts = computed(() => {
  const list = (accountsData.value || []).map(a => ({ label: `${a.narration} (${a.type})`, value: a.id }))
  return [{ label: 'All Accounts', value: 'all' }, ...list]
})

const transactions = ref([])
const summary = ref({ total_dr: 0, total_cr: 0, balance: 0 })
const isLoading = ref(false)

async function loadLedger() {
  isLoading.value = true
  try {
    const res = await $fetch<any>(`${config.public.apiBase}/general-ledger`, {
      params: {
        from: fromDate.value,
        to: toDate.value,
        ledger_account_id: selectedNarration.value
      }
    })
    transactions.value = res.transactions
    summary.value = res.summary
  } catch (e) {
    toast.add({ title: 'Error loading ledger', color: 'error' })
  } finally {
    isLoading.value = false
  }
}

// Modal State
const isModalOpen = ref(false)
const isCreating = ref(false)
const newAccount = ref({ narration: '', type: 'DR' })

async function createLedgerAccount() {
  if (!newAccount.value.narration) {
    toast.add({ title: 'Please enter a narration', color: 'error' })
    return
  }
  isCreating.value = true
  try {
    await $fetch(`${config.public.apiBase}/ledger-accounts`, {
      method: 'POST',
      body: newAccount.value
    })
    toast.add({ title: 'Ledger Account created!', color: 'success' })
    isModalOpen.value = false
    newAccount.value = { narration: '', type: 'DR' }
    await refreshAccounts()
  } catch (e: any) {
    toast.add({ title: e.data?.message || 'Error creating account', color: 'error' })
  } finally {
    isCreating.value = false
  }
}

// Initial load
onMounted(() => {
  loadLedger()
})
</script>

<template>
  <UDashboardPanel>
    <UDashboardNavbar title="General Ledger">
      <template #leading>
        <UDashboardSidebarCollapse />
      </template>
      <template #right>
         <UModal v-model:open="isModalOpen" title="Create Ledger Account" description="Add a new ledger narration">
            <UButton label="New Ledger Account" color="primary" icon="i-lucide-plus" />
            
            <template #body>
              <form @submit.prevent="createLedgerAccount" class="space-y-4">
                <UFormField label="Narration (e.g. Electricity Bill, Salary)" name="narration" required>
                  <UInput v-model="newAccount.narration" class="w-full" placeholder="Enter narration description" required autofocus />
                </UFormField>
                <UFormField label="Account Type" name="type" required>
                  <USelect v-model="newAccount.type" :items="[{label: 'Dr (Income / Cash In)', value: 'DR'}, {label: 'Cr (Expense / Cash Out)', value: 'CR'}]" class="w-full" required />
                </UFormField>

                <div class="flex justify-end gap-3 pt-4">
                  <UButton label="Cancel" color="neutral" variant="ghost" @click="isModalOpen = false" />
                  <UButton type="submit" label="Save Account" color="primary" :loading="isCreating" />
                </div>
              </form>
            </template>
         </UModal>
      </template>
    </UDashboardNavbar>

    <UDashboardPanelContent class="bg-muted/30">

      <!-- Summary KPI Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <UCard class="shadow-sm ring-0 border border-default relative overflow-hidden">
           <div class="absolute right-0 top-0 opacity-10 transform translate-x-4 -translate-y-4">
              <UIcon name="i-lucide-arrow-down-left" class="w-24 h-24 text-success" />
           </div>
           <p class="text-sm font-medium text-muted-foreground mb-1">Total Debits (DR)</p>
           <p class="text-3xl font-bold text-success">Rs. {{ summary.total_dr.toLocaleString() }}</p>
        </UCard>
        
        <UCard class="shadow-sm ring-0 border border-default relative overflow-hidden">
           <div class="absolute right-0 top-0 opacity-10 transform translate-x-4 -translate-y-4">
              <UIcon name="i-lucide-arrow-up-right" class="w-24 h-24 text-error" />
           </div>
           <p class="text-sm font-medium text-muted-foreground mb-1">Total Credits (CR)</p>
           <p class="text-3xl font-bold text-error">Rs. {{ summary.total_cr.toLocaleString() }}</p>
        </UCard>

        <UCard class="shadow-sm ring-0 border border-default relative overflow-hidden" :class="summary.balance >= 0 ? 'bg-primary/5' : 'bg-error/5'">
           <div class="absolute right-0 top-0 opacity-10 transform translate-x-4 -translate-y-4">
              <UIcon name="i-lucide-scale" class="w-24 h-24 text-primary" />
           </div>
           <p class="text-sm font-medium text-muted-foreground mb-1">Net Balance</p>
           <p class="text-3xl font-bold" :class="summary.balance >= 0 ? 'text-primary' : 'text-error'">Rs. {{ summary.balance.toLocaleString() }}</p>
        </UCard>
      </div>

      <!-- Main Table Card -->
      <UCard class="shadow-sm ring-0 border border-default">
         <template #header>
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
               <h3 class="font-semibold text-lg">Transaction Ledger</h3>
               <div class="flex items-center gap-3 w-full sm:w-auto">
                  <UInput type="date" v-model="fromDate" icon="i-lucide-calendar" size="sm" />
                  <span class="text-muted-foreground text-sm">to</span>
                  <UInput type="date" v-model="toDate" icon="i-lucide-calendar" size="sm" />
                  <USelect v-model="selectedNarration" :items="accounts" class="min-w-48" />
                  <UButton label="Filter" color="neutral" variant="solid" @click="loadLedger" :loading="isLoading" size="sm" />
               </div>
            </div>
         </template>

         <UTable 
          :data="transactions" 
          :loading="isLoading"
          :columns="[
            { accessorKey: 'id', header: 'ID' },
            { accessorKey: 'date', header: 'Date' },
            { accessorKey: 'description', header: 'Description' },
            { id: 'narration', accessorKey: 'ledger_account.narration', header: 'Narration' },
            { accessorKey: 'dr', header: 'DR (In)' },
            { accessorKey: 'cr', header: 'CR (Out)' }
          ]"
        >
          <template #dr-cell="{ row }">
            <span class="font-medium text-success">{{ row.original.dr > 0 ? '+ ' + row.original.dr.toLocaleString() : '-' }}</span>
          </template>
          <template #cr-cell="{ row }">
            <span class="font-medium text-error">{{ row.original.cr > 0 ? '- ' + row.original.cr.toLocaleString() : '-' }}</span>
          </template>
        </UTable>

        <template #footer v-if="transactions.length === 0 && !isLoading">
           <div class="text-center py-8">
              <UIcon name="i-lucide-search-x" class="w-12 h-12 text-muted-foreground mx-auto mb-3" />
              <p class="text-muted-foreground">No transactions found for the selected criteria.</p>
           </div>
        </template>
      </UCard>

    </UDashboardPanelContent>
  </UDashboardPanel>
</template>
