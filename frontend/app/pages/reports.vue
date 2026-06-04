<script setup lang="ts">
import { format } from 'date-fns'

const config = useRuntimeConfig()
const { token } = useAuth()

const isDownloading = ref(false)

const dailyDate = ref(new Date())
const selectedCustomerLoan = ref('')
const { data: loans } = await useApiFetch<any[]>(`${config.public.apiBase}/loans`)

const downloadPDF = async (url: string, filename: string) => {
  isDownloading.value = true
  try {
    const res = await fetch(url, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token.value}`,
        'Accept': 'application/pdf'
      }
    })
    
    if (!res.ok) throw new Error('Failed to download report')
      
    const blob = await res.blob()
    const downloadUrl = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = downloadUrl
    a.download = filename
    document.body.appendChild(a)
    a.click()
    window.URL.revokeObjectURL(downloadUrl)
    a.remove()
  } catch (error) {
    console.error(error)
    alert('Could not download the PDF report.')
  } finally {
    isDownloading.value = false
  }
}

const downloadDailyCollection = () => {
  const d = format(dailyDate.value, 'yyyy-MM-dd')
  downloadPDF(`${config.public.apiBase}/reports/daily-collection?date=${d}`, `Daily_Collection_${d}.pdf`)
}

const downloadArrears = () => {
  downloadPDF(`${config.public.apiBase}/reports/arrears`, `Arrears_Report_${format(new Date(), 'yyyy-MM-dd')}.pdf`)
}

const downloadActiveLoans = () => {
  downloadPDF(`${config.public.apiBase}/reports/active-loans`, `Active_Loans_${format(new Date(), 'yyyy-MM-dd')}.pdf`)
}

const downloadCustomerStatement = () => {
  if (!selectedCustomerLoan.value) return
  downloadPDF(`${config.public.apiBase}/reports/customer-statement/${selectedCustomerLoan.value}`, `Customer_Statement_${selectedCustomerLoan.value}.pdf`)
}
</script>

<template>
  <UDashboardPanel scrollable>
    <template #header>
      <UDashboardNavbar title="Reports & Analytics">
        <template #right>
          <UButton v-if="isDownloading" loading color="primary" variant="soft">Generating PDF...</UButton>
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-5xl">
        
        <!-- Daily Collection Report -->
        <UCard>
          <template #header>
            <div class="flex items-center gap-3">
              <UIcon name="i-lucide-calendar-days" class="w-6 h-6 text-primary" />
              <h3 class="text-lg font-semibold">Daily Collection Report</h3>
            </div>
          </template>
          <p class="text-sm text-gray-500 mb-4">Generates a detailed breakdown of all collections made on a specific date, grouped by Field Officer.</p>
          <UFormGroup label="Select Date">
             <!-- Basic date input for simplicity -->
             <input type="date" v-model="dailyDate" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm px-3 py-2 border dark:bg-gray-800 dark:border-gray-700" />
          </UFormGroup>
          <template #footer>
            <UButton block color="primary" icon="i-lucide-download" @click="downloadDailyCollection">Download PDF</UButton>
          </template>
        </UCard>

        <!-- Arrears Report -->
        <UCard>
          <template #header>
            <div class="flex items-center gap-3">
              <UIcon name="i-lucide-alert-triangle" class="w-6 h-6 text-red-500" />
              <h3 class="text-lg font-semibold">Arrears & Defaulters</h3>
            </div>
          </template>
          <p class="text-sm text-gray-500 mb-8">A comprehensive list of all active loans that have missed their scheduled payments or are currently in arrears.</p>
          <template #footer>
            <UButton block color="red" icon="i-lucide-download" @click="downloadArrears">Download PDF</UButton>
          </template>
        </UCard>

        <!-- Active Loans Report -->
        <UCard>
          <template #header>
            <div class="flex items-center gap-3">
              <UIcon name="i-lucide-banknote" class="w-6 h-6 text-green-500" />
              <h3 class="text-lg font-semibold">Active Loans Summary</h3>
            </div>
          </template>
          <p class="text-sm text-gray-500 mb-8">A complete summary of all currently active loans, showing the principal amounts issued to date.</p>
          <template #footer>
            <UButton block color="green" icon="i-lucide-download" @click="downloadActiveLoans">Download PDF</UButton>
          </template>
        </UCard>

        <!-- Customer Statement -->
        <UCard>
          <template #header>
            <div class="flex items-center gap-3">
              <UIcon name="i-lucide-user" class="w-6 h-6 text-blue-500" />
              <h3 class="text-lg font-semibold">Customer Loan Statement</h3>
            </div>
          </template>
          <p class="text-sm text-gray-500 mb-4">Generate a printable statement for a specific loan showing history of all payments made.</p>
          <UFormGroup label="Select Active Loan">
            <USelectMenu v-model="selectedCustomerLoan" :options="loans ? loans.map(l => ({ id: l.id, loan_number: l.loan_number })) : []" value-attribute="id" option-attribute="loan_number" placeholder="Search by Loan No..." searchable />
          </UFormGroup>
          <template #footer>
            <UButton block color="blue" icon="i-lucide-download" @click="downloadCustomerStatement" :disabled="!selectedCustomerLoan">Download PDF</UButton>
          </template>
        </UCard>

      </div>
    </template>
  </UDashboardPanel>
</template>
