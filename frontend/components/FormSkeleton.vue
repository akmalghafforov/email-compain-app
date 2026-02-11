<template>
  <div class="max-w-lg space-y-5">
    <div v-for="i in 5" :key="i">
      <div class="h-4 w-24 animate-pulse rounded bg-gray-200" />
      <div class="mt-2 h-10 w-full animate-pulse rounded-lg bg-gray-100" />
    </div>
    <div class="flex gap-3 pt-2">
      <div class="h-10 w-32 animate-pulse rounded-lg bg-gray-200" />
      <div class="h-10 w-20 animate-pulse rounded-lg bg-gray-100" />
    </div>
  </div>
</template>
