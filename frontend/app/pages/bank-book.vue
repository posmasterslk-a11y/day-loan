<script setup lang="ts">
const config = useRuntimeConfig()

const bankId = ref(undefined)
const { data: banks } = await useApiFetch<any[]>(`${config.public.apiBase}/banks`)

const { data: transactions, status } = await useApiFetch<any[]>(() => 
  `${config.public.apiBase}/ledger/bank${bankId.value ? `?bank_id=${bankId.value}` : ''}`
)

const columns = [
  { accessorKey: 'date', header: 'Date' },
  { accessorKey: 'bank.name', header: 'Bank' },
  { accessorKey: 'account_type', header: 'Account' },
  { accessorKey: 'description', header: 'Description' },
  { accessorKey: 'amount', header: 'Amount' },
  { accessorKey: 'dr', header: 'DR (In)' },
  { accessorKey: 'cr', header: 'CR (Out)' }
]

const totalDr = computed(() => transactions.value?.reduce((sum, t) => sum + parseFloat(t.dr), 0) || 0)
const totalCr = computed(() => transactions.value?.reduce((sum, t) => sum + parseFloat(t.cr), 0) || 0)
const balance = computed(() => totalDr.value - totalCr.value)
</script>

<template>
  <UDashboardPanel>
    <UDashboardNavbar title="Bank Book">
      <template #leading>
        <UDashboardSidebarCollapse />
      </template>
      <template #right>
        <div class="flex items-center gap-2">
           <span class="text-sm text-muted-foreground">Select Bank:</span>
           <USelect v-model="bankId" :items="banks?.map(b => ({ label: b.name, value: b.id })) || []" class="w-48" clearable />
        </div>
      </template>
    </UDashboardNavbar>
    
    <UDashboardPanelContent>
      <!-- Summary -->
      <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="p-4 bg-muted/50 rounded-lg border border-default">
          <div class="text-muted-foreground text-sm mb-1">Total Deposits (DR)</div>
          <div class="text-2xl font-bold text-success">Rs. {{ totalDr.toFixed(2) }}</div>
        </div>
        <div class="p-4 bg-muted/50 rounded-lg border border-default">
          <div class="text-muted-foreground text-sm mb-1">Total Withdrawals (CR)</div>
          <div class="text-2xl font-bold text-error">Rs. {{ totalCr.toFixed(2) }}</div>
        </div>
        <div class="p-4 bg-primary/10 rounded-lg border border-primary/30">
          <div class="text-primary/70 text-sm mb-1">Current Bank Balance</div>
          <div class="text-2xl font-bold text-primary">Rs. {{ balance.toFixed(2) }}</div>
        </div>
      </div>

      <!-- Ledger Table -->
      <UTable :data="transactions" :columns="columns" :loading="status === 'pending'" class="border border-default rounded-md" />
    </UDashboardPanelContent>
  </UDashboardPanel>
</template>
