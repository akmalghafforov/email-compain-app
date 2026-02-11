<template>
  <nav aria-label="Main navigation" class="bg-slate-900 px-4 shadow-lg">

    <div class="mx-auto flex h-14 max-w-6xl items-center justify-between">

      <NuxtLink to="/" class="text-lg font-bold text-white tracking-tight hover:text-indigo-300 transition-colors">
        Email Campaign
      </NuxtLink>

      <div class="flex items-center gap-1">

        <NuxtLink
          v-for="link in links"
          :key="link.to"
          :to="link.to"
          class="rounded-md px-3 py-2 text-sm font-medium text-slate-300 transition-colors hover:bg-slate-800 hover:text-white"
          active-class="!bg-indigo-600 !text-white"
        >
          {{ link.label }}
        </NuxtLink>

      </div>
    </div>
  </nav>

</template>

<script setup lang="ts">
const links = navLinks
</script>
