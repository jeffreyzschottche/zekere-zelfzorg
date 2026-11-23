<script setup lang="ts">
import type { Today } from '~/types/Care'

const props = defineProps<{ profile: number; today: Today }>()
const emit = defineEmits<{ saved: [] }>()
const saving = defineModel<boolean>('saving', { required: true })
const api = useApi()
const assignees = computed(() => props.today.members.filter(member => member.role === 'patient' || member.role === 'caregiver'))
const assignment = reactive({ assignee_id: assignees.value[0]?.id ?? 0, starts_on: props.today.current_day, ends_on: '', time: '20:00', repeat: 'daily' })
const followup = reactive({ enabled: true, minutes: 15, question: 'observation', trigger: 'yes', responder: 'either' })
const error = ref('')
const saved = ref(false)

async function plan() {
  if (saving.value || !assignment.assignee_id) return
  saving.value = true
  error.value = ''
  saved.value = false
  try {
    await api.post(`/care/profiles/${props.profile}/assignments`, {
      ...assignment, ends_on: assignment.ends_on || null,
      followup_minutes: followup.enabled ? followup.minutes : null,
      followup_question: followup.question, followup_trigger: followup.trigger, followup_responder: followup.responder,
    })
    saved.value = true
    emit('saved')
  } catch {
    error.value = 'De planning kon niet worden opgeslagen. Controleer de velden en probeer opnieuw.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <form class="care-card care-stack" @submit.prevent="plan">
    <p v-if="saved" class="care-badge" role="status">Planning opgeslagen</p>
    <p v-if="error" class="care-alert" role="alert">{{ error }}</p>
    <h2 class="text-2xl font-bold">Handeling plannen</h2>
    <p>Nachtzak bij een katheterdagzak — demonstratie</p>
    <div class="care-grid">
      <label>Uitvoerder
        <select v-model.number="assignment.assignee_id" class="care-field" required>
          <option v-for="member in assignees" :key="member.id" :value="member.id">{{ member.name }}</option>
        </select>
      </label>
      <label>Herhaling
        <select v-model="assignment.repeat" class="care-field">
          <option value="once">Eenmalig</option><option value="daily">Dagelijks</option>
          <option value="alternate">Om de dag</option><option value="weekly">Wekelijks</option>
        </select>
      </label>
      <label>Startdatum <input v-model="assignment.starts_on" type="date" class="care-field" required /></label>
      <label>Einddatum (optioneel) <input v-model="assignment.ends_on" type="date" class="care-field" :min="assignment.starts_on" /></label>
      <label>Tijd <input v-model="assignment.time" type="time" class="care-field" required /></label>
    </div>
    <fieldset class="care-card care-stack">
      <legend class="font-semibold">Nazorg in deze demonstratie</legend>
      <label><input v-model="followup.enabled" type="checkbox" /> Nazorgcheck plannen</label>
      <div v-if="followup.enabled" class="care-grid">
        <label>Minuten na uitvoering <input v-model.number="followup.minutes" type="number" min="0" max="10080" class="care-field" required /></label>
        <label>Vraag
          <select v-model="followup.question" class="care-field">
            <option value="observation">Is er iets opgevallen?</option><option value="support">Is extra uitleg gewenst?</option>
          </select>
        </label>
        <label>Melding bij
          <select v-model="followup.trigger" class="care-field">
            <option value="yes">Ja</option><option value="no">Nee</option><option value="unknown">Weet ik niet</option>
          </select>
        </label>
        <label>Wie antwoordt?
          <select v-model="followup.responder" class="care-field">
            <option value="either">Patiënt of uitvoerder</option><option value="patient">Patiënt</option><option value="executor">Uitvoerder</option>
          </select>
        </label>
      </div>
    </fieldset>
    <div><button type="submit" class="care-button" :disabled="saving || !assignees.length">Planning opslaan</button></div>
  </form>
</template>
