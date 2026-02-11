<script setup lang="ts">
withDefaults(defineProps<{
  rows?: number
  columns?: number
}>(), {
  rows: 5,
  columns: 4,
})
</script>

<template>
  <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th v-for="col in columns" :key="col" class="px-6 py-3">
            <div class="h-3 w-20 animate-pulse rounded bg-gray-200" />
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200">
        <tr v-for="row in rows" :key="row">
          <td v-for="col in columns" :key="col" class="whitespace-nowrap px-6 py-4">
            <div class="h-4 animate-pulse rounded bg-gray-100" :class="col === 1 ? 'w-32' : 'w-20'" />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
