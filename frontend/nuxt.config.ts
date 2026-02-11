// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  modules: ['@nuxtjs/tailwindcss'],
  runtimeConfig: {
    public: {
      apiBase: '/api-proxy',
    },
  },
  routeRules: {
    '/api-proxy/**': {
      proxy: `${process.env.API_BASE_URL || 'http://localhost:8000'}/**`,
    },
  },
})
