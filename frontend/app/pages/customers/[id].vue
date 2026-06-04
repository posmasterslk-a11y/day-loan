<script setup lang="ts">
const route = useRoute()
const config = useRuntimeConfig()

const { data, status } = await useApiFetch<any>(`${config.public.apiBase}/customers/${route.params.id}`)

const customer = computed(() => data.value?.customer)
const loans = computed(() => data.value?.loans || [])

const selectedLoanId = ref<number | undefined>(undefined)

watchEffect(() => {
   if (loans.value.length > 0 && !selectedLoanId.value) {
      selectedLoanId.value = loans.value[0].id
   }
})

const loan = computed(() => loans.value.find((l: any) => l.id === selectedLoanId.value))

const schedules = computed(() => loan.value?.schedules || [])
const payments = computed(() => loan.value?.payments || [])

// Global Customer Math
const globalTotalLoanValue = computed(() => {
  return loans.value.reduce((sum: number, l: any) => sum + parseFloat(l.total_payable || 0), 0)
})

// Single Selected Loan Math
const principalAmount = computed(() => parseFloat(loan.value?.amount || 0))
const paidAmount = computed(() => {
  if (!payments.value || payments.value.length === 0) return 0;
  return payments.value.reduce((sum: number, p: any) => sum + parseFloat(p.amount || 0), 0)
})
const currentBalance = computed(() => parseFloat(loan.value?.total_payable || 0))
const totalPayable = computed(() => currentBalance.value + paidAmount.value)
const progress = computed(() => totalPayable.value ? (paidAmount.value / totalPayable.value) * 100 : 0)

const paymentColumns = [
  { accessorKey: 'payment_date', header: 'Date' },
  { accessorKey: 'amount', header: 'Amount (Rs)' },
  { accessorKey: 'status', header: 'Status' }
]

function getScheduleColor(status: string) {
  if (status === 'Paid') return 'bg-emerald-50 text-emerald-600 border-emerald-200'
  if (status === 'Arrears') return 'bg-red-50 text-red-600 border-red-200'
  if (status === 'Partial') return 'bg-amber-50 text-amber-600 border-amber-200'
  return 'bg-white text-gray-400 border-gray-200'
}
</script>

<template>
  <UDashboardPanel>
    <UDashboardNavbar title="Loan Analytics">
      <template #leading>
        <UButton icon="i-lucide-arrow-left" color="neutral" variant="ghost" to="/customers" />
      </template>
    </UDashboardNavbar>
    
    <UDashboardPanelContent class="bg-gray-50/50 p-6 lg:p-10">
      
      <div v-if="!customer && status === 'pending'" class="flex items-center justify-center h-64">
         <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin text-gray-400" />
      </div>

      <div v-else-if="customer" class="max-w-7xl mx-auto space-y-8">
        
        <!-- Header: Profile & Selector -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 bg-white p-6 rounded-2xl border border-gray-200/60 shadow-sm">
          <div class="flex items-center gap-5">
            <UAvatar :src="customer.photo_path ? `${config.public.apiBase.replace('/api', '')}/storage/${customer.photo_path}` : ''" size="3xl" class="ring-2 ring-gray-100" />
            <div>
              <h2 class="text-2xl font-bold text-gray-900">{{ customer.full_name }}</h2>
              <div class="flex items-center gap-4 text-sm text-gray-500 mt-1">
                <span class="flex items-center gap-1.5"><UIcon name="i-lucide-id-card" class="w-4 h-4" /> {{ customer.nic }}</span>
                <span class="flex items-center gap-1.5"><UIcon name="i-lucide-phone" class="w-4 h-4" /> {{ customer.phone }}</span>
              </div>
            </div>
          </div>

          <div class="flex-1 flex justify-center hidden lg:flex">
             <div class="text-center px-8 border-l border-r border-gray-100">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-1">Lifetime Loan Value</p>
                <p class="text-2xl font-black text-primary">Rs. {{ globalTotalLoanValue.toLocaleString() }}</p>
             </div>
          </div>
          
          <div class="w-full md:w-80">
            <label class="block text-xs font-medium text-gray-500 mb-1.5 uppercase tracking-wide">Selected Loan</label>
            <USelect 
               v-if="loans.length > 0"
               v-model="selectedLoanId" 
               :items="loans.map((l: any) => ({ label: `Loan ${l.loan_number} • ${parseFloat(l.total_payable) <= 0 ? 'Completed' : 'Rs. ' + l.total_payable}`, value: l.id }))" 
               class="w-full"
               size="md"
            />
            <div v-else class="text-sm text-gray-500 bg-gray-50 p-2 rounded border border-dashed text-center">
               No loans available
            </div>
          </div>
        </div>

        <div v-if="loan" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
           
           <!-- Left Column: Financials -->
           <div class="lg:col-span-4 space-y-8">
              
              <!-- Core Financials -->
              <div class="bg-white rounded-2xl border border-gray-200/60 shadow-sm p-6">
                 <h3 class="font-semibold text-gray-900 mb-6 flex items-center gap-2">
                    <UIcon name="i-lucide-pie-chart" class="w-5 h-5 text-gray-400" />
                    Financial Overview
                 </h3>
                 
                 <div class="space-y-6">
                    <div>
                       <div class="flex items-center gap-3 mb-1">
                          <p class="text-sm font-medium text-gray-500">Principal Loan Amount</p>
                          <UBadge v-if="currentBalance <= 0" color="success" size="sm" variant="subtle">Completed</UBadge>
                          <UBadge v-else color="primary" size="sm" variant="subtle">Active</UBadge>
                       </div>
                       <p class="text-3xl font-bold text-gray-900">Rs. {{ principalAmount.toLocaleString() }}</p>
                    </div>
                    
                    <UDivider />

                    <div>
                       <p class="text-sm font-medium text-gray-500 mb-1">Total Payable (with Interest)</p>
                       <p class="text-xl font-bold text-gray-700">Rs. {{ totalPayable.toLocaleString() }}</p>
                    </div>

                    <UDivider />

                    <div>
                       <p class="text-sm font-medium text-gray-500 mb-1">Remaining Balance</p>
                       <p class="text-xl font-bold text-rose-600">Rs. {{ currentBalance.toLocaleString() }}</p>
                    </div>
                 </div>
              </div>

              <!-- Payment Status -->
              <div class="bg-white rounded-2xl border border-gray-200/60 shadow-sm p-6">
                 <div class="flex items-end justify-between mb-2">
                    <p class="text-sm font-medium text-gray-500">Recovery Progress</p>
                    <p class="text-sm font-bold text-emerald-600">{{ progress.toFixed(1) }}%</p>
                 </div>
                 <UProgress :value="progress" color="emerald" class="h-2.5 rounded-full mb-6" />

                 <div class="grid grid-cols-2 gap-4">
                    <div class="bg-emerald-50/50 p-4 rounded-xl border border-emerald-100/50">
                       <p class="text-xs font-medium text-emerald-600 mb-1 uppercase tracking-wide">Recovered</p>
                       <p class="text-lg font-bold text-emerald-700">Rs. {{ paidAmount.toLocaleString() }}</p>
                    </div>
                    <div class="bg-rose-50/50 p-4 rounded-xl border border-rose-100/50">
                       <p class="text-xs font-medium text-rose-600 mb-1 uppercase tracking-wide">Remaining</p>
                       <p class="text-lg font-bold text-rose-700">Rs. {{ currentBalance.toLocaleString() }}</p>
                    </div>
                 </div>
              </div>

           </div>

           <!-- Right Column: Timeline & Data -->
           <div class="lg:col-span-8 space-y-8">
              
              <!-- Calendar / Timeline Grid -->
              <div class="bg-white rounded-2xl border border-gray-200/60 shadow-sm overflow-hidden">
                 <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                       <h3 class="font-semibold text-gray-900 text-lg">Rental Timeline</h3>
                       <p class="text-sm text-gray-500 mt-0.5">60-day schedule visualization</p>
                    </div>
                    
                    <div class="flex items-center gap-3 text-xs font-medium text-gray-500">
                       <span class="flex items-center gap-1.5"><div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div> Paid</span>
                       <span class="flex items-center gap-1.5"><div class="w-2.5 h-2.5 rounded-full bg-amber-500"></div> Partial</span>
                       <span class="flex items-center gap-1.5"><div class="w-2.5 h-2.5 rounded-full bg-red-500"></div> Arrears</span>
                    </div>
                 </div>
                 
                 <div class="p-6 bg-gray-50/30">
                    <div class="grid grid-cols-10 sm:grid-cols-12 md:grid-cols-15 gap-2">
                       <UTooltip 
                          v-for="(day, index) in schedules" 
                          :key="day.id"
                          :text="`Day ${index + 1} • Rs. ${day.amount} • ${day.due_date}`"
                       >
                          <div 
                             class="aspect-square rounded border flex items-center justify-center cursor-default transition-all duration-200 hover:ring-2 ring-primary/50"
                             :class="getScheduleColor(day.status)"
                          >
                             <span class="text-xs font-semibold">{{ index + 1 }}</span>
                          </div>
                       </UTooltip>
                    </div>
                 </div>
              </div>

              <!-- Receipts Table -->
              <div class="bg-white rounded-2xl border border-gray-200/60 shadow-sm overflow-hidden">
                 <div class="p-6 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900 text-lg">Payment Receipts</h3>
                 </div>
                 <UTable :data="payments" :columns="paymentColumns" :ui="{ th: 'bg-gray-50/80 font-semibold text-gray-600', td: 'py-3' }" />
              </div>

           </div>
        </div>

      </div>
    </UDashboardPanelContent>
  </UDashboardPanel>
</template>
