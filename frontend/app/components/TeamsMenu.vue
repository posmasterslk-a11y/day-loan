<script setup lang="ts">
defineProps<{
  collapsed?: boolean
}>()
</script>

<template>
  <UButton
    icon="i-lucide-landmark"
    :label="collapsed ? undefined : 'Micro Finance v1'"
    color="neutral"
    variant="ghost"
    block
    :square="collapsed"
    :class="[!collapsed && 'py-2']"
    class="font-bold text-lg"
    :ui="{ leadingIcon: 'text-primary w-6 h-6' }"
  />
</template>
