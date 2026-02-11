<script setup lang="ts">
import type { Campaign } from '~/types/campaign'
import { getErrorMessage } from '~/utils/error'

interface Template {
  id: number
  name: string
}

const SENDER_CHANNELS = [
  { value: 'smtp', label: 'SMTP' },
  { value: 'sendgrid', label: 'SendGrid' },
  { value: 'mailgun', label: 'Mailgun' },
] as const

const DEFAULT_SCHEDULED_TIME = '09:00'

const config = useRuntimeConfig()
const route = useRoute()
const router = useRouter()

const id = route.params.id as string

const [{ data: response, status, error }, { data: templatesResponse }] = await Promise.all([
  useFetch<{ data: Campaign }>(`${config.public.apiBase}/api/campaigns/${id}`),
  useFetch<{ data: Template[] }>(`${config.public.apiBase}/api/templates`),
])

const templates = computed(() => templatesResponse.value?.data ?? [])

const campaign = computed(() => response.value?.data ?? null)

const form = reactive({
  name: '',
  subject: '',
  template_id: null as number | null,
  sender_channel: '',
  scheduled_date: '',
  scheduled_time: DEFAULT_SCHEDULED_TIME,
})

watch(
  () => response.value,
  (val) => {
    if (val?.data) {
      form.name = val.data.name
      form.subject = val.data.subject
      form.template_id = val.data.template_id ?? null
      form.sender_channel = val.data.sender_channel
      if (val.data.scheduled_at) {
        const d = new Date(val.data.scheduled_at)
        form.scheduled_date = d.toISOString().slice(0, 10)
        form.scheduled_time = d.toTimeString().slice(0, 5)
      }
    }
  },
  { immediate: true }
)

const saving = ref(false)
const saveError = ref<string | null>(null)

async function submit() {
  saving.value = true
  saveError.value = null

  try {
    await $fetch(`${config.public.apiBase}/api/campaigns/${id}`, {
      method: 'PUT',
      body: {
        name: form.name,
        subject: form.subject,
        template_id: form.template_id,
        sender_channel: form.sender_channel,
        scheduled_at: form.scheduled_date
          ? `${form.scheduled_date}T${form.scheduled_time}:00`
          : null,
      },
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
    await $fetch(`${config.public.apiBase}/api/campaigns/${id}/dispatch`, {
      method: 'POST',
    })
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
      <NuxtLink to="/campaigns" class="text-sm text-gray-500 hover:text-gray-700">← Campaigns</NuxtLink>
      <h1 class="text-3xl font-bold text-gray-900">Edit Campaign</h1>
    </div>

    <div v-if="status === 'pending'" class="text-center text-gray-500">
      Loading campaign...
    </div>

    <div v-else-if="error" class="rounded-xl border border-red-200 bg-red-50 p-6 shadow-sm">
      <p class="text-sm text-red-600">Failed to load campaign. Please try again later.</p>
    </div>

    <template v-else>
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
            <option :value="null">— No template —</option>
            <option v-for="template in templates" :key="template.id" :value="template.id">
              {{ template.name }}
            </option>
          </select>
        </div>

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
            {{ saving ? 'Saving…' : 'Save Draft' }}
          </button>
          <NuxtLink
            to="/campaigns"
            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            Cancel
          </NuxtLink>
        </div>
      </form>

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
          {{ dispatching ? 'Starting…' : 'Start Campaign' }}
        </button>
      </div>
    </template>
  </div>
</template>
