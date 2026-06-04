<script setup lang="ts">
// Collectors Module
</script>

<template>
  <UDashboardPage>
    <UDashboardPanel grow>
      <UDashboardNavbar title="Collectors" badge="0" />

      <UDashboardToolbar>
        <template #left>
          <UInput icon="i-lucide-search" placeholder="Search collectors..." autofocus />
        </template>
        <template #right>
          <UButton label="Add Collector" icon="i-lucide-plus" />
        </template>
      </UDashboardToolbar>

      <div class="flex-1 overflow-y-auto p-4">
        <p class="text-gray-500">Collectors management module coming soon.</p>
      </div>
    </UDashboardPanel>
  </UDashboardPage>
</template>
