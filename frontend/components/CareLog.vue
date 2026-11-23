<script setup lang="ts">
import { careDate, statusNames, type Entry } from '~/types/Care'
defineProps<{ entries: Entry[] }>()
</script>

<template>
  <section class="care-card care-stack" aria-labelledby="log-title">
    <h2 id="log-title" class="text-2xl font-bold">Logboek</h2>
    <p v-if="!entries.length">Er staan nog geen uitvoeringen in het logboek.</p>
    <details v-for="entry in entries" :key="entry.id" class="care-details care-card">
      <summary>{{ entry.day }} · {{ entry.title }} · {{ statusNames[entry.status] }}</summary>
      <p>{{ entry.recorder }} · geregistreerd op {{ careDate(entry.recorded_at) }}</p>
      <p v-if="entry.retrospective">Achteraf geregistreerd</p>
      <p v-if="entry.performed_at">Uitvoertijd {{ careDate(entry.performed_at) }}</p>
      <p>Instructieversie {{ entry.version }}</p>
      <p v-if="entry.note">{{ entry.note }}</p>
    </details>
  </section>
</template>
