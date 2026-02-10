<script setup lang="ts">
const config = useRuntimeConfig()

interface Subscriber {
  id: number
  email: string
  name: string | null
  status: string
  metadata: Record<string, unknown> | null
  subscribed_at: string | null
  unsubscribed_at: string | null
  created_at: string
  updated_at: string
}

const { data: response, status, error } = await useFetch<{ data: Subscriber[] }>(
  `${config.public.apiBase}/api/subscribers`
)

const subscribers = computed(() => response.value?.data ?? [])

const statusColor: Record<string, string> = {
  active: 'bg-green-100 text-green-700',
  pending: 'bg-yellow-100 text-yellow-700',
  unsubscribed: 'bg-gray-100 text-gray-600',
  bounced: 'bg-red-100 text-red-700',
}
</script>

<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900">Subscribers</h1>
    <p class="mt-2 text-gray-600">Manage your subscribers here.</p>

    <div v-if="status === 'pending'" class="mt-6 text-center text-gray-500">
      Loading subscribers...
    </div>

    <div v-else-if="error" class="mt-6 rounded-xl border border-red-200 bg-red-50 p-6 shadow-sm">
      <p class="text-sm text-red-600">Failed to load subscribers. Please try again later.</p>
    </div>

    <div v-else-if="subscribers.length === 0" class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
      <p class="text-sm text-gray-500">No subscribers yet. Start by importing or adding subscribers.</p>
    </div>

    <div v-else class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Email</th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Subscribed At</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="subscriber in subscribers" :key="subscriber.id" class="hover:bg-gray-50">
            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
              {{ subscriber.name || '—' }}
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
              {{ subscriber.email }}
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-sm">
              <span
                class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                :class="statusColor[subscriber.status] ?? 'bg-gray-100 text-gray-600'"
              >
                {{ subscriber.status }}
              </span>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
              {{ subscriber.subscribed_at ? new Date(subscriber.subscribed_at).toLocaleDateString() : '—' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
