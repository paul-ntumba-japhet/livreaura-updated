export type StepStatus = 'upcoming' | 'current' | 'complete' | 'error' | 'blocked'

export type RegistrationStepId =
  | 'parcours'
  | 'informations'
  | 'securite'
  | 'verification'
  | 'activation'

const labels: Record<RegistrationStepId, string> = {
  parcours: 'Choix du parcours',
  informations: 'Informations personnelles',
  securite: 'Sécurité du compte',
  verification: 'Vérification e\u2011mail',
  activation: 'Activation',
}

const order: RegistrationStepId[] = [
  'parcours',
  'informations',
  'securite',
  'verification',
  'activation',
]

export function registrationSteps(current: RegistrationStepId): Array<{
  id: string
  label: string
  status: StepStatus
}> {
  const currentIndex = order.indexOf(current)

  return order.map((id, index) => {
    let status: StepStatus = 'upcoming'
    if (index < currentIndex) {
      status = 'complete'
    }
    else if (index === currentIndex) {
      status = 'current'
    }

    return {
      id,
      label: labels[id],
      status,
    }
  })
}
