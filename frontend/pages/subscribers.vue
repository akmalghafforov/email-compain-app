<script setup lang="ts">
import type { PaginatedResponse, Subscriber } from '~/types/subscriber'

const config = useRuntimeConfig()
const currentPage = ref(1)
const perPage = 15

const { data: response, status, error } = await useFetch<PaginatedResponse<Subscriber>>(
  computed(() => `${config.public.apiBase}/api/subscribers?page=${currentPage.value}&per_page=${perPage}`)
)

const subscribers = computed(() => response.value?.data ?? [])
const meta = computed(() => response.value?.meta)
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

    <template v-else>
      <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
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
                <StatusBadge :status="subscriber.status" />
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                {{ subscriber.subscribed_at ? new Date(subscriber.subscribed_at).toLocaleDateString() : '—' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AppPagination v-if="meta" :meta="meta" v-model:current-page="currentPage" />
    </template>
  </div>
</template>
