<script setup lang="ts">
import { eachDayOfInterval, format } from 'date-fns'
import { VisXYContainer, VisLine, VisAxis, VisArea, VisCrosshair, VisTooltip } from '@unovis/vue'
import type { Period, Range } from '~/types'

const cardRef = useTemplateRef<HTMLElement | null>('cardRef')

const props = defineProps<{
  period: Period
  range: Range
}>()

type DataRecord = {
  date: Date
  amount: number
}

const { width } = useElementSize(cardRef)
const config = useRuntimeConfig()

const data = ref<DataRecord[]>([])

watch([() => props.period, () => props.range], async () => {
  if (!props.range.start || !props.range.end) return
  
  // Generate all days in the range
  const days = eachDayOfInterval({ start: props.range.start, end: props.range.end })
  
  const startStr = format(props.range.start, 'yyyy-MM-dd')
  const endStr = format(props.range.end, 'yyyy-MM-dd')
  
  try {
    const rawData = await $fetch<any[]>(`${config.public.apiBase}/dashboard/chart?start=${startStr}&end=${endStr}`)
    
    // Create a map of date -> amount for fast lookup
    const amountMap = new Map<string, number>()
    if (rawData && Array.isArray(rawData)) {
      rawData.forEach(d => {
        amountMap.set(d.date, parseFloat(d.amount))
      })
    }
    
    // Map every day to either the real amount or 0
    data.value = days.map(date => {
      const dateStr = format(date, 'yyyy-MM-dd')
      return {
        date,
        amount: amountMap.get(dateStr) || 0
      }
    })
  } catch(e) {
    console.error('Failed to fetch chart data', e)
    // Fallback to empty days
    data.value = days.map(date => ({ date, amount: 0 }))
  }
}, { immediate: true })

const x = (_: DataRecord, i: number) => i
const y = (d: DataRecord) => d.amount

const total = computed(() => data.value.reduce((acc: number, { amount }) => acc + amount, 0))

const formatNumber = new Intl.NumberFormat('en', { style: 'currency', currency: 'LKR', maximumFractionDigits: 0 }).format

const formatDate = (date: Date): string => {
  return format(date, 'd MMM')
}

const xTicks = (i: number) => {
  if (i === 0 || i === data.value.length - 1 || !data.value[i]) {
    return ''
  }
  return formatDate(data.value[i].date)
}

const template = (d: DataRecord) => `${formatDate(d.date)}: ${formatNumber(d.amount)}`
</script>

<template>
  <UCard ref="cardRef" :ui="{ root: 'overflow-visible', body: 'px-0! pt-0! pb-3!' }">
    <template #header>
      <div>
        <p class="text-xs text-muted uppercase mb-1.5">
          Revenue
        </p>
        <p class="text-3xl text-highlighted font-semibold">
          {{ formatNumber(total) }}
        </p>
      </div>
    </template>

    <VisXYContainer
      :data="data"
      :padding="{ top: 40 }"
      class="h-96"
      :width="width"
    >
      <VisLine
        :x="x"
        :y="y"
        color="var(--ui-primary)"
      />
      <VisArea
        :x="x"
        :y="y"
        color="var(--ui-primary)"
        :opacity="0.1"
      />

      <VisAxis
        type="x"
        :x="x"
        :tick-format="xTicks"
      />

      <VisCrosshair
        color="var(--ui-primary)"
        :template="template"
      />

      <VisTooltip />
    </VisXYContainer>
  </UCard>
</template>

<style scoped>
.unovis-xy-container {
  --vis-crosshair-line-stroke-color: var(--ui-primary);
  --vis-crosshair-circle-stroke-color: var(--ui-bg);

  --vis-axis-grid-color: var(--ui-border);
  --vis-axis-tick-color: var(--ui-border);
  --vis-axis-tick-label-color: var(--ui-text-dimmed);

  --vis-tooltip-background-color: var(--ui-bg);
  --vis-tooltip-border-color: var(--ui-border);
  --vis-tooltip-text-color: var(--ui-text-highlighted);
}
</style>
