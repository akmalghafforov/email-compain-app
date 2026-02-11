<script setup lang="ts">
import type { Campaign } from '~/types/campaign'
import type { CampaignStats } from '~/types/campaignStats'
import { formatDateTime, formatPercent } from '~/utils/date'

const { url } = useApi()
const route = useRoute()

const id = route.params.id as string

const [{ data: campaignResponse, status, error }, { data: statsResponse }] = await Promise.all([
  useFetch<{ data: Campaign }>(url(`/api/campaigns/${id}`)),
  useFetch<{ data: CampaignStats }>(url(`/api/campaigns/${id}/stats`)),
])

const campaign = computed(() => campaignResponse.value?.data ?? null)
const stats = computed(() => statsResponse.value?.data ?? null)

if (campaign.value && campaign.value.status !== 'sent' && campaign.value.status !== 'failed') {
  await navigateTo(`/campaigns/${id}/edit`)
}
</script>

<template>
  <div>
    <div class="mb-6 flex items-center gap-3">
      <NuxtLink to="/campaigns" class="text-sm text-gray-500 hover:text-gray-700">← Campaigns</NuxtLink>
      <h1 class="text-3xl font-bold text-gray-900">Campaign Details</h1>
    </div>

    <div v-if="status === 'pending'" class="max-w-3xl space-y-6">
      <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="h-5 w-32 animate-pulse rounded bg-gray-200" />
        <div class="mt-4 grid grid-cols-2 gap-x-6 gap-y-4">
          <div v-for="i in 6" :key="i">
            <div class="h-3 w-20 animate-pulse rounded bg-gray-200" />
            <div class="mt-2 h-4 w-28 animate-pulse rounded bg-gray-100" />
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="error" class="rounded-xl border border-red-200 bg-red-50 p-6 shadow-sm">
      <p class="text-sm text-red-600">Failed to load campaign. Please try again later.</p>
    </div>

    <template v-else-if="campaign">
      <div class="max-w-3xl space-y-6">
        <!-- Campaign Info -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
          <h2 class="text-lg font-semibold text-gray-900">Campaign Info</h2>
          <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <div>
              <dt class="font-medium text-gray-500">Name</dt>
              <dd class="mt-1 text-gray-900">{{ campaign.name }}</dd>
            </div>
            <div>
              <dt class="font-medium text-gray-500">Subject</dt>
              <dd class="mt-1 text-gray-900">{{ campaign.subject }}</dd>
            </div>
            <div>
              <dt class="font-medium text-gray-500">Sender Channel</dt>
              <dd class="mt-1 text-gray-900">{{ campaign.sender_channel }}</dd>
            </div>
            <div>
              <dt class="font-medium text-gray-500">Status</dt>
              <dd class="mt-1"><StatusBadge :status="campaign.status" /></dd>
            </div>
            <div>
              <dt class="font-medium text-gray-500">Scheduled At</dt>
              <dd class="mt-1 text-gray-900">{{ formatDateTime(campaign.scheduled_at) }}</dd>
            </div>
            <div>
              <dt class="font-medium text-gray-500">Sent At</dt>
              <dd class="mt-1 text-gray-900">{{ formatDateTime(campaign.sent_at) }}</dd>
            </div>
          </dl>
        </div>

        <!-- Delivery Stats -->
        <div v-if="stats" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
          <h2 class="text-lg font-semibold text-gray-900">Delivery Statistics</h2>
          <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
              <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Recipients</p>
              <p class="mt-1 text-2xl font-bold text-gray-900">{{ stats.totalRecipients.toLocaleString() }}</p>
            </div>
            <div class="rounded-lg border border-green-100 bg-green-50 p-4">
              <p class="text-xs font-medium uppercase tracking-wider text-green-600">Sent</p>
              <p class="mt-1 text-2xl font-bold text-green-700">{{ stats.totalSent.toLocaleString() }}</p>
            </div>
            <div class="rounded-lg border border-red-100 bg-red-50 p-4">
              <p class="text-xs font-medium uppercase tracking-wider text-red-600">Failed</p>
              <p class="mt-1 text-2xl font-bold text-red-700">{{ stats.totalFailed.toLocaleString() }}</p>
            </div>
            <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">
              <p class="text-xs font-medium uppercase tracking-wider text-blue-600">Opened</p>
              <p class="mt-1 text-2xl font-bold text-blue-700">{{ stats.totalOpened.toLocaleString() }}</p>
            </div>
            <div class="rounded-lg border border-indigo-100 bg-indigo-50 p-4">
              <p class="text-xs font-medium uppercase tracking-wider text-indigo-600">Clicked</p>
              <p class="mt-1 text-2xl font-bold text-indigo-700">{{ stats.totalClicked.toLocaleString() }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
              <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Open Rate</p>
              <p class="mt-1 text-2xl font-bold text-gray-900">{{ formatPercent(stats.openRate) }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
              <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Click Rate</p>
              <p class="mt-1 text-2xl font-bold text-gray-900">{{ formatPercent(stats.clickRate) }}</p>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
