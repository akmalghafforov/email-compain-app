<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
    <p class="mt-2 text-gray-600">Welcome to the Email Campaign manager.</p>

    <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <NuxtLink
        v-for="card in cards"
        :key="card.to"
        :to="card.to"
        class="group rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md hover:border-indigo-300"
      >
        <h2 class="text-lg font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors">
          {{ card.label }}
        </h2>
        <p class="mt-1 text-sm text-gray-500">{{ card.description }}</p>
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
const cards = navLinks
</script>
