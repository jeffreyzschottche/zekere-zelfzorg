<script setup lang="ts">
definePageMeta({ middleware: 'guest', layout: 'auth' })

const auth = useAuthStore()
const route = useRoute()
const form = reactive({ email: '', password: '' })
const error = ref('')
const loading = ref(false)

async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(form.email, form.password)
    const target = route.query.redirect
    const redirect = typeof target === 'string' && /^\/(dashboard|handeling|oefenen)(\?|$)/.test(target)
      ? target : '/dashboard'
    await navigateTo(redirect)
  } catch {
    error.value = 'Inloggen is niet gelukt. Controleer je gegevens en probeer opnieuw.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="care-card care-stack">
    <header class="care-stack">
      <h1 class="care-title text-4xl">Inloggen</h1>
      <p>Open je persoonlijke zorgomgeving.</p>
    </header>
    <form class="care-stack" @submit.prevent="handleLogin">
      <p v-if="error" class="care-alert" role="alert">{{ error }}</p>
      <label>E-mailadres <input v-model="form.email" autocomplete="username" type="email" required class="care-field" /></label>
      <label>Wachtwoord <input v-model="form.password" autocomplete="current-password" type="password" required class="care-field" /></label>
      <button type="submit" :disabled="loading" class="care-button">{{ loading ? 'Bezig met inloggen…' : 'Inloggen' }}</button>
    </form>
    <div class="care-row">
      <NuxtLink to="/forgot-password" class="underline">Wachtwoord vergeten?</NuxtLink>
      <NuxtLink to="/register" class="underline">Account aanmaken</NuxtLink>
    </div>
  </div>
</template>
