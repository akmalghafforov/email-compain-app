<script setup lang="ts">
import type { Campaign } from '~/types/campaign'
import { useListPage } from '~/composables/useListPage'

const { currentPage, items: campaigns, meta, status, error } = useListPage<Campaign>('/api/campaigns')
</script>

<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900">Campaigns</h1>
    <p class="mt-2 text-gray-600">Manage your campaigns here.</p>

    <div v-if="status === 'pending'" class="mt-6 text-center text-gray-500">
      Loading campaigns...
    </div>

    <div v-else-if="error" class="mt-6 rounded-xl border border-red-200 bg-red-50 p-6 shadow-sm">
      <p class="text-sm text-red-600">Failed to load campaigns. Please try again later.</p>
    </div>

    <div v-else-if="campaigns.length === 0" class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
      <p class="text-sm text-gray-500">No campaigns yet. Create your first campaign to get started.</p>
    </div>

    <template v-else>
      <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Scheduled At</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Sent At</th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Recipients</th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Sent</th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Failed</th>
              <th class="px-6 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="campaign in campaigns" :key="campaign.id" class="hover:bg-gray-50">
              <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                {{ campaign.name }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm">
                <StatusBadge :status="campaign.status" />
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                {{ campaign.scheduled_at ? new Date(campaign.scheduled_at).toLocaleDateString() : '—' }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                {{ campaign.sent_at ? new Date(campaign.sent_at).toLocaleDateString() : '—' }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-700">
                {{ (campaign.total_recipients ?? 0).toLocaleString() }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-700">
                {{ (campaign.total_sent ?? 0).toLocaleString() }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                <span v-if="(campaign.total_failed ?? 0) > 0" class="text-red-600">
                  {{ (campaign.total_failed ?? 0).toLocaleString() }}
                </span>
                <span v-else class="text-gray-400">—</span>
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm">

                <NuxtLink
                  v-if="campaign.status === 'sent'"
                  :to="`/campaigns/${campaign.id}`"
                  class="font-medium text-indigo-600 hover:text-indigo-800"
                >
                  View
                </NuxtLink>

                <NuxtLink
                  v-else
                  :to="`/campaigns/${campaign.id}/edit`"
                  class="font-medium text-indigo-600 hover:text-indigo-800"
                >
                  Edit
                </NuxtLink>

              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AppPagination v-if="meta" :meta="meta" v-model:current-page="currentPage" label="campaigns" />
    </template>
  </div>
</template>
