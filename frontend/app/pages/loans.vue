<script setup lang="ts">
import * as z from 'zod'

const config = useRuntimeConfig()
const toast = useToast()

const { data: loans, refresh, status } = await useApiFetch<any[]>(`${config.public.apiBase}/loans`)
const { data: customers } = await useApiFetch<any[]>(`${config.public.apiBase}/customers`)
const { data: products } = await useApiFetch<any[]>(`${config.public.apiBase}/loan-products`)
const { data: guarantors } = await useApiFetch<any[]>(`${config.public.apiBase}/guarantors`)

const items = [{
  label: 'Active Loans',
  value: 'active',
  icon: 'i-lucide-activity'
}, {
  label: 'Settled / History',
  value: 'settled',
  icon: 'i-lucide-check-circle'
}]
const activeTab = ref('active')

const searchQuery = ref('')
const filteredData = computed(() => {
  if (!loans.value) return []
  
  let result = loans.value
  if (activeTab.value === 'active') {
    result = result.filter((l: any) => l.status !== 'Settled' && l.total_payable > 0)
  } else {
    result = result.filter((l: any) => l.status === 'Settled' || l.total_payable <= 0)
  }

  if (!searchQuery.value) return result
  
  const q = searchQuery.value.toLowerCase()
  return result.filter((l: any) => 
    (l.loan_number && l.loan_number.toLowerCase().includes(q)) ||
    (l.customer?.full_name && l.customer.full_name.toLowerCase().includes(q)) ||
    (l.customer?.nic && l.customer.nic.toLowerCase().includes(q)) ||
    (l.customer?.phone && l.customer.phone.toLowerCase().includes(q))
  )
})

const UBadge = resolveComponent('UBadge')
const UButton = resolveComponent('UButton')

const columns = [
  { accessorKey: 'loan_number', header: 'Loan No' },
  { accessorKey: 'customer.full_name', header: 'Customer' },
  { accessorKey: 'product.name', header: 'Product' },
  { accessorKey: 'amount', header: 'Amount' },
  { accessorKey: 'total_payable', header: 'Total Payable' },
  { accessorKey: 'daily_installment', header: 'Daily Installment' },
  { 
    accessorKey: 'status', 
    header: 'Status',
    cell: ({ row }: any) => {
      const color = row.original.status === 'Pending' ? 'warning' : 'success';
      return h(UBadge, { color, variant: 'subtle' }, () => row.original.status)
    }
  },
  { 
    id: 'actions', 
    header: '',
    cell: ({ row }: any) => {
      if (row.original.status === 'Pending') {
        return h('div', { class: 'flex gap-2 items-center' }, [
          h(UButton, { 
            icon: 'i-lucide-eye',
            size: 'xs',
            color: 'gray',
            variant: 'ghost',
            onClick: () => openViewModal(row.original)
          }),
          h(UButton, { 
            label: 'Approve', 
            size: 'xs', 
            color: 'primary',
            onClick: () => approveLoan(row.original.id)
          })
        ])
      }
      return h(UButton, { 
        icon: 'i-lucide-eye',
        size: 'xs',
        color: 'gray',
        variant: 'ghost',
        onClick: () => openViewModal(row.original)
      })
    }
  }
]

const schema = z.object({
  customer_id: z.number({ required_error: 'Customer is required' }),
  loan_product_id: z.number({ required_error: 'Loan Product is required' }),
  amount: z.number().min(1, 'Amount must be greater than 0'),
  guarantor_id: z.number().optional(),
  start_date: z.string().optional()
})

const state = reactive({
  customer_id: undefined,
  loan_product_id: undefined,
  amount: 0,
  guarantor_id: undefined,
  start_date: new Date().toISOString().split('T')[0]
})

const isModalOpen = ref(false)
const isViewModalOpen = ref(false)
const selectedLoan = ref<any>(null)

function openViewModal(loan: any) {
  selectedLoan.value = loan
  isViewModalOpen.value = true
}

const selectedProduct = computed(() => {
  if (!state.loan_product_id || !products.value) return null;
  return products.value.find(p => p.id === state.loan_product_id);
})

const calculatedTotal = computed(() => {
  if (!selectedProduct.value || state.amount <= 0) return 0;
  const interest = state.amount * (selectedProduct.value.interest_rate / 100);
  return state.amount + interest;
})

const calculatedDaily = computed(() => {
  if (!selectedProduct.value || state.amount <= 0) return 0;
  return calculatedTotal.value / selectedProduct.value.duration_days;
})

async function onSubmit(event: any) {
  try {
    await useApi()(`${config.public.apiBase}/loans`, {
      method: 'POST',
      body: event.data
    })
    toast.add({ title: 'Success', description: 'Loan issued successfully! Schedules generated.', color: 'success' })
    isModalOpen.value = false
    refresh()
  } catch (error: any) {
    toast.add({ title: 'Error', description: error.data?.message || 'Failed to issue loan', color: 'error' })
  }
}

async function approveLoan(id: number) {
  try {
    await useApi()(`${config.public.apiBase}/loans/${id}/approve`, {
      method: 'PUT'
    })
    toast.add({ title: 'Success', description: 'Loan approved successfully!', color: 'success' })
    refresh()
  } catch (error: any) {
    toast.add({ title: 'Error', description: error.data?.message || 'Failed to approve loan', color: 'error' })
  }
}
</script>

<template>
  <UDashboardPanel>
    <UDashboardNavbar title="Loans">
      <template #leading>
        <UDashboardSidebarCollapse />
      </template>
      <template #right>
        <UButton label="Issue New Loan" icon="i-lucide-plus" @click="isModalOpen = true" />
      </template>
    </UDashboardNavbar>
    
    <UDashboardPanelContent>
      <UTabs :items="items" v-model="activeTab" class="mb-6 w-full" />
      <div class="flex flex-wrap items-center justify-between gap-1.5 mb-4">
        <UInput
          v-model="searchQuery"
          class="max-w-md w-full sm:w-96"
          icon="i-lucide-search"
          placeholder="Search by NIC, Name, Loan No, or Mobile..."
        />
      </div>
      <UTable :data="filteredData" :columns="columns" :loading="status === 'pending'" />
    </UDashboardPanelContent>

    <UModal v-model:open="isModalOpen" title="Issue New Loan">
      <template #body>
        <UForm :schema="schema" :state="state" @submit="onSubmit" class="space-y-4">
          <UFormField label="Select Customer" name="customer_id">
            <USelect v-model="state.customer_id" :items="customers?.map(c => ({ label: c.name + ' ('+c.nic+')', value: c.db_id }))" class="w-full" placeholder="Select a customer..." />
          </UFormField>

          <UFormField label="Select Loan Product" name="loan_product_id">
            <USelect v-model="state.loan_product_id" :items="products?.map(p => ({ label: p.name + ' ('+p.min_amount+'-'+p.max_amount+')', value: p.id }))" class="w-full" placeholder="Select a product..." />
          </UFormField>
          
          <UFormField label="Loan Amount" name="amount" :help="selectedProduct ? `Must be between ${selectedProduct.min_amount} and ${selectedProduct.max_amount}` : ''">
            <UInput v-model="state.amount" type="number" class="w-full" />
          </UFormField>

          <UFormField label="Issue Date" name="start_date">
            <UInput v-model="state.start_date" type="date" class="w-full" />
          </UFormField>
          
          <UFormField label="Select Guarantor (Optional)" name="guarantor_id">
            <USelect v-model="state.guarantor_id" :items="guarantors?.map(g => ({ label: g.full_name + ' ('+g.nic+')', value: g.id }))" class="w-full" placeholder="Select a guarantor..." />
          </UFormField>
          
          <div v-if="selectedProduct && state.amount > 0" class="mt-4 p-4 bg-muted/50 rounded-lg space-y-2 border border-default">
             <div class="flex justify-between">
                <span class="text-muted-foreground">Interest Rate:</span>
                <span class="font-medium">{{ selectedProduct.interest_rate }}%</span>
             </div>
             <div class="flex justify-between">
                <span class="text-muted-foreground">Duration:</span>
                <span class="font-medium">{{ selectedProduct.duration_days }} Days</span>
             </div>
             <div class="flex justify-between">
                <span class="text-muted-foreground">Total Payable:</span>
                <span class="font-medium text-primary">{{ calculatedTotal }}</span>
             </div>
             <div class="flex justify-between font-bold border-t border-default pt-2">
                <span>Daily Installment:</span>
                <span class="text-primary">{{ calculatedDaily.toFixed(2) }}</span>
             </div>
          </div>
          
          <div class="flex justify-end gap-2 mt-4">
            <UButton label="Cancel" color="neutral" variant="subtle" @click="isModalOpen = false" />
            <UButton type="submit" label="Issue Loan" />
          </div>
        </UForm>
      </template>
    </UModal>

    <UModal v-model:open="isViewModalOpen" title="Loan Details" v-if="selectedLoan">
      <template #body>
        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-muted-foreground">Loan No:</span> <span class="font-medium">{{ selectedLoan.loan_number }}</span></div>
            <div><span class="text-muted-foreground">Status:</span> 
               <UBadge :color="selectedLoan.status === 'Pending' ? 'warning' : 'success'" variant="subtle" size="xs" class="ml-2">{{ selectedLoan.status }}</UBadge>
            </div>
            <div><span class="text-muted-foreground">Customer:</span> <span class="font-medium">{{ selectedLoan.customer?.full_name }} ({{ selectedLoan.customer?.nic }})</span></div>
            <div><span class="text-muted-foreground">Guarantor:</span> <span class="font-medium">{{ selectedLoan.guarantor ? selectedLoan.guarantor.full_name : 'None' }}</span></div>
            <div class="col-span-2 border-b border-default my-2"></div>
            <div><span class="text-muted-foreground">Product:</span> <span class="font-medium">{{ selectedLoan.product?.name }}</span></div>
            <div><span class="text-muted-foreground">Interest Rate:</span> <span class="font-medium">{{ selectedLoan.interest_rate }}%</span></div>
            <div><span class="text-muted-foreground">Principal Amount:</span> <span class="font-medium">{{ selectedLoan.amount }}</span></div>
            <div><span class="text-muted-foreground">Total Payable:</span> <span class="font-medium text-primary">{{ selectedLoan.total_payable }}</span></div>
            <div><span class="text-muted-foreground">Duration:</span> <span class="font-medium">{{ selectedLoan.term_days }} Days</span></div>
            <div><span class="text-muted-foreground">Daily Installment:</span> <span class="font-medium text-primary">{{ selectedLoan.daily_installment }}</span></div>
            <div class="col-span-2 border-b border-default my-2"></div>
            <div><span class="text-muted-foreground">Issue Date:</span> <span class="font-medium">{{ new Date(selectedLoan.created_at).toLocaleDateString() }}</span></div>
            <div><span class="text-muted-foreground">Issue Time:</span> <span class="font-medium">{{ new Date(selectedLoan.created_at).toLocaleTimeString() }}</span></div>
          </div>
          <div class="flex justify-end mt-6">
            <UButton label="Close" color="neutral" variant="subtle" @click="isViewModalOpen = false" />
          </div>
        </div>
      </template>
    </UModal>
  </UDashboardPanel>
</template>
