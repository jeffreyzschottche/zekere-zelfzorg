import { test } from 'node:test'
import assert from 'node:assert/strict'
import { practiceQuestions, practiceScore } from '../data/carePractice.ts'

test('scores correct answers and ignores missing or unknown questions', () => {
  assert.equal(practiceScore({ steps: 'opened', account: 'shared', extra: 'waiting' }), 1)
  assert.equal(practiceScore({}), 0)
  const allCorrect = Object.fromEntries(practiceQuestions.map(({ id, correct }) => [id, correct]))
  assert.equal(practiceScore(allCorrect), 3)
  for (const question of practiceQuestions) assert.ok(question.correct in question.options)
})
