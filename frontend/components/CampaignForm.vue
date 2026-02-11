<script setup lang="ts">
import type { Campaign } from '~/types/campaign'
import type { Template } from '~/types/template'
import {
  SENDER_CHANNELS,
  TEMPLATE_ENGINES,
  CUSTOM_TEMPLATE,
  DEFAULT_SCHEDULED_TIME,
} from '~/constants/campaign'
import { buildISOString, parseDateParts } from '~/utils/date'

const props = defineProps<{
  initialData?: Campaign | null
  templates: Template[]
  saving: boolean
  saveError: string | null
  submitLabel: string
  submittingLabel: string
}>()

const emit = defineEmits<{
  submit: [payload: {
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
  }]
}>()

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

const errors = reactive<Record<string, string>>({})

const isCustomTemplate = computed(() => form.template_id === CUSTOM_TEMPLATE)

if (props.initialData) {
  populateForm(props.initialData)
}

watch(
  () => props.initialData,
  (val) => {
    if (val) populateForm(val)
  },
)

function populateForm(data: Campaign) {
  form.name = data.name
  form.subject = data.subject
  form.template_id = data.template_id ?? null
  form.sender_channel = data.sender_channel
  if (data.scheduled_at) {
    const parts = parseDateParts(data.scheduled_at)
    form.scheduled_date = parts.date
    form.scheduled_time = parts.time
  }
}

function validate(): boolean {
  // Clear previous errors
  for (const key of Object.keys(errors)) {
    delete errors[key]
  }

  if (!form.name.trim()) {
    errors.name = 'Name is required.'
  } else if (form.name.length > 255) {
    errors.name = 'Name must be 255 characters or less.'
  }

  if (!form.subject.trim()) {
    errors.subject = 'Subject is required.'
  } else if (form.subject.length > 255) {
    errors.subject = 'Subject must be 255 characters or less.'
  }

  if (isCustomTemplate.value) {
    if (!form.custom_template.name.trim()) {
      errors.custom_name = 'Template name is required.'
    }
    if (!form.custom_template.subject_template.trim()) {
      errors.custom_subject_template = 'Subject template is required.'
    }
    if (!form.custom_template.body_content.trim()) {
      errors.custom_body_content = 'Body content is required.'
    } else if (form.custom_template.body_content.length > 65535) {
      errors.custom_body_content = 'Body content must be 65,535 characters or less.'
    }
  }

  if (form.scheduled_date) {
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    const scheduled = new Date(form.scheduled_date + 'T00:00:00')
    if (scheduled < today) {
      errors.scheduled_date = 'Scheduled date must be today or in the future.'
    }
  }

  return Object.keys(errors).length === 0
}

function handleSubmit() {
  if (!validate()) return

  const templateId = isCustomTemplate.value ? null : (form.template_id as number | null)

  const payload: Parameters<typeof emit>[1] = {
    name: form.name,
    subject: form.subject,
    template_id: templateId,
    sender_channel: form.sender_channel,
    scheduled_at: form.scheduled_date
      ? buildISOString(form.scheduled_date, form.scheduled_time)
      : null,
  }

  if (isCustomTemplate.value) {
    payload.custom_template = { ...form.custom_template }
  }

  emit('submit', payload)
}

function clearSchedule() {
  form.scheduled_date = ''
  form.scheduled_time = DEFAULT_SCHEDULED_TIME
}
</script>

<template>
  <form class="max-w-lg space-y-5" @submit.prevent="handleSubmit">
    <div v-if="saveError" class="rounded-xl border border-red-200 bg-red-50 p-4">
      <p class="text-sm text-red-600">{{ saveError }}</p>
    </div>

    <div>
      <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
      <input
        id="name"
        v-model="form.name"
        type="text"
        class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
      />
      <p v-if="errors.name" class="mt-1 text-xs text-red-600">{{ errors.name }}</p>
    </div>

    <div>
      <label for="subject" class="block text-sm font-medium text-gray-700">Subject</label>
      <input
        id="subject"
        v-model="form.subject"
        type="text"
        class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
      />
      <p v-if="errors.subject" class="mt-1 text-xs text-red-600">{{ errors.subject }}</p>
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
            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          />
          <p v-if="errors.custom_name" class="mt-1 text-xs text-red-600">{{ errors.custom_name }}</p>
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
            placeholder="e.g. Hello {{ name }}!"
            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          />
          <p v-if="errors.custom_subject_template" class="mt-1 text-xs text-red-600">{{ errors.custom_subject_template }}</p>
        </div>

        <div>
          <label for="custom_body_content" class="block text-sm font-medium text-gray-700">Body Content</label>
          <textarea
            id="custom_body_content"
            v-model="form.custom_template.body_content"
            rows="8"
            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 font-mono"
          />
          <p v-if="errors.custom_body_content" class="mt-1 text-xs text-red-600">{{ errors.custom_body_content }}</p>
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
          @click="clearSchedule"
        >
          Clear
        </button>
      </div>
      <p v-if="errors.scheduled_date" class="mt-1 text-xs text-red-600">{{ errors.scheduled_date }}</p>
      <p v-else-if="form.scheduled_date" class="mt-1 text-xs text-gray-400">
        Scheduled for {{ new Date(buildISOString(form.scheduled_date, form.scheduled_time)).toLocaleString() }}
      </p>
    </div>

    <div class="flex gap-3 pt-2">
      <button
        type="submit"
        :disabled="saving"
        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
      >
        {{ saving ? submittingLabel : submitLabel }}
      </button>
      <NuxtLink
        to="/campaigns"
        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
      >
        Cancel
      </NuxtLink>
    </div>
  </form>
</template>
