<script setup lang="ts">
import type { PaginationMeta } from '~/types/pagination'
import { usePagination } from '~/composables/usePagination'

const props = defineProps<{
  meta: PaginationMeta
  currentPage: number
  label?: string
}>()

const emit = defineEmits<{
  'update:currentPage': [page: number]
}>()

const metaRef = computed(() => props.meta)
const currentPageRef = computed(() => props.currentPage)
const { visiblePages } = usePagination(metaRef, currentPageRef)

function goToPage(page: number) {
  if (page >= 1 && page <= props.meta.last_page) {
    emit('update:currentPage', page)
  }
}
</script>

<template>
  <div v-if="meta.last_page > 1" class="mt-4 flex items-center justify-between">
    <p class="text-sm text-gray-600">
      Showing {{ (meta.current_page - 1) * meta.per_page + 1 }} to {{ Math.min(meta.current_page * meta.per_page, meta.total) }} of {{ meta.total }} {{ label ?? 'items' }}
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
