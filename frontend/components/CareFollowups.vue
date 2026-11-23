<script setup lang="ts">
import { careDate, type Followup } from '~/types/Care'

const props = defineProps<{ profile: number; checks: Followup[] }>()
const emit = defineEmits<{ refresh: [] }>()
const api = useApi()
const answers = reactive<Record<number, string>>({})
const pending = ref<number>()
const message = ref('')
const error = ref('')

async function save(id: number) {
  pending.value = id
  message.value = ''
  error.value = ''
  try {
    const result = await api.post<{ deviation: boolean }>(`/care/profiles/${props.profile}/followups/${id}`, { answer: answers[id] })
    message.value = result.deviation
      ? 'Antwoord opgeslagen. Er staat een gesimuleerde melding in het zorgdashboard.'
      : 'Antwoord opgeslagen.'
  } catch {
    error.value = 'Het antwoord kon niet worden opgeslagen. Vernieuw de controles en probeer opnieuw.'
  } finally {
    pending.value = undefined
    emit('refresh')
  }
}
</script>

<template>
  <section class="care-card care-stack" aria-labelledby="followup-title">
    <div class="care-row justify-between">
      <h2 id="followup-title" class="text-2xl font-bold">Nazorgchecks</h2>
      <button type="button" class="care-button care-secondary" @click="emit('refresh')">Vernieuwen</button>
    </div>
    <p v-if="message" class="care-badge" role="status">{{ message }}</p>
    <p v-if="error" class="care-alert" role="alert">{{ error }}</p>
    <p v-if="!checks.length">Er staan geen nazorgchecks in dit zorgprofiel.</p>
    <article v-for="check in checks" :key="check.id" class="care-card care-stack">
      <div class="care-row">
        <span class="care-badge">{{ check.answer ? 'Beantwoord' : check.is_due ? 'Nog niet beantwoord' : 'Gepland' }}</span>
        <time :datetime="check.due_at">{{ careDate(check.due_at) }}</time>
      </div>
      <h3 class="text-xl font-bold">{{ check.title }}</h3>
      <p v-if="check.answer">{{ check.observer }} · {{ check.options[check.answer] }}</p>
      <p v-if="check.deviation" class="care-alert">Afwijking in de demo. De melding is gesimuleerd.</p>
      <form v-if="check.can_answer" @submit.prevent="save(check.id)">
        <CareQuestion v-model="answers[check.id]" :name="'nazorg-' + check.id" :question="check.question" :options="check.options" />
        <button type="submit" class="care-button" :disabled="pending !== undefined">Antwoord opslaan</button>
      </form>
    </article>
  </section>
</template>
