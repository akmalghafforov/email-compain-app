<script setup lang="ts">
defineProps<{
  status: string
}>()

const statusColor: Record<string, string> = {
  active: 'bg-green-100 text-green-700',
  pending: 'bg-yellow-100 text-yellow-700',
  unsubscribed: 'bg-gray-100 text-gray-600',
  bounced: 'bg-red-100 text-red-700',
}
</script>

<template>
  <span
    class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
    :class="statusColor[status] ?? 'bg-gray-100 text-gray-600'"
  >
    {{ status }}
  </span>
</template>
