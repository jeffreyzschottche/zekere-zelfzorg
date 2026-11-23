export type Role = 'patient' | 'caregiver' | 'professional'
export type Profile = { id: number; name: string; role: Role }
export type Member = Profile
export type Task = { id: number; title: string; assignee_id: number; day: string; time: string; can_execute: boolean }
export type Today = { day: string; tasks: Task[]; members: Member[] }

export const roleNames: Record<Role, string> = {
  patient: 'Patiënt',
  caregiver: 'Mantelzorger',
  professional: 'Zorgprofessional',
}
