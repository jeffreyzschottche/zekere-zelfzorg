<script setup lang="ts">
import { roleNames, statusNames, type Entry, type Followup, type Inbox, type Profile, type Skill, type Today } from '~/types/Care'

definePageMeta({ middleware: 'auth' })

const api = useApi()
const route = useRoute()
const entries = ref<Entry[]>([])
const checks = ref<Followup[]>([])
const skills = ref<Skill[]>([])
const inbox = ref<Inbox>()
const profiles = ref<Profile[]>([])
const profile = ref<Profile>()
const today = ref<Today>()
const error = ref('')
const busy = ref(true)
const dueCount = computed(() => checks.value.filter(check => check.can_answer).length)
let reminderTimer: ReturnType<typeof setInterval> | undefined
const saving = ref(false)
const assignment = reactive({ assignee_id: 0, starts_on: '', ends_on: '', time: '20:00', repeat: 'daily' })
const followup = reactive({ enabled: true, minutes: 15, question: 'observation', trigger: 'yes', responder: 'either' })
const base = computed(() => `/care/profiles/${profile.value?.id}`)
const dateLabel = computed(() => today.value && new Intl.DateTimeFormat('nl-NL', { dateStyle: 'full', timeZone: 'UTC' }).format(new Date(today.value.day)))
const memberName = (id: number) => today.value?.members.find(member => member.id === id)?.name

async function select(next: Profile) {
  busy.value = true
  error.value = ''
  profile.value = next
  today.value = undefined
  try {
    const [plan, log, followups, assessments, reviews] = await Promise.all([
      api.get<Today>(`${base.value}/today`), api.get<Entry[]>(`${base.value}/log`), api.get<Followup[]>(`${base.value}/followups`),
      api.get<Skill[]>(`${base.value}/skills`),
      next.role === 'professional' ? api.get<Inbox>(`${base.value}/reviews`) : undefined,
    ])
    today.value = plan
    entries.value = log
    checks.value = followups
    skills.value = assessments
    inbox.value = reviews
    assignment.starts_on = today.value.day
    assignment.assignee_id = today.value.members.find(member => member.role !== 'professional')?.id ?? 0
  } catch {
    error.value = 'De zorgplanning kon niet worden geladen.'
  } finally {
    busy.value = false
  }
}

onMounted(async () => {
  reminderTimer = setInterval(() => {
    if (profile.value && !busy.value && document.visibilityState === 'visible') void refreshChecks(true)
  }, 30_000)
  try {
    profiles.value = await api.get<Profile[]>('/care/profiles')
    const first = profiles.value[0]
    if (first) await select(first)
  } catch {
    error.value = 'De zorgprofielen konden niet worden geladen. Log opnieuw in en probeer het nog eens.'
  } finally {
    busy.value = false
  }
})

onBeforeUnmount(() => clearInterval(reminderTimer))

async function viewDay(day: string) {
  busy.value = true
  error.value = ''
  try {
    today.value = await api.get<Today>(`${base.value}/today?day=${day}`)
  } catch {
    error.value = 'De planning voor deze dag kon niet worden geladen.'
  } finally {
    busy.value = false
  }
}

async function refreshChecks(quiet = false) {
  const selected = profile.value?.id
  try {
    const fresh = await api.get<Followup[]>(`${base.value}/followups`)
    if (profile.value?.id === selected) checks.value = fresh
  } catch {
    if (!quiet && profile.value?.id === selected) error.value = 'De nazorgchecks konden niet worden vernieuwd.'
  }
}

async function refreshReviews() {
  const selected = profile.value?.id
  try {
    const fresh = await api.get<Inbox>(`${base.value}/reviews`)
    if (profile.value?.id === selected) inbox.value = fresh
  } catch {
    if (profile.value?.id === selected) error.value = 'De beoordelingen konden niet worden vernieuwd.'
  }
}

async function refreshSkills() {
  const selected = profile.value?.id
  try {
    const fresh = await api.get<Skill[]>(`${base.value}/skills`)
    if (profile.value?.id === selected) skills.value = fresh
  } catch {
    if (profile.value?.id === selected) error.value = 'De vaardigheidsstatus kon niet worden vernieuwd.'
  }
}

async function plan() {
  saving.value = true
  error.value = ''
  try {
    await api.post(`${base.value}/assignments`, {
      ...assignment, ends_on: assignment.ends_on || null,
      followup_minutes: followup.enabled ? followup.minutes : null,
      followup_question: followup.question, followup_trigger: followup.trigger, followup_responder: followup.responder,
    })
    today.value = await api.get<Today>(`${base.value}/today`)
  } catch {
    error.value = 'De planning kon niet worden opgeslagen. Controleer de velden en probeer opnieuw.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="care-wrap care-page care-stack">
    <header class="care-stack">
      <span class="care-badge">Fictieve zorgomgeving</span>
      <h1 class="care-title">{{ today && today.day !== today.current_day ? 'Gisteren' : 'Vandaag' }}</h1>
      <p v-if="today" class="text-lg capitalize">{{ dateLabel }}</p>
    </header>
    <div v-if="profiles.length > 1" class="care-row" aria-label="Zorgprofielen">
      <button v-for="item in profiles" :key="item.id" type="button" class="care-button care-secondary"
        :aria-pressed="profile?.id === item.id" :disabled="busy || saving" @click="select(item)">
        {{ item.name }}
      </button>
    </div>
    <p v-if="route.query.saved === '1'" class="care-badge" role="status">Registratie opgeslagen</p>
    <p v-if="busy" role="status">Zorgplanning laden…</p>
    <p v-if="error" class="care-alert" role="alert">{{ error }}</p>
    <p v-if="!busy && !error && !profile" class="care-card">Er is nog geen zorgprofiel aan dit account gekoppeld.</p>
    <template v-if="today && profile">
      <nav class="care-row" aria-label="Op deze pagina">
        <a class="care-button care-secondary" href="#planning">Zorgplanning</a>
        <a class="care-button care-secondary" href="#followup-title">Nazorgchecks</a>
        <a class="care-button care-secondary" href="#log-title">Logboek</a>
      </nav>
      <div role="status">
        <a v-if="dueCount" href="#followup-title" class="care-badge">{{ dueCount === 1 ? 'Er staat een nazorgcheck klaar' : dueCount + ' nazorgchecks staan klaar' }}</a>
      </div>
      <div class="care-grid">
        <section class="care-card care-stack">
          <h2 id="planning" class="text-2xl font-bold">Zorgplanning</h2>
          <div class="care-row">
            <button type="button" class="care-button care-secondary" :disabled="busy" :aria-pressed="today.day === today.current_day" @click="viewDay(today.current_day)">Vandaag</button>
            <button type="button" class="care-button care-secondary" :disabled="busy" :aria-pressed="today.day === today.previous_day" @click="viewDay(today.previous_day)">Gisteren</button>
          </div>
          <p v-if="!today.tasks.length">Er staan op deze dag geen handelingen gepland.</p>
          <article v-for="task in today.tasks" :key="task.id" class="care-card care-stack">
            <div class="care-row"><time>{{ task.time }}</time><span class="care-badge">{{ task.status ? statusNames[task.status] : 'Nog niet bevestigd' }}</span></div>
            <h3 class="text-xl font-bold">{{ task.title }}</h3>
            <p>Uitvoerder op de planning: {{ memberName(task.assignee_id) }}</p>
            <div v-if="task.can_execute"><NuxtLink class="care-button" :to="{ path: '/handeling', query: { profile: profile.id, task: task.id, day: task.day, past: today.day === today.current_day ? '0' : '1' } }">{{ today.day === today.current_day ? 'Start handeling' : 'Registreren' }}</NuxtLink></div>
          </article>
        </section>
        <section class="care-card care-stack">
          <h2 class="text-2xl font-bold">Zorgteam</h2>
          <p>{{ profile.name }} · {{ roleNames[profile.role] }}</p>
          <ul>
            <li v-for="member in today.members" :key="member.id">{{ member.name }} · {{ roleNames[member.role] }}</li>
          </ul>
        </section>
      </div>
      <CareReview v-if="inbox" :key="'review-' + profile.id" :profile="profile.id" :entries="entries" :inbox="inbox" @refresh="refreshReviews" />
      <CareSkills v-if="skills.length" :key="'skills-' + profile.id" :profile="profile.id" :skills="skills" :professional="profile.role === 'professional'" @refresh="refreshSkills" />
      <CareFollowups :key="'followups-' + profile.id" :profile="profile.id" :checks="checks" @refresh="refreshChecks()" />
      <CareLog :entries="entries" :checks="checks" />
      <form v-if="profile.role === 'professional'" class="care-card care-stack" @submit.prevent="plan">
        <h2 class="text-2xl font-bold">Handeling plannen</h2>
        <p>Nachtzak bij een katheterdagzak — demonstratie</p>
        <div class="care-grid">
          <label>Uitvoerder
            <select v-model.number="assignment.assignee_id" class="care-field" required>
              <option v-for="member in today.members.filter(member => member.role !== 'professional')" :key="member.id" :value="member.id">{{ member.name }}</option>
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
        <div><button type="submit" class="care-button" :disabled="saving">Planning opslaan</button></div>
      </form>
    </template>
  </div>
</template>
