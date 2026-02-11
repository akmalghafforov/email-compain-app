<script setup lang="ts">
import type { Campaign } from '~/types/campaign'
import type { Template } from '~/types/template'
import { getErrorMessage } from '~/utils/error'

const { url, post } = useApi()
const router = useRouter()

const { data: templatesResponse } = await useFetch<{ data: Template[] }>(url('/api/templates'))
const templates = computed(() => templatesResponse.value?.data ?? [])

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

    const campaign = await post<{ data: Campaign }>('/api/campaigns', {
      name: payload.name,
      subject: payload.subject,
      template_id: templateId,
      sender_channel: payload.sender_channel,
      scheduled_at: payload.scheduled_at,
    })
    router.push(`/campaigns/${campaign.data.id}/edit`)
  } catch (err: unknown) {
    saveError.value = getErrorMessage(err, 'Failed to create campaign.')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div>
    <div class="mb-6 flex items-center gap-3">
      <NuxtLink to="/campaigns" class="text-sm text-gray-500 hover:text-gray-700">&larr; Campaigns</NuxtLink>
      <h1 class="text-3xl font-bold text-gray-900">New Campaign</h1>
    </div>

    <CampaignForm
      :templates="templates"
      :saving="saving"
      :save-error="saveError"
      submit-label="Create Campaign"
      submitting-label="Creating\u2026"
      @submit="handleSubmit"
    />
  </div>
</template>
