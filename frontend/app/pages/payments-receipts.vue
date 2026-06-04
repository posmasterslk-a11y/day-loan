<script setup lang="ts">
const config = useRuntimeConfig()
const toast = useToast()

const { data: accountsData } = await useApiFetch<any[]>(`${config.public.apiBase}/ledger-accounts`)
const { data: banksData } = await useApiFetch<any[]>(`${config.public.apiBase}/banks`)

const accounts = computed(() => {
  return (accountsData.value || []).map(a => ({ label: `${a.narration} (${a.type})`, value: a.id }))
})

const banks = computed(() => {
  return (banksData.value || []).map(b => ({ label: `${b.name} - ${b.account_number}`, value: b.id }))
})

const state = ref({
  ledger_account_id: '',
  amount: 0,
  description: '',
  source: 'MAIN',
  bank_id: null,
  date: new Date().toISOString().split('T')[0]
})

const isLoading = ref(false)

async function submitPayment() {
  if (!state.value.ledger_account_id || state.value.amount <= 0) {
    toast.add({ title: 'Please fill all required fields correctly', color: 'error' })
    return
  }

  isLoading.value = true
  try {
    await useApi()(`${config.public.apiBase}/payments-receipts`, {
      method: 'POST',
      body: state.value
    })
    toast.add({ title: 'Payment / Receipt saved successfully!', color: 'success' })
    // reset
    state.value.amount = 0
    state.value.description = ''
  } catch (e: any) {
    toast.add({ title: e.data?.message || 'Error saving transaction', color: 'error' })
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <UDashboardPanel>
    <UDashboardNavbar title="Payment & Receipt">
      <template #leading>
        <UDashboardSidebarCollapse />
      </template>
    </UDashboardNavbar>

    <UDashboardPanelContent class="bg-muted/30 p-4 sm:p-8">
      
      <div class="w-full max-w-2xl mx-auto mt-4 sm:mt-12 mb-12">
        <UCard class="shadow-xl ring-1 ring-gray-200 dark:ring-gray-800 rounded-2xl overflow-hidden relative" :ui="{ body: 'p-0', header: 'px-6 py-8 sm:px-8 border-b-0' }">
        <!-- Decorative Header Background -->
        <div class="absolute top-0 left-0 right-0 h-32 bg-gradient-to-r from-primary/10 to-transparent pointer-events-none"></div>

        <template #header>
           <div class="flex items-center gap-2 relative z-10">
              <UIcon name="i-lucide-arrow-right-left" class="w-5 h-5 text-primary" />
              <h3 class="font-semibold text-lg text-gray-900 dark:text-white">Record Transaction</h3>
           </div>
        </template>

        <form @submit.prevent="submitPayment" class="p-4 sm:p-5 space-y-5 bg-white dark:bg-neutral-900 relative z-10">
          
          <div class="grid grid-cols-2 gap-4">
             <UFormField label="Date" name="date" required>
               <UInput type="date" v-model="state.date" icon="i-lucide-calendar" size="md" class="w-full" required />
             </UFormField>

             <UFormField label="Narration" name="ledger_account_id" required>
               <USelect v-model="state.ledger_account_id" :items="accounts" placeholder="Select..." size="md" class="w-full" required />
             </UFormField>
          </div>

          <div class="grid grid-cols-2 gap-4">
             <UFormField label="Description" name="description" required>
               <UInput v-model="state.description" placeholder="Transaction details" size="md" class="w-full" required />
             </UFormField>

             <UFormField label="Amount (Rs)" name="amount" required>
               <UInput type="number" step="0.01" v-model="state.amount" placeholder="0.00" icon="i-lucide-banknote" size="md" class="w-full font-medium" required />
             </UFormField>
          </div>

          <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
             <div class="flex gap-4">
               <URadioGroup v-model="state.source" :items="[{label: 'Main Cashbook', value: 'MAIN'}, {label: 'Bank Book', value: 'BANK'}]" class="flex gap-4" :ui="{ legend: 'sr-only' }" />
               
               <!-- Only show bank selection if BANK is selected -->
               <div v-if="state.source === 'BANK'" class="w-56 ml-4">
                 <USelect v-model="state.bank_id" :items="banks" placeholder="Select Bank" size="md" required />
               </div>
             </div>

             <UButton type="submit" label="Save" icon="i-lucide-check" color="primary" class="px-6" size="md" :loading="isLoading" />
          </div>

        </form>
      </UCard>
      </div>

    </UDashboardPanelContent>
  </UDashboardPanel>
</template>
