<script setup lang="ts">
import type { Period, Range, Stat } from '~/types'

const props = defineProps<{
  period: Period
  range: Range
}>()

function formatCurrency(value: number): string {
  return value.toLocaleString('en-US', {
    style: 'currency',
    currency: 'LKR',
    maximumFractionDigits: 0
  })
}

const config = useRuntimeConfig()
const { data: dbStats } = await useApiFetch<any>(`${config.public.apiBase}/dashboard/stats`)

const stats = computed<Stat[]>(() => {
  if (!dbStats.value) return []
  return [
    {
      title: 'Total Customers',
      icon: 'i-lucide-users',
      value: dbStats.value.customers,
      variation: 0
    },
    {
      title: 'Active Loans',
      icon: 'i-lucide-banknote',
      value: dbStats.value.active_loans,
      variation: 0
    },
    {
      title: 'Lifetime Loan Value',
      icon: 'i-lucide-landmark',
      value: formatCurrency(dbStats.value.loan_value || 0),
      variation: 0
    },
    {
      title: 'Expected Today',
      icon: 'i-lucide-calendar-clock',
      value: formatCurrency(dbStats.value.today_expected || 0),
      variation: 0
    },
    {
      title: 'Collected Today',
      icon: 'i-lucide-wallet',
      value: formatCurrency(dbStats.value.today_collected || 0),
      variation: 0
    },
    {
      title: 'Loans Issued (This Month)',
      icon: 'i-lucide-calendar-plus',
      value: dbStats.value.loans_this_month || 0,
      variation: 0
    },
    {
      title: 'Loan Value (This Month)',
      icon: 'i-lucide-coins',
      value: formatCurrency(dbStats.value.loan_value_this_month || 0),
      variation: 0
    },
    {
      title: 'Est. Profit (This Month)',
      icon: 'i-lucide-trending-up',
      value: formatCurrency(dbStats.value.profit_this_month || 0),
      variation: 0
    }
  ]
})
</script>

<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div
      v-for="(stat, index) in stats"
      :key="index"
      class="bg-white dark:bg-gray-900 ring-1 ring-gray-200 dark:ring-gray-800 rounded-xl p-5 flex flex-col justify-between shadow-sm hover:shadow transition-shadow"
    >
      <div class="flex items-center justify-between mb-4">
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ stat.title }}</span>
        <div class="p-1.5 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
          <UIcon :name="stat.icon" class="w-5 h-5" />
        </div>
      </div>
      <div class="flex items-baseline gap-2">
         <div class="text-2xl font-semibold text-gray-900 dark:text-white">
           {{ stat.value }}
         </div>
      </div>
    </div>
  </div>
</template>
