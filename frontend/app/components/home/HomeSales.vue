<script setup lang="ts">
import { h, resolveComponent } from 'vue'
import type { TableColumn } from '@nuxt/ui'
import type { Period, Range } from '~/types'

const props = defineProps<{
  period: Period
  range: Range
}>()

const UBadge = resolveComponent('UBadge')

const config = useRuntimeConfig()
const { data: dbStats } = await useApiFetch<any>(`${config.public.apiBase}/dashboard/stats`)

const data = computed(() => {
  if (!dbStats.value?.recent_payments) return []
  return dbStats.value.recent_payments.map((p: any) => ({
    id: p.id,
    date: p.created_at,
    status: p.status === 'Completed' ? 'paid' : p.status.toLowerCase(),
    customer: p.loan?.customer?.full_name || 'Unknown',
    loan_no: p.loan?.loan_number || '-',
    officer: p.officer?.name || 'Unknown',
    amount: parseFloat(p.amount)
  }))
})

const columns: TableColumn<any>[] = [
  {
    accessorKey: 'id',
    header: 'ID',
    cell: ({ row }) => `#${row.getValue('id')}`
  },
  {
    accessorKey: 'date',
    header: 'Date',
    cell: ({ row }) => {
      return new Date(row.getValue('date')).toLocaleString('en-US', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
      })
    }
  },
  {
    accessorKey: 'customer',
    header: 'Customer'
  },
  {
    accessorKey: 'loan_no',
    header: 'Loan No'
  },
  {
    accessorKey: 'officer',
    header: 'Collected By'
  },
  {
    accessorKey: 'amount',
    header: () => h('div', { class: 'text-right' }, 'Amount'),
    cell: ({ row }) => {
      const amount = Number.parseFloat(row.getValue('amount'))

      const formatted = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'LKR',
        maximumFractionDigits: 0
      }).format(amount)

      return h('div', { class: 'text-right font-bold text-success' }, formatted)
    }
  }
]
</script>

<template>
  <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/50">
       <h3 class="font-semibold text-gray-900 dark:text-white">Recent Collections</h3>
       <UButton label="View All" color="primary" variant="soft" size="xs" to="/collections" />
    </div>
    <UTable
      :data="data"
      :columns="columns"
      class="shrink-0"
      :ui="{
        base: 'table-fixed border-separate border-spacing-0',
        thead: '[&>tr]:bg-white dark:[&>tr]:bg-gray-900 [&>tr]:after:content-none text-gray-500',
        tbody: '[&>tr]:last:[&>td]:border-b-0',
        th: 'first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r font-medium',
        td: 'border-b border-default text-sm'
      }"
    />
  </div>
</template>
