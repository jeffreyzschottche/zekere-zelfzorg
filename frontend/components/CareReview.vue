<script setup lang="ts">
import { careDate, statusNames, type Entry, type Inbox } from '~/types/Care'

const props = defineProps<{ profile: number; entries: Entry[]; inbox: Inbox }>()
const emit = defineEmits<{ refresh: [] }>()
const api = useApi()
const selected = ref<number>()
const status = ref('seen')
const note = ref('')
const error = ref('')
const saved = ref(false)
const busy = ref(false)
const entry = computed(() => props.entries.find(entry => entry.id === selected.value))
const review = computed(() => props.inbox.reviews.find(review => review.execution_id === selected.value))

function open(id: number) {
  selected.value = id
  status.value = 'seen'
  note.value = ''
  error.value = ''
  saved.value = false
}

async function save() {
  busy.value = true
  error.value = ''
  try {
    await api.post(`/care/profiles/${props.profile}/reviews/${selected.value}`, { outcome: status.value, note: note.value })
    saved.value = true
    emit('refresh')
  } catch {
    error.value = 'De beoordeling kon niet worden opgeslagen.'
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <section class="care-card care-stack" aria-labelledby="reviews-title">
    <h2 id="reviews-title" class="text-2xl font-bold">Meldingen en beoordelingen</h2>
    <p>De meldingen in dit overzicht zijn gesimuleerd.</p>
    <p v-if="!inbox.alerts.length">Er zijn geen demomeldingen.</p>
    <ul v-else class="care-stack">
      <li v-for="alert in inbox.alerts" :key="alert.id" class="care-row">
        <span class="care-badge">{{ alert.outcome ? 'Bekeken' : 'Open' }}</span>
        <span>{{ alert.source === 'followup' ? 'Afwijking bij nazorgcheck' : 'Twijfel bij eindcheck' }}</span>
        <button v-if="entries.some(item => item.id === alert.execution_id)" class="care-button care-secondary" type="button" :disabled="busy" @click="open(alert.execution_id)">Open registratie</button>
      </li>
    </ul>
    <label v-if="entries.length">Registratie bekijken
      <select class="care-field" :value="selected ?? ''" :disabled="busy" @change="open(Number(($event.target as HTMLSelectElement).value))">
        <option value="" disabled>Kies een registratie</option>
        <option v-for="item in entries" :key="item.id" :value="item.id">{{ item.day }} · {{ item.recorder }} · {{ item.title }}</option>
      </select>
    </label>
    <article v-if="entry" class="care-card care-stack">
      <h3 class="text-xl font-bold">{{ entry.title }}</h3>
      <p>{{ entry.recorder }} · {{ statusNames[entry.status] }} · {{ entry.day }}</p>
      <p v-if="entry.answers.doubt">Twijfel bij eindcheck · {{ { yes: 'Ja', no: 'Nee', unknown: 'Weet ik niet' }[entry.answers.doubt] }}</p>
      <p v-if="entry.note">{{ entry.note }}</p>
      <div v-if="review">
        <strong>{{ review.outcome === 'followup' ? 'Vervolg gewenst' : 'Bekeken' }}</strong>
        <p>{{ review.reviewer }} · {{ careDate(review.reviewed_at) }}</p><p>{{ review.note }}</p>
      </div>
      <form v-else-if="!saved" class="care-stack" @submit.prevent="save">
        <label>Uitkomst
          <select v-model="status" class="care-field"><option value="seen">Bekeken</option><option value="followup">Vervolg gewenst</option></select>
        </label>
        <label>Toelichting <textarea v-model="note" class="care-field" rows="3" maxlength="1000" required /></label>
        <div><button type="submit" class="care-button" :disabled="busy">Beoordeling opslaan</button></div>
      </form>
      <p v-if="saved" class="care-badge" role="status">Beoordeling opgeslagen</p>
      <p v-if="error" class="care-alert" role="alert">{{ error }}</p>
    </article>
  </section>
</template>
