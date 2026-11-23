<script setup lang="ts">
import { careDate, type Skill } from '~/types/Care'

const props = defineProps<{ profile: number; skills: Skill[]; professional: boolean }>()
const emit = defineEmits<{ refresh: [] }>()
const api = useApi()
const form = reactive({ user_id: 0, status: 'demonstrated', method: 'demo_observation', note: '' })
const pending = ref(false)
const error = ref('')
const saved = ref(false)
const labels = { demonstrated: 'Gedemonstreerd in oefensituatie', practice: 'Meer oefenen', reassess: 'Herbeoordeling nodig' }

async function save() {
  pending.value = true
  saved.value = false
  error.value = ''
  try {
    await api.post(`/care/profiles/${props.profile}/skills`, form)
    form.note = ''
    saved.value = true
    emit('refresh')
  } catch {
    error.value = 'De vaardigheidsbeoordeling kon niet worden opgeslagen.'
  } finally {
    pending.value = false
  }
}
</script>

<template>
  <section class="care-card care-stack" aria-labelledby="skills-title">
    <h2 id="skills-title" class="text-2xl font-bold">Vaardigheden in de demo</h2>
    <p>Nachtzak bij een katheterdagzak</p>
    <article v-for="skill in skills" :key="skill.id" class="care-card care-stack">
      <h3 class="text-xl font-bold">{{ skill.name }}</h3>
      <span class="care-badge">{{ skill.status ? labels[skill.status] : 'Nog niet beoordeeld' }}</span>
      <p v-if="skill.reviewed_at">{{ skill.reviewer }} · {{ careDate(skill.reviewed_at) }} · {{ skill.version }}</p>
      <p v-if="skill.note">{{ skill.note }}</p>
    </article>
    <form v-if="professional && skills.length" class="care-stack" @submit.prevent="save">
      <h3 class="text-xl font-bold">Demobeoordeling vastleggen</h3>
      <div class="care-grid">
        <label>Mantelzorger
          <select v-model.number="form.user_id" class="care-field" required>
            <option :value="0" disabled>Kies een mantelzorger</option><option v-for="skill in skills" :key="skill.id" :value="skill.id">{{ skill.name }}</option>
          </select>
        </label>
        <label>Uitkomst
          <select v-model="form.status" class="care-field"><option v-for="(label, value) in labels" :key="value" :value="value">{{ label }}</option></select>
        </label>
        <label>Manier van beoordelen
          <select v-model="form.method" class="care-field"><option value="demo_observation">Simulatie van directe observatie</option><option value="demo_video">Simulatie van een oefenvideo</option></select>
        </label>
      </div>
      <label>Feedback <textarea v-model="form.note" class="care-field" rows="3" maxlength="1000" required /></label>
      <div><button type="submit" class="care-button" :disabled="pending || !form.user_id">Vaardigheid opslaan</button></div>
    </form>
    <p v-if="saved" class="care-badge" role="status">Vaardigheidsbeoordeling opgeslagen</p>
    <p v-if="error" class="care-alert" role="alert">{{ error }}</p>
  </section>
</template>
