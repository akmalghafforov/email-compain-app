<script setup lang="ts">
import type { Campaign } from '~/types/campaign'
import type { Template } from '~/types/template'
import { getErrorMessage } from '~/utils/error'

const SENDER_CHANNELS = [
  { value: 'smtp', label: 'SMTP' },
  { value: 'sendgrid', label: 'SendGrid' },
  { value: 'mailgun', label: 'Mailgun' },
] as const

const TEMPLATE_ENGINES = [
  { value: 'blade', label: 'Blade' },
  { value: 'twig', label: 'Twig' },
  { value: 'markdown', label: 'Markdown' },
  { value: 'mjml', label: 'MJML' },
] as const

const CUSTOM_TEMPLATE = 'custom'
const DEFAULT_SCHEDULED_TIME = '09:00'

const config = useRuntimeConfig()
const router = useRouter()

const { data: templatesResponse } = await useFetch<{ data: Template[] }>(
  `${config.public.apiBase}/api/templates`,
)

const templates = computed(() => templatesResponse.value?.data ?? [])

const form = reactive({
  name: '',
  subject: '',
  template_id: null as number | null | typeof CUSTOM_TEMPLATE,
  sender_channel: 'smtp',
  scheduled_date: '',
  scheduled_time: DEFAULT_SCHEDULED_TIME,
  custom_template: {
    name: '',
    engine: 'blade' as string,
    subject_template: '',
    body_content: '',
  },
})

const isCustomTemplate = computed(() => form.template_id === CUSTOM_TEMPLATE)

const saving = ref(false)
const saveError = ref<string | null>(null)

async function submit() {
  saving.value = true
  saveError.value = null

  try {
    let templateId: number | null = isCustomTemplate.value ? null : (form.template_id as number | null)

    if (isCustomTemplate.value) {
      const created = await $fetch<{ data: Template }>(`${config.public.apiBase}/api/templates`, {
        method: 'POST',
        body: {
          name: form.custom_template.name,
          engine: form.custom_template.engine,
          subject_template: form.custom_template.subject_template,
          body_content: form.custom_template.body_content,
        },
      })
      templateId = created.data.id
    }

    const campaign = await $fetch<{ data: Campaign }>(`${config.public.apiBase}/api/campaigns`, {
      method: 'POST',
      body: {
        name: form.name,
        subject: form.subject,
        template_id: templateId,
        sender_channel: form.sender_channel,
        scheduled_at: form.scheduled_date
          ? `${form.scheduled_date}T${form.scheduled_time}:00`
          : null,
      },
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

    <form class="max-w-lg space-y-5" @submit.prevent="submit">
      <div v-if="saveError" class="rounded-xl border border-red-200 bg-red-50 p-4">
        <p class="text-sm text-red-600">{{ saveError }}</p>
      </div>

      <div>
        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
        <input
          id="name"
          v-model="form.name"
          type="text"
          required
          class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        />
      </div>

      <div>
        <label for="subject" class="block text-sm font-medium text-gray-700">Subject</label>
        <input
          id="subject"
          v-model="form.subject"
          type="text"
          required
          class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        />
      </div>

      <div>
        <label for="template_id" class="block text-sm font-medium text-gray-700">Template</label>
        <select
          id="template_id"
          v-model="form.template_id"
          class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        >
          <option :value="null">&mdash; No template &mdash;</option>
          <option v-for="template in templates" :key="template.id" :value="template.id">
            {{ template.name }}
          </option>
          <option :value="CUSTOM_TEMPLATE">+ Custom template&hellip;</option>
        </select>
      </div>

      <template v-if="isCustomTemplate">
        <div class="rounded-lg border border-indigo-100 bg-indigo-50 p-4 space-y-4">
          <p class="text-xs font-medium text-indigo-700 uppercase tracking-wider">New custom template</p>

          <div>
            <label for="custom_name" class="block text-sm font-medium text-gray-700">Template Name</label>
            <input
              id="custom_name"
              v-model="form.custom_template.name"
              type="text"
              required
              class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label for="custom_engine" class="block text-sm font-medium text-gray-700">Engine</label>
            <select
              id="custom_engine"
              v-model="form.custom_template.engine"
              class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            >
              <option v-for="engine in TEMPLATE_ENGINES" :key="engine.value" :value="engine.value">
                {{ engine.label }}
              </option>
            </select>
          </div>

          <div>
            <label for="custom_subject_template" class="block text-sm font-medium text-gray-700">Subject Template</label>
            <input
              id="custom_subject_template"
              v-model="form.custom_template.subject_template"
              type="text"
              required
              placeholder="e.g. Hello {{ name }}!"
              class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label for="custom_body_content" class="block text-sm font-medium text-gray-700">Body Content</label>
            <textarea
              id="custom_body_content"
              v-model="form.custom_template.body_content"
              rows="8"
              required
              class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 font-mono"
            />
          </div>
        </div>
      </template>

      <div>
        <label for="sender_channel" class="block text-sm font-medium text-gray-700">Sender Channel</label>
        <select
          id="sender_channel"
          v-model="form.sender_channel"
          class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        >
          <option v-for="channel in SENDER_CHANNELS" :key="channel.value" :value="channel.value">
            {{ channel.label }}
          </option>
        </select>
      </div>

      <div>
        <span class="block text-sm font-medium text-gray-700">Scheduled At</span>
        <div class="mt-1 flex gap-2">
          <input
            id="scheduled_date"
            v-model="form.scheduled_date"
            type="date"
            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          />
          <input
            id="scheduled_time"
            v-model="form.scheduled_time"
            type="time"
            :disabled="!form.scheduled_date"
            class="block w-36 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-400"
          />
          <button
            v-if="form.scheduled_date"
            type="button"
            class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-500 hover:bg-gray-50"
            @click="form.scheduled_date = ''; form.scheduled_time = DEFAULT_SCHEDULED_TIME"
          >
            Clear
          </button>
        </div>
        <p v-if="form.scheduled_date" class="mt-1 text-xs text-gray-400">
          Scheduled for {{ new Date(`${form.scheduled_date}T${form.scheduled_time}`).toLocaleString() }}
        </p>
      </div>

      <div class="flex gap-3 pt-2">
        <button
          type="submit"
          :disabled="saving"
          class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
        >
          {{ saving ? 'Creating…' : 'Create Campaign' }}
        </button>
        <NuxtLink
          to="/campaigns"
          class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
        >
          Cancel
        </NuxtLink>
      </div>
    </form>
  </div>
</template>
