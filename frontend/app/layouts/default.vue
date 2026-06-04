<script setup lang="ts">
import type { NavigationMenuItem } from '@nuxt/ui'

const route = useRoute()
const toast = useToast()

const open = ref(false)

const dashboardLinks = [[
  { label: 'Dashboard', icon: 'i-lucide-layout-dashboard', to: '/', onSelect: () => { open.value = false } }
]] satisfies NavigationMenuItem[][]

const loanLinks = [[
  { label: 'Cus Management', icon: 'i-lucide-users', to: '/customers', onSelect: () => { open.value = false } },
  { label: 'Guaranter', icon: 'i-lucide-shield-check', to: '/guarantors', onSelect: () => { open.value = false } },
  { label: 'Issue Loan', icon: 'i-lucide-banknote', to: '/loans', onSelect: () => { open.value = false } },
  { label: 'Collections', icon: 'i-lucide-wallet', to: '/collections', onSelect: () => { open.value = false } },
  { label: 'Arreas', icon: 'i-lucide-alert-triangle', to: '/arrears', onSelect: () => { open.value = false } }
]] satisfies NavigationMenuItem[][]

const accountLinks = [[
  { label: 'Main Cashbook', icon: 'i-lucide-book-text', to: '/main-cashbook', onSelect: () => { open.value = false } },
  { label: 'Teller Cashbook', icon: 'i-lucide-receipt', to: '/officer-cashbook', onSelect: () => { open.value = false } },
  { label: 'Bank Book', icon: 'i-lucide-landmark', to: '/bank-book', onSelect: () => { open.value = false } },
  { label: 'General Ledger', icon: 'i-lucide-file-spreadsheet', to: '/general-ledger', onSelect: () => { open.value = false } },
  { label: 'Payment & Receipt', icon: 'i-lucide-file-text', to: '/payments-receipts', onSelect: () => { open.value = false } }
]] satisfies NavigationMenuItem[][]

const managementLinks = [[
  { label: 'User Account Management', icon: 'i-lucide-users-2', to: '/users', onSelect: () => { open.value = false } },
  { label: 'Reports', icon: 'i-lucide-file-bar-chart-2', to: '/reports', onSelect: () => { open.value = false } },
  { label: 'Branches', icon: 'i-lucide-git-branch', to: '/branches', onSelect: () => { open.value = false } },
  { label: 'System Settings', icon: 'i-lucide-settings', to: '/settings', onSelect: () => { open.value = false } }
]] satisfies NavigationMenuItem[][]

const groups = computed(() => [{
  id: 'dashboard',
  label: 'Dashboard',
  items: dashboardLinks.flat()
}, {
  id: 'loan',
  label: 'Loan',
  items: loanLinks.flat()
}, {
  id: 'accounts',
  label: 'Accounts',
  items: accountLinks.flat()
}, {
  id: 'management',
  label: 'Management',
  items: managementLinks.flat()
}])

onMounted(async () => {
  const cookie = useCookie('cookie-consent')
  if (cookie.value === 'accepted') {
    return
  }

  toast.add({
    title: 'We use first-party cookies to enhance your experience on our website.',
    duration: 0,
    close: false,
    actions: [{
      label: 'Accept',
      color: 'neutral',
      variant: 'outline',
      onClick: () => {
        cookie.value = 'accepted'
      }
    }, {
      label: 'Opt out',
      color: 'neutral',
      variant: 'ghost'
    }]
  })
})
</script>

<template>
  <UDashboardGroup unit="rem">
    <UDashboardSidebar
      id="default"
      v-model:open="open"
      collapsible
      resizable
      class="bg-elevated/25"
      :ui="{ footer: 'lg:border-t lg:border-default' }"
    >
      <template #header="{ collapsed }">
        <TeamsMenu :collapsed="collapsed" />
      </template>

      <template #default="{ collapsed }">
        <UDashboardSearchButton :collapsed="collapsed" class="bg-transparent ring-default" />

        <div class="mt-4">
          <UNavigationMenu
            :collapsed="collapsed"
            :items="dashboardLinks[0]"
            orientation="vertical"
            tooltip
            popover
          />
        </div>

        <div class="px-2 mt-4 text-xs font-semibold text-muted-foreground uppercase tracking-wider" v-if="!collapsed">Loan</div>
        <UNavigationMenu
          :collapsed="collapsed"
          :items="loanLinks[0]"
          orientation="vertical"
          tooltip
          popover
        />

        <div class="px-2 mt-4 text-xs font-semibold text-muted-foreground uppercase tracking-wider" v-if="!collapsed">Accounts</div>
        <UNavigationMenu
          :collapsed="collapsed"
          :items="accountLinks[0]"
          orientation="vertical"
          tooltip
          popover
        />

        <div class="px-2 mt-4 text-xs font-semibold text-muted-foreground uppercase tracking-wider" v-if="!collapsed">Management</div>
        <UNavigationMenu
          :collapsed="collapsed"
          :items="managementLinks[0]"
          orientation="vertical"
          tooltip
          popover
          class="mb-auto"
        />
      </template>

      <template #footer="{ collapsed }">
        <UserMenu :collapsed="collapsed" />
      </template>
    </UDashboardSidebar>

    <UDashboardSearch :groups="groups" />

    <slot />

    <NotificationsSlideover />
  </UDashboardGroup>
</template>
