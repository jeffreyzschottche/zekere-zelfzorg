<script setup lang="ts">
const auth = useAuthStore()
const api = useApi()
async function logout() {
  try {
    await api.post('/logout')
  } finally {
    auth.logout()
    await navigateTo('/login')
  }
}
</script>

<template>
  <a href="#inhoud" class="care-button care-skip">Direct naar inhoud</a>
  <header class="care-header">
    <nav class="care-wrap care-nav" aria-label="Hoofdnavigatie">
      <NuxtLink to="/" class="care-brand"><span class="care-mark" aria-hidden="true">✓</span>Zekere Zelfzorg</NuxtLink>
      <template v-if="auth.isLoggedIn">
        <NuxtLink to="/dashboard" class="care-button care-secondary">Mijn zorg</NuxtLink>
        <button type="button" class="care-button care-secondary" @click="logout">Uitloggen</button>
      </template>
      <NuxtLink v-else to="/login" class="care-button care-secondary">Inloggen</NuxtLink>
    </nav>
  </header>
</template>
