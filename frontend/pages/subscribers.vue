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

interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

interface PaginatedResponse {
  data: Subscriber[]
  meta: PaginationMeta
}

const currentPage = ref(1)
const perPage = 15

const url = computed(() =>
  `${config.public.apiBase}/api/subscribers?page=${currentPage.value}&per_page=${perPage}`
)

const { data: response, status, error } = await useFetch<PaginatedResponse>(url)

const subscribers = computed(() => response.value?.data ?? [])
const meta = computed(() => response.value?.meta)

function goToPage(page: number) {
  if (page >= 1 && page <= (meta.value?.last_page ?? 1)) {
    currentPage.value = page
  }
}

const visiblePages = computed(() => {
  const last = meta.value?.last_page ?? 1
  const current = currentPage.value
  const pages: number[] = []

  let start = Math.max(1, current - 2)
  let end = Math.min(last, current + 2)

  if (current <= 3) {
    end = Math.min(last, 5)
  }
  if (current >= last - 2) {
    start = Math.max(1, last - 4)
  }

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }

  return pages
})

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

      <!-- Pagination -->
      <div v-if="meta && meta.last_page > 1" class="mt-4 flex items-center justify-between">
        <p class="text-sm text-gray-600">
          Showing {{ (meta.current_page - 1) * meta.per_page + 1 }} to {{ Math.min(meta.current_page * meta.per_page, meta.total) }} of {{ meta.total }} subscribers
        </p>

        <nav class="flex items-center gap-1">
          <button
            :disabled="currentPage === 1"
            class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
            @click="goToPage(currentPage - 1)"
          >
            Previous
          </button>

          <button
            v-for="page in visiblePages"
            :key="page"
            class="rounded-lg px-3 py-2 text-sm font-medium"
            :class="page === currentPage ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'"
            @click="goToPage(page)"
          >
            {{ page }}
          </button>

          <button
            :disabled="currentPage === meta.last_page"
            class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
            @click="goToPage(currentPage + 1)"
          >
            Next
          </button>
        </nav>
      </div>
    </template>
  </div>
</template>
