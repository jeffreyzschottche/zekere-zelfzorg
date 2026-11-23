export default defineNuxtConfig({
  srcDir: '.',
  css: ['~/assets/css/care.css'],
  app: { head: { htmlAttrs: { lang: 'nl' }, title: 'Zekere Zelfzorg' } },
  compatibilityDate: '2024-01-09',

  modules: ['@nuxtjs/tailwindcss', '@pinia/nuxt'],

  runtimeConfig: {
    public: {
      apiBaseUrl: process.env.NUXT_PUBLIC_API_BASE_URL || 'http://localhost:8000/api/v1',
    },
  },

  devtools: { enabled: true },
});
