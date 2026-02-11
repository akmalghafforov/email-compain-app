<script setup lang="ts">
import type { Template } from '~/types/template'
import { useListPage } from '~/composables/useListPage'

const { currentPage, items: templates, meta, status, error } = useListPage<Template>('/api/templates')
</script>

<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900">Templates</h1>
    <p class="mt-2 text-gray-600">Manage your email templates here.</p>

    <div v-if="status === 'pending'" class="mt-6 text-center text-gray-500">
      Loading templates...
    </div>

    <div v-else-if="error" class="mt-6 rounded-xl border border-red-200 bg-red-50 p-6 shadow-sm">
      <p class="text-sm text-red-600">Failed to load templates. Please try again later.</p>
    </div>

    <div v-else-if="templates.length === 0" class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
      <p class="text-sm text-gray-500">No templates yet. Design your first email template.</p>
    </div>

    <template v-else>
      <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Engine</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Subject Template</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Created At</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="template in templates" :key="template.id" class="hover:bg-gray-50">
              <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                {{ template.name }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                {{ template.engine }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">
                {{ template.subject_template }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                {{ new Date(template.created_at).toLocaleDateString() }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AppPagination v-if="meta" :meta="meta" v-model:current-page="currentPage" label="templates" />
    </template>
  </div>
</template>
