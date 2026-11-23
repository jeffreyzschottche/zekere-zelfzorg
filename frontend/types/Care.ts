export type Role = 'patient' | 'caregiver' | 'professional'
export type Profile = { id: number; name: string; role: Role }
export type Member = Profile
export type Task = { id: number; title: string; assignee_id: number; day: string; time: string; can_execute: boolean; status: ExecutionStatus | null }
export type Today = { day: string; previous_day: string; current_day: string; tasks: Task[]; members: Member[] }

export const roleNames: Record<Role, string> = {
  patient: 'Patiënt',
  caregiver: 'Mantelzorger',
  professional: 'Zorgprofessional',
}

export type ExecutionStatus = 'performed' | 'not_performed' | 'unknown'
export type Question = { id: string; label: string; options: Record<string, string> }
export type Instruction = {
  title: string; version: string; faq: string
  steps: { id: string; title: string; body: string; help: string }[]
  questions: Question[]
}
export type Entry = {
  id: number; title: string; recorder: string; day: string; version: string; template: string
  status: ExecutionStatus; note: string | null; retrospective: boolean
  performed_at: string | null; recorded_at: string; answers: Record<string, string>
}
export const statusNames: Record<ExecutionStatus, string> = {
  performed: 'Zelf bevestigd',
  not_performed: 'Niet uitgevoerd',
  unknown: 'Onbekend',
}
export function careDate(value: string) {
  return new Intl.DateTimeFormat('nl-NL', {
    dateStyle: 'medium', timeStyle: 'short', timeZone: 'Europe/Amsterdam',
  }).format(new Date(value))
}
