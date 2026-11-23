<script setup lang="ts">
import type { ExecutionStatus, Instruction } from '~/types/Care'

definePageMeta({ middleware: 'auth' })
const route = useRoute()
const api = useApi()
const auth = useAuthStore()
const detail = ref<Instruction>()
const stage = ref(-1)
const heading = ref<HTMLElement>()
const status = ref<ExecutionStatus>('performed')
const answers = reactive<Record<string, string>>({})
const opened = new Set<string>()
const note = ref('')
const actualTime = ref('')
const error = ref('')
const saving = ref(false)
const isPast = route.query.past === '1'
const endpoint = `/care/profiles/${encodeURIComponent(String(route.query.profile))}/assignments/${encodeURIComponent(String(route.query.task))}/${encodeURIComponent(String(route.query.day))}`
const step = computed(() => detail.value?.steps[stage.value])
const complete = computed(() => detail.value && stage.value === detail.value.steps.length)

onMounted(async () => {
  try {
    detail.value = await api.get<Instruction>(endpoint)
  } catch {
    error.value = 'Deze handeling kan niet worden geopend met jouw account.'
  }
})

async function go(next: number) {
  stage.value = next
  if (step.value) opened.add(step.value.id)
  await nextTick()
  heading.value?.focus()
}

function reportOnly() {
  status.value = 'not_performed'
  void go(detail.value!.steps.length)
}

async function save() {
  saving.value = true
  error.value = ''
  try {
    // Use the care profile's time zone even when the device uses another zone.
    const performed_at = actualTime.value && status.value === 'performed'
      ? amsterdamInstant(String(route.query.day), actualTime.value)
      : null
    await api.post(endpoint, { status: status.value, version: detail.value?.version,
      answers: status.value === 'performed' ? answers : undefined,
      opened_steps: [...opened], note: note.value, performed_at })
    await navigateTo({ path: '/dashboard', query: { saved: '1' } })
  } catch {
    error.value = 'De registratie kon niet worden opgeslagen. Controleer de uitvoertijd of kijk of er al een registratie staat.'
  } finally {
    saving.value = false
  }
}

function amsterdamInstant(day: string, time: string) {
  const wall = `${day}T${time}`
  const localFormat = new Intl.DateTimeFormat('sv-SE', {
    timeZone: 'Europe/Amsterdam', dateStyle: 'short', timeStyle: 'short',
  })
  for (const hours of [2, 1]) {
    const candidate = new Date(Date.parse(wall + ':00Z') - hours * 3600000)
    if (localFormat.format(candidate).replace(' ', 'T') === wall) return candidate.toISOString()
  }
  throw new Error('Ongeldige lokale uitvoertijd')
}
</script>

<template>
  <div class="care-wrap care-page care-stack max-w-3xl">
    <div><NuxtLink to="/dashboard" class="care-button care-secondary">Terug naar Mijn zorg</NuxtLink></div>
    <p v-if="error" class="care-alert" role="alert">{{ error }}</p>
    <p v-if="!detail && !error" role="status">Handeling laden…</p>
    <template v-if="detail">
      <header class="care-stack">
        <span class="care-badge">Demonstratie · {{ detail.version }}</span>
        <h1 class="care-title">{{ detail.title }}</h1>
      </header>
      <p class="care-alert">Deze voorbeeldstappen zijn bedoeld om de app te oefenen.</p>
      <section v-if="stage === -1" class="care-card care-stack">
        <h2 class="text-2xl font-bold">Voorbereiding</h2>
        <p>Je registreert met je eigen account als {{ auth.user?.name }}.</p>
        <details class="care-details"><summary>Heb je nog vragen?</summary><p>{{ detail.faq }}</p></details>
        <div class="care-row">
          <button type="button" class="care-button" @click="go(isPast ? detail.steps.length : 0)">{{ isPast ? 'Achteraf registreren' : 'Begin met de demonstratie' }}</button>
          <button v-if="!isPast" type="button" class="care-button care-secondary" @click="reportOnly">Niet uitgevoerd melden</button>
        </div>
      </section>
      <section v-if="step" :key="step.id" class="care-card care-stack">
        <p>Stap {{ stage + 1 }} van {{ detail.steps.length }}</p>
        <h2 ref="heading" tabindex="-1" class="text-2xl font-bold">{{ step.title }}</h2>
        <p class="text-lg">{{ step.body }}</p>
        <details class="care-details"><summary>Vragen?</summary><p>{{ step.help }}</p></details>
        <div class="care-row">
          <button type="button" class="care-button care-secondary" @click="go(stage - 1)">Vorige</button>
          <button type="button" class="care-button" @click="go(stage + 1)">{{ stage + 1 === detail.steps.length ? 'Afronden' : 'Volgende' }}</button>
        </div>
      </section>
      <form v-if="complete" class="care-card care-stack" @submit.prevent="save">
        <h2 ref="heading" tabindex="-1" class="text-2xl font-bold">Eindcheck en registratie</h2>
        <p>Je legt je eigen terugmelding vast.</p>
        <label>Wat is er gebeurd?
          <select v-model="status" class="care-field">
            <option value="performed">Uitgevoerd</option><option value="not_performed">Niet uitgevoerd</option><option value="unknown">Ik weet het niet</option>
          </select>
        </label>
        <template v-if="status === 'performed'">
          <CareQuestion v-for="question in detail.questions" :key="question.id" v-model="answers[question.id]" :name="question.id" :question="question.label" :options="question.options" />
          <label v-if="isPast">Werkelijke uitvoertijd (optioneel, Nederlandse tijd)
            <input v-model="actualTime" type="time" class="care-field" />
          </label>
        </template>
        <label>Notitie (optioneel) <textarea v-model="note" class="care-field" maxlength="1000" rows="3" /></label>
        <div class="care-row">
          <button type="button" class="care-button care-secondary" @click="go(isPast || !opened.size ? -1 : stage - 1)">Vorige</button>
          <button type="submit" class="care-button" :disabled="saving">Registratie opslaan</button>
        </div>
      </form>
    </template>
  </div>
</template>
