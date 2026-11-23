export const practiceQuestions = [
  {
    id: 'steps', label: 'Wat legt de app vast als je door de voorbeeldstappen klikt?',
    options: { opened: 'Welke stappen je hebt geopend', completed: 'Dat de handeling goed is uitgevoerd' },
    correct: 'opened',
    feedback: 'Het stappenoverzicht legt vast welke schermen je hebt geopend. Je uitvoering registreer je zelf bij de eindcheck.',
  },
  {
    id: 'account', label: 'Een andere mantelzorger voert de handeling uit. Wie bevestigt de uitvoering?',
    options: { shared: 'De mantelzorger die als eerste kan inloggen', personal: 'De uitvoerder via het eigen account' },
    correct: 'personal',
    feedback: 'De uitvoerder bevestigt de eigen uitvoering. Zo blijft zichtbaar wie de registratie heeft gedaan.',
  },
  {
    id: 'followup', label: 'Wat staat er bij een nazorgcheck die nog geen antwoord heeft?',
    options: { waiting: 'Nog niet beantwoord', clear: 'Geen afwijking gevonden' },
    correct: 'waiting',
    feedback: 'Zolang er geen antwoord is, blijft de uitkomst onbekend.',
  },
] as const

export function practiceScore(answers: Record<string, string>) {
  return practiceQuestions.filter(question => answers[question.id] === question.correct).length
}
