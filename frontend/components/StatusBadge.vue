<script setup lang="ts">
defineProps<{
  status: string
}>()

const statusColor: Record<string, string> = {
  active: 'bg-green-100 text-green-700',
  pending: 'bg-yellow-100 text-yellow-700',
  unsubscribed: 'bg-gray-100 text-gray-600',
  bounced: 'bg-red-100 text-red-700',
  draft: 'bg-gray-100 text-gray-600',
  started: 'bg-blue-100 text-blue-700',
  collecting_subscribers: 'bg-yellow-100 text-yellow-700',
  subscribers_collected: 'bg-indigo-100 text-indigo-700',
  sending: 'bg-blue-100 text-blue-700',
  sent: 'bg-green-100 text-green-700',
  partially_sent: 'bg-orange-100 text-orange-700',
  failed: 'bg-red-100 text-red-700',
  cancelled: 'bg-gray-100 text-gray-600',
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
