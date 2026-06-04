<script setup lang="ts">
defineProps<{
  collapsed?: boolean
}>()
</script>

<template>
  <div class="flex items-center gap-2 px-2" :class="[!collapsed && 'py-2']">
    <img src="/logo.png" alt="LoanMaster" class="h-8 object-contain" />
  </div>
</template>
