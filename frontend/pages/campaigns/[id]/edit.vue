<script setup lang="ts">
import type { Campaign } from '~/types/campaign'
import type { Template } from '~/types/template'
import { getErrorMessage } from '~/utils/error'

const { url, post, put } = useApi()
const route = useRoute()
const router = useRouter()

const id = route.params.id as string

const [{ data: response, status, error }, { data: templatesResponse }] = await Promise.all([
  useFetch<{ data: Campaign }>(url(`/api/campaigns/${id}`)),
  useFetch<{ data: Template[] }>(url('/api/templates')),
])

const templates = computed(() => templatesResponse.value?.data ?? [])
const campaign = computed(() => response.value?.data ?? null)

const saving = ref(false)
const saveError = ref<string | null>(null)

type FormPayload = {
  name: string
  subject: string
  template_id: number | null
  sender_channel: string
  scheduled_at: string | null
  custom_template?: {
    name: string
    engine: string
    subject_template: string
    body_content: string
  }
}

async function handleSubmit(payload: FormPayload) {
  saving.value = true
  saveError.value = null

  try {
    let templateId = payload.template_id

    if (payload.custom_template) {
      const created = await post<{ data: Template }>('/api/templates', payload.custom_template as unknown as Record<string, unknown>)
      templateId = created.data.id
    }

    await put(`/api/campaigns/${id}`, {
      name: payload.name,
      subject: payload.subject,
      template_id: templateId,
      sender_channel: payload.sender_channel,
      scheduled_at: payload.scheduled_at,
    })
    router.push('/campaigns')
  } catch (err: unknown) {
    saveError.value = getErrorMessage(err, 'Failed to save campaign.')
  } finally {
    saving.value = false
  }
}

const dispatching = ref(false)
const dispatchError = ref<string | null>(null)

async function dispatch() {
  dispatching.value = true
  dispatchError.value = null

  try {
    await post(`/api/campaigns/${id}/dispatch`)
    router.push('/campaigns')
  } catch (err: unknown) {
    dispatchError.value = getErrorMessage(err, 'Failed to start campaign.')
  } finally {
    dispatching.value = false
  }
}

const canStart = computed(() => campaign.value?.status === 'draft')
</script>

<template>
  <div>
    <div class="mb-6 flex items-center gap-3">
      <NuxtLink to="/campaigns" class="text-sm text-gray-500 hover:text-gray-700">&larr; Campaigns</NuxtLink>
      <h1 class="text-3xl font-bold text-gray-900">Edit Campaign</h1>
    </div>

    <div v-if="status === 'pending'">
      <FormSkeleton />
    </div>

    <div v-else-if="error" class="rounded-xl border border-red-200 bg-red-50 p-6 shadow-sm">
      <p class="text-sm text-red-600">Failed to load campaign. Please try again later.</p>
    </div>

    <template v-else>
      <CampaignForm
        :initial-data="campaign"
        :templates="templates"
        :saving="saving"
        :save-error="saveError"
        submit-label="Save Draft"
        submitting-label="Saving\u2026"
        @submit="handleSubmit"
      />

      <div class="mt-8 max-w-lg border-t border-gray-200 pt-6">
        <h2 class="text-sm font-medium text-gray-900">Campaign Actions</h2>
        <p class="mt-1 text-sm text-gray-500">
          Starting a campaign will begin collecting subscribers and sending emails.
        </p>
        <div v-if="dispatchError" class="mt-3 rounded-xl border border-red-200 bg-red-50 p-4">
          <p class="text-sm text-red-600">{{ dispatchError }}</p>
        </div>
        <button
          type="button"
          :disabled="dispatching || !canStart"
          :title="!canStart ? 'Only draft campaigns can be started' : undefined"
          class="mt-4 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
          @click="dispatch"
        >
          {{ dispatching ? 'Starting\u2026' : 'Start Campaign' }}
        </button>
      </div>
    </template>
  </div>
</template>
