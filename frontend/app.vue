<template>
  <NuxtLayout>
    <NuxtErrorBoundary>
      <NuxtPage />
      <template #error="{ error, clearError }">
        <div class="mx-auto max-w-lg py-16 text-center">
          <h2 class="text-xl font-semibold text-gray-900">Something went wrong</h2>
          <p class="mt-2 text-sm text-gray-600">{{ error.message || 'An unexpected error occurred.' }}</p>
          <button
            class="mt-4 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
            @click="clearError"
          >
            Try again
          </button>
        </div>
      </template>
    </NuxtErrorBoundary>
  </NuxtLayout>
</template>
