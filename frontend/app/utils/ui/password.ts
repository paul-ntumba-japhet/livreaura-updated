export type PasswordRuleId = 'length' | 'uppercase' | 'lowercase' | 'digit' | 'special'

export type PasswordStrengthLevel = 'empty' | 'weak' | 'medium' | 'strong' | 'excellent'

export type PasswordRuleState = {
  id: PasswordRuleId
  label: string
  met: boolean
}

export type PasswordEvaluation = {
  rules: PasswordRuleState[]
  score: number
  complete: boolean
  level: PasswordStrengthLevel
  levelLabel: string
}

const passwordRuleDefinitions: Array<{
  id: PasswordRuleId
  label: string
  test: (password: string) => boolean
}> = [
  {
    id: 'length',
    label: 'Au moins 8 caractères',
    test: password => password.length >= 8,
  },
  {
    id: 'uppercase',
    label: 'Une lettre majuscule',
    test: password => /\p{Lu}/u.test(password),
  },
  {
    id: 'lowercase',
    label: 'Une lettre minuscule',
    test: password => /\p{Ll}/u.test(password),
  },
  {
    id: 'digit',
    label: 'Un chiffre',
    test: password => /\p{Nd}/u.test(password),
  },
  {
    id: 'special',
    label: 'Un caractère spécial',
    test: password => /[^\p{L}\p{Nd}]/u.test(password),
  },
]

const strengthLabels: Record<PasswordStrengthLevel, string> = {
  empty: '',
  weak: 'Faible',
  medium: 'Moyen',
  strong: 'Fort',
  excellent: 'Excellent',
}

export function evaluatePassword(password: string): PasswordEvaluation {
  const rules = passwordRuleDefinitions.map(rule => ({
    id: rule.id,
    label: rule.label,
    met: rule.test(password),
  }))
  const score = rules.filter(rule => rule.met).length
  const complete = score === rules.length

  let level: PasswordStrengthLevel = 'empty'
  if (password.length > 0) {
    if (score <= 2) {
      level = 'weak'
    }
    else if (score === 3) {
      level = 'medium'
    }
    else if (score === 4) {
      level = 'strong'
    }
    else {
      level = 'excellent'
    }
  }

  return {
    rules,
    score,
    complete,
    level,
    levelLabel: strengthLabels[level],
  }
}

export function passwordMeetsRequirements(password: string): boolean {
  return evaluatePassword(password).complete
}

export const passwordRequirementsMessage = 'Le mot de passe doit contenir une majuscule, une minuscule, un chiffre, un caractère spécial et au moins 8 caractères.'

function joinFrenchList(items: string[]): string {
  if (items.length === 0) {
    return ''
  }
  if (items.length === 1) {
    return items[0]
  }
  if (items.length === 2) {
    return `${items[0]} et ${items[1]}`
  }
  return `${items.slice(0, -1).join(', ')} et ${items[items.length - 1]}`
}

export function passwordGuidance(evaluation: PasswordEvaluation): string {
  if (evaluation.level === 'empty') {
    return ''
  }
  if (evaluation.complete) {
    return ''
  }
  const missing = evaluation.rules
    .filter(rule => !rule.met)
    .map(rule => rule.label.toLocaleLowerCase('fr'))
  return `Ajoutez ${joinFrenchList(missing)}.`
}
