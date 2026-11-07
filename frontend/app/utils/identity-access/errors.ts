export type IdentityErrorKind =
  | 'quota'
  | 'unavailable'
  | 'forbidden'
  | 'not_found'
  | 'network'
  | 'conflict'
  | 'validation'
  | 'unknown'

export type IdentityRequestError = {
  kind: IdentityErrorKind
  status: number | null
  code?: string
  fields?: Record<string, string[]>
}

type ErrorBody = {
  error?: {
    code?: string
    message?: string
    champs?: Record<string, string[] | string>
  }
}

function asFields(champs: Record<string, string[] | string> | undefined): Record<string, string[]> | undefined {
  if (!champs) {
    return undefined
  }

  return Object.fromEntries(
    Object.entries(champs).map(([key, value]) => [key, Array.isArray(value) ? value : [value]]),
  )
}

export function statusFromUnknown(error: unknown): number | null {
  const candidate = error as {
    status?: number
    statusCode?: number
    response?: { status?: number }
  }
  const status = candidate.statusCode ?? candidate.status ?? candidate.response?.status
  return typeof status === 'number' && status > 0 ? status : null
}

export function normalizeIdentityFetchError(error: unknown): IdentityRequestError {
  const candidate = error as {
    data?: ErrorBody
  }
  const status = statusFromUnknown(error)
  const code = candidate.data?.error?.code
  const fields = asFields(candidate.data?.error?.champs)

  if (status === 401) {
    return { kind: 'unknown', status, code: code ?? 'authentification_refusee' }
  }
  if (status === 419) {
    return { kind: 'conflict', status, code: 'csrf' }
  }
  if (status === 429) {
    return { kind: 'quota', status, code }
  }
  if (status === 503) {
    return { kind: 'unavailable', status, code }
  }
  if (status === 403) {
    return { kind: 'forbidden', status, code }
  }
  if (status === 404) {
    return { kind: 'not_found', status, code }
  }
  if (status === 409) {
    return { kind: 'conflict', status, code, fields }
  }
  if (status === 422) {
    return { kind: 'validation', status, code, fields }
  }
  if (status === null || status === 0) {
    return { kind: 'network', status: null }
  }

  return { kind: 'unknown', status, code, fields }
}

export function messageForIdentityError(error: IdentityRequestError): string {
  switch (error.kind) {
    case 'quota':
      return 'Trop de tentatives. Réessayez dans un moment.'
    case 'unavailable':
      return 'Le service est temporairement indisponible. Réessayez plus tard.'
    case 'forbidden':
      return 'Vous n’avez pas accès à cette ressource.'
    case 'not_found':
      return 'Aucun parcours n’est visible pour le moment.'
    case 'network':
      return 'La liste n’a pas pu être obtenue. Vérifiez votre connexion, puis réessayez.'
    case 'conflict':
      if (error.code === 'compte_existant') {
        return 'Un compte existe déjà pour cet e-mail. Connectez-vous ou récupérez l’accès.'
      }
      if (error.code === 'intention_conflictuelle' || error.code === 'inscription_incoherente') {
        return 'Cette demande entre en conflit avec une inscription déjà enregistrée. Relisez les informations, puis réessayez.'
      }
      if (error.code === 'csrf') {
        return 'La session de sécurité a expiré. Réessayez.'
      }
      return 'La demande n’a pas pu aboutir. Relisez les informations, puis réessayez.'
    case 'validation':
      if (error.code === 'textes_requis') {
        return 'Acceptez les textes requis pour créer le compte.'
      }
      return 'Certains champs sont invalides. Corrigez-les, puis soumettez à nouveau.'
    default:
      return 'Impossible de charger les parcours.'
  }
}

export function messageForRegistrationError(error: IdentityRequestError): string {
  if (error.code === 'csrf') {
    return 'La session de sécurité n’a pas pu être établie. Réessayez.'
  }
  if (error.kind === 'network') {
    return 'Le résultat de la création n’est pas confirmé. Vérifiez votre connexion, puis renvoyez la même demande sans changer l’adresse e-mail.'
  }
  if (error.kind === 'unknown') {
    return 'La création du compte n’est pas confirmée. Renvoyez la même demande sans changer l’adresse e-mail.'
  }
  if (error.kind === 'not_found') {
    return 'Cette inscription n’est pas disponible pour le moment.'
  }
  return messageForIdentityError(error)
}

export function messageForActivationError(error: IdentityRequestError): string {
  if (error.code === 'csrf') {
    return 'La session de sécurité n’a pas pu être établie. Réessayez.'
  }
  if (error.kind === 'network') {
    return 'Le résultat de l’activation n’est pas confirmé. Vérifiez votre connexion, puis renvoyez la même demande.'
  }
  if (error.kind === 'unknown') {
    return 'L’activation n’a pas pu être confirmée. Réessayez.'
  }
  if (error.code === 'defi_invalide') {
    return 'Ce lien n’est plus valable. Il peut être expiré, déjà utilisé ou incorrect.'
  }
  if (error.code === 'compte_ineligible') {
    return 'Ce compte ne peut pas être activé. Connectez-vous si l’accès est déjà ouvert, ou demandez de l’assistance.'
  }
  return messageForIdentityError(error)
}

export function messageForLoginError(error: IdentityRequestError): string {
  if (error.code === 'csrf') {
    return 'La session de sécurité n’a pas pu être établie. Réessayez.'
  }
  if (error.status === 401 || error.code === 'authentification_refusee') {
    return 'E-mail ou mot de passe incorrect.'
  }
  if (error.kind === 'network') {
    return 'Le résultat de la connexion n’est pas confirmé. Vérifiez votre connexion, puis réessayez.'
  }
  if (error.kind === 'quota') {
    return 'Trop de tentatives. Réessayez dans un moment.'
  }
  if (error.kind === 'validation') {
    return 'Indiquez une adresse e-mail et un mot de passe valides.'
  }
  if (error.kind === 'unavailable') {
    return 'Le service n’est pas joignable. Vérifiez que l’API est démarrée, puis réessayez.'
  }
  if (error.kind === 'unknown') {
    return 'La connexion n’a pas pu être confirmée. Réessayez.'
  }
  return messageForIdentityError(error)
}

export function messageForAccountError(error: IdentityRequestError): string {
  if (error.status === 401 || error.code === 'authentification_refusee') {
    return 'La session n’est plus valable. Connectez-vous pour continuer.'
  }
  if (error.code === 'portee_insuffisante') {
    return 'Cette session n’autorise pas cette opération. Activez le compte, ou utilisez le parcours de recours prévu.'
  }
  if (error.kind === 'forbidden') {
    return 'Vous n’avez pas accès à ce compte.'
  }
  if (error.kind === 'network') {
    return 'Le compte n’a pas pu être chargé. Vérifiez votre connexion, puis réessayez.'
  }
  if (error.kind === 'unavailable') {
    return 'Le service est temporairement indisponible. Réessayez plus tard.'
  }
  if (error.kind === 'unknown') {
    return 'Le compte n’a pas pu être chargé. Réessayez.'
  }
  return messageForIdentityError(error)
}

export function messageForChangePasswordError(error: IdentityRequestError): string {
  if (error.code === 'csrf') {
    return 'La session de sécurité n’a pas pu être établie. Réessayez.'
  }
  if (error.status === 401 || error.code === 'authentification_refusee') {
    return 'Le mot de passe actuel est incorrect.'
  }
  if (error.code === 'portee_insuffisante') {
    return 'Le mot de passe ne peut pas être changé avec cette session. Activez le compte, puis reconnectez-vous.'
  }
  if (error.kind === 'network') {
    return 'Le résultat du changement n’est pas confirmé. Essayez de vous connecter avec le nouveau mot de passe. S’il est refusé, réessayez depuis le compte.'
  }
  if (error.kind === 'validation') {
    return 'Le mot de passe ne respecte pas les exigences. Corrigez-le, puis réessayez.'
  }
  if (error.kind === 'unavailable') {
    return 'Le service est temporairement indisponible. Réessayez plus tard.'
  }
  if (error.kind === 'unknown') {
    return 'Le changement n’a pas pu être confirmé. Essayez de vous connecter avec le nouveau mot de passe. S’il est refusé, réessayez depuis le compte.'
  }
  return messageForIdentityError(error)
}

export function messageForConsentError(error: IdentityRequestError): string {
  if (error.code === 'csrf') {
    return 'La session de sécurité n’a pas pu être établie. Réessayez.'
  }
  if (error.status === 401 || error.code === 'authentification_refusee') {
    return 'La session n’est plus valable. Connectez-vous pour continuer.'
  }
  if (error.code === 'retrait_non_autorise') {
    return 'Ce texte requis ne peut pas être retiré. Le compte n’est pas suspendu pour autant.'
  }
  if (error.code === 'texte_inconnu') {
    return 'Ce texte n’est plus applicable. Actualisez la page, puis réessayez.'
  }
  if (error.code === 'nature_incompatible') {
    return 'Cette réponse ne correspond pas à ce texte. Actualisez la page, puis réessayez.'
  }
  if (error.code === 'consentement_absent') {
    return 'Aucune réponse enregistrée n’existe pour ce texte.'
  }
  if (error.kind === 'conflict') {
    return 'La situation a changé. Relisez les textes, puis réessayez.'
  }
  if (error.kind === 'network') {
    return 'Le résultat n’est pas confirmé. Actualisez la page pour vérifier l’état, puis réessayez si besoin.'
  }
  if (error.kind === 'validation') {
    return 'Cette réponse n’a pas pu être enregistrée. Relisez le texte, puis réessayez.'
  }
  if (error.kind === 'unknown') {
    return 'La réponse n’a pas pu être confirmée. Actualisez la page, puis réessayez si besoin.'
  }
  return messageForIdentityError(error)
}

export function messageForAuthorPathError(error: IdentityRequestError): string {
  if (error.code === 'csrf') {
    return 'La session de sécurité n’a pas pu être établie. Réessayez.'
  }
  if (error.status === 401 || error.code === 'authentification_refusee') {
    return 'La session n’est plus valable. Connectez-vous pour continuer.'
  }
  if (error.code === 'compte_ineligible' || error.code === 'portee_insuffisante') {
    return 'Le parcours auteur n’est disponible qu’après activation du compte.'
  }
  if (error.code === 'fiche_inconnue') {
    return 'Cette fiche auteur est inconnue. Vérifiez l’identifiant, puis réessayez.'
  }
  if (error.code === 'intention_conflictuelle') {
    return 'Cette demande entre en conflit avec une intention déjà enregistrée. Relisez les informations, puis réessayez.'
  }
  if (error.kind === 'forbidden') {
    return 'Le parcours auteur n’est pas disponible pour ce compte.'
  }
  if (error.kind === 'conflict') {
    return 'La situation a changé. Actualisez la page, puis réessayez.'
  }
  if (error.kind === 'network') {
    return 'Le résultat n’est pas confirmé. Actualisez la page pour vérifier l’état, puis réessayez si besoin.'
  }
  if (error.kind === 'validation') {
    return 'Cette demande n’a pas pu être enregistrée. Vérifiez l’identifiant de fiche, puis réessayez.'
  }
  if (error.kind === 'unknown') {
    return 'Le parcours auteur n’a pas pu être confirmé. Actualisez la page, puis réessayez si besoin.'
  }
  return messageForIdentityError(error)
}

export function messageForLogoutError(error: IdentityRequestError): string {
  if (error.code === 'csrf') {
    return 'La session de sécurité n’a pas pu être établie. Réessayez.'
  }
  if (error.kind === 'network') {
    return 'Le résultat de la déconnexion n’est pas confirmé. Vérifiez votre connexion, puis réessayez.'
  }
  if (error.kind === 'unknown') {
    return 'La déconnexion n’a pas pu être confirmée. Réessayez, ou actualisez la page.'
  }
  return messageForIdentityError(error)
}

export function messageForRecoveryRequestError(error: IdentityRequestError): string {
  if (error.code === 'csrf') {
    return 'La session de sécurité n’a pas pu être établie. Réessayez.'
  }
  if (error.kind === 'network') {
    return 'Le résultat de la demande n’est pas confirmé. Vérifiez votre connexion, puis réessayez si besoin.'
  }
  if (error.kind === 'quota') {
    return 'Trop de demandes. Réessayez dans un moment.'
  }
  if (error.kind === 'validation') {
    return 'Indiquez une adresse e-mail valide.'
  }
  if (error.kind === 'unknown') {
    return 'La demande n’a pas pu être confirmée. Réessayez.'
  }
  return messageForIdentityError(error)
}

export function messageForRecoveryProofError(error: IdentityRequestError): string {
  if (error.code === 'csrf') {
    return 'La session de sécurité n’a pas pu être établie. Réessayez.'
  }
  if (error.code === 'defi_invalide' || error.kind === 'validation') {
    return 'Ce lien n’est plus valable. Il peut être expiré, déjà utilisé ou incorrect.'
  }
  if (error.kind === 'network') {
    return 'Le lien n’a pas pu être contrôlé. Vérifiez votre connexion, puis réessayez.'
  }
  if (error.kind === 'unknown') {
    return 'Le lien n’a pas pu être contrôlé. Réessayez.'
  }
  return messageForIdentityError(error)
}

export function messageForRecoveryConfirmError(error: IdentityRequestError): string {
  if (error.code === 'csrf') {
    return 'La session de sécurité n’a pas pu être établie. Réessayez.'
  }
  if (error.code === 'defi_invalide') {
    return 'Ce lien n’est plus valable. Il peut être expiré, déjà utilisé ou incorrect.'
  }
  if (error.kind === 'network') {
    return 'Le résultat du changement n’est pas confirmé. Essayez de vous connecter avec le nouveau mot de passe. S’il est refusé, redemandez une récupération.'
  }
  if (error.kind === 'validation') {
    return 'Le mot de passe ne respecte pas les exigences. Corrigez-le, puis réessayez.'
  }
  if (error.kind === 'unknown') {
    return 'Le changement n’a pas pu être confirmé. Essayez de vous connecter avec le nouveau mot de passe. S’il est refusé, redemandez une récupération.'
  }
  return messageForIdentityError(error)
}

export function messageForOrganizationError(error: IdentityRequestError): string {
  if (error.code === 'csrf') {
    return 'La session de sécurité n’a pas pu être établie. Réessayez.'
  }
  if (error.status === 401 || error.code === 'authentification_refusee') {
    return 'La session n’est plus valable. Connectez-vous pour continuer.'
  }
  if (error.code === 'compte_ineligible' || error.code === 'portee_insuffisante') {
    return 'La création d’une organisation n’est disponible qu’après activation du compte.'
  }
  if (error.code === 'type_inconnu') {
    return 'Ce type d’organisation n’est plus disponible. Actualisez la page, puis réessayez.'
  }
  if (error.code === 'organisation_inconnue') {
    return 'Cette organisation est inconnue. Vérifiez la sélection, puis réessayez.'
  }
  if (error.code === 'organisation_ineligible') {
    return 'Cette organisation n’est plus disponible.'
  }
  if (error.code === 'intention_conflictuelle') {
    return 'Cette demande entre en conflit avec une intention déjà enregistrée. Relisez les informations, puis réessayez.'
  }
  if (error.kind === 'forbidden') {
    return 'La création d’une organisation n’est pas disponible pour ce compte.'
  }
  if (error.kind === 'conflict') {
    return 'La situation a changé. Actualisez la page, puis réessayez.'
  }
  if (error.kind === 'network') {
    return 'Le résultat n’est pas confirmé. Actualisez la page pour vérifier l’état, puis réessayez si besoin.'
  }
  if (error.kind === 'validation') {
    return 'Certains champs sont invalides. Corrigez-les, puis soumettez à nouveau.'
  }
  if (error.kind === 'unavailable') {
    return 'Le service est temporairement indisponible. Réessayez plus tard.'
  }
  if (error.kind === 'unknown') {
    return 'La demande d’organisation n’a pas pu être confirmée. Actualisez la page, puis réessayez si besoin.'
  }
  return messageForIdentityError(error)
}

export function messageForResendError(error: IdentityRequestError): string {
  if (error.code === 'csrf') {
    return 'La session de sécurité n’a pas pu être établie. Réessayez.'
  }
  if (error.kind === 'network') {
    return 'Le résultat du renvoi n’est pas confirmé. Consultez votre boîte de réception, puis réessayez si besoin.'
  }
  if (error.kind === 'quota') {
    return 'Trop de demandes de renvoi. Réessayez dans un moment.'
  }
  if (error.kind === 'validation') {
    return 'Indiquez une adresse e-mail valide.'
  }
  return messageForIdentityError(error)
}

export function messageForVerificationError(error: IdentityRequestError): string {
  if (error.code === 'csrf') {
    return 'La session de sécurité n’a pas pu être établie. Réessayez.'
  }
  if (error.status === 401 || error.code === 'authentification_refusee') {
    return 'La session n’est plus valable. Connectez-vous pour continuer.'
  }
  if (error.code === 'compte_ineligible' || error.code === 'portee_insuffisante') {
    return 'Le dépôt d’un dossier n’est disponible qu’après activation du compte.'
  }
  if (error.code === 'depot_refuse') {
    return 'Le dépôt n’est pas autorisé pour ce sujet.'
  }
  if (error.code === 'niveau_inconnu') {
    return 'Ce niveau de vérification n’est pas disponible.'
  }
  if (error.code === 'sujet_incompatible') {
    return 'Ce niveau exige une organisation. Choisissez une organisation, puis réessayez.'
  }
  if (error.code === 'organisation_ineligible') {
    return 'Cette organisation n’est plus disponible.'
  }
  if (error.code === 'exigences_indisponibles') {
    return 'Aucune exigence n’est applicable pour ce sujet, ce pays et ce niveau.'
  }
  if (error.code === 'dossier_inconnu' || error.code === 'piece_inconnue') {
    return 'Ce dossier n’est pas visible.'
  }
  if (error.code === 'dossier_fige') {
    return 'Ce dossier n’est plus modifiable. Les preuves soumises sont figées.'
  }
  if (error.code === 'dossier_incomplet') {
    return 'Le dossier n’est pas complet. Complétez les éléments manquants, puis soumettez à nouveau.'
  }
  if (error.code === 'fichier_irrecevable') {
    return 'Ce fichier n’est pas recevable. Utilisez un PDF, JPEG ou PNG de 8 Mo au plus.'
  }
  if (error.code === 'fichier_inaccessible') {
    return 'Une pièce n’est plus accessible. Le dossier reste en brouillon. Réessayez, ou signalez l’incident.'
  }
  if (error.code === 'intention_conflictuelle') {
    return 'Cette demande entre en conflit avec une intention déjà enregistrée. Relisez les informations, puis réessayez.'
  }
  if (error.kind === 'forbidden') {
    return 'Le dépôt d’un dossier n’est pas disponible pour ce compte.'
  }
  if (error.kind === 'not_found') {
    return 'Ce dossier n’est pas visible.'
  }
  if (error.kind === 'conflict') {
    return 'La situation a changé. Actualisez la page, puis réessayez.'
  }
  if (error.kind === 'network') {
    return 'Le résultat n’est pas confirmé. Actualisez la page pour vérifier l’état, puis réessayez si besoin.'
  }
  if (error.kind === 'validation') {
    return 'Certains champs sont invalides. Corrigez-les, puis soumettez à nouveau.'
  }
  if (error.kind === 'unavailable') {
    return 'Le service est temporairement indisponible. Réessayez plus tard.'
  }
  if (error.kind === 'unknown') {
    return 'La demande n’a pas pu être confirmée. Actualisez la page, puis réessayez si besoin.'
  }
  return messageForIdentityError(error)
}

export function messageForBackofficeError(error: IdentityRequestError): string {
  if (error.code === 'csrf') {
    return 'La session de sécurité n’a pas pu être établie. Réessayez.'
  }
  if (error.status === 401 || error.code === 'authentification_refusee') {
    return 'La session n’est plus valable. Connectez-vous pour continuer.'
  }
  if (error.code === 'portee_insuffisante') {
    return 'Cette session n’autorise pas le back-office. Connectez-vous avec une session normale.'
  }
  if (error.code === 'permission_insuffisante') {
    return 'Cette opération interne n’est pas autorisée pour ce compte.'
  }
  if (error.code === 'conflit_interet') {
    return 'Cette opération est liée à ce compte. Un agent indépendant est nécessaire.'
  }
  if (error.code === 'organisation_inconnue' || error.code === 'compte_inconnu' || error.code === 'demande_inconnue' || error.code === 'document_inconnu' || error.code === 'permission_inconnue') {
    return 'Cet élément n’est pas visible.'
  }
  if (error.code === 'organisation_ineligible' || error.code === 'compte_ineligible' || error.code === 'document_ineligible') {
    return 'Le compte, l’organisation ou le document indiqué n’est pas éligible à cette opération.'
  }
  if (error.code === 'affectation_requise') {
    return 'Le dossier doit d’abord être affecté au responsable courant.'
  }
  if (error.code === 'habilitation_inconnue') {
    return 'Cette habilitation n’est pas visible.'
  }
  if (error.code === 'tache_inconnue' || error.code === 'dossier_inconnu' || error.code === 'controle_inconnu' || error.code === 'piece_inconnue' || error.code === 'transmission_inconnue') {
    return 'Cet élément n’est pas visible.'
  }
  if (error.code === 'representation_non_resolue') {
    return 'La représentation principale n’est pas résolue. Transférez le principal avant la clôture.'
  }
  if (error.code === 'tache_cloturee' || error.code === 'dossier_decide' || error.code === 'etat_incompatible') {
    return 'La situation a changé. Actualisez la page, puis réévaluez l’action.'
  }
  if (error.code === 'controles_incomplets') {
    return 'Tous les contrôles doivent être consignés avant la décision.'
  }
  if (error.code === 'controles_insuffisants') {
    return 'L’approbation exige que les contrôles obligatoires soient satisfaits.'
  }
  if (error.code === 'decision_commerciale_indisponible') {
    return 'La décision commerciale n’est pas proposée ici.'
  }
  if (error.code === 'decision_organisationnelle_indisponible') {
    return 'La décision d’organisation n’est pas proposée ici.'
  }
  if (error.code === 'preconditions_commerciales_insuffisantes') {
    return 'L’approbation commerciale exige l’identité préalable, un profil fiscal et un compte de reversement du même sujet.'
  }
  if (error.code === 'non_applicable_interdit') {
    return 'Cette exigence ne peut pas être marquée non applicable.'
  }
  if (error.kind === 'forbidden') {
    return 'Cette opération interne n’est pas autorisée pour ce compte.'
  }
  if (error.kind === 'not_found') {
    return 'Cet élément n’est pas visible.'
  }
  if (error.kind === 'conflict') {
    return 'La situation a changé. Actualisez la page, puis réessayez.'
  }
  if (error.kind === 'network') {
    return 'Le résultat n’est pas confirmé. Consultez l’état du dossier, puis reprenez l’action appropriée.'
  }
  if (error.kind === 'validation') {
    return 'Certains champs sont invalides. Corrigez-les, puis soumettez à nouveau.'
  }
  if (error.kind === 'unavailable') {
    return 'Le service est temporairement indisponible. Réessayez plus tard.'
  }
  if (error.kind === 'unknown') {
    return 'Le résultat n’est pas confirmé. Consultez l’état du dossier, puis reprenez l’action appropriée.'
  }
  return messageForIdentityError(error)
}

export function messageForSupportError(error: IdentityRequestError): string {
  if (error.code === 'fichier_irrecevable') {
    return 'Ce fichier n’est pas recevable. Utilisez un PDF, JPEG ou PNG de 8 Mo au plus.'
  }
  if (error.code === 'fichier_inaccessible') {
    return 'Cette pièce n’est plus accessible. Consultez l’état de la demande, puis réessayez.'
  }
  if (error.code === 'piece_inconnue' || error.code === 'demande_inconnue') {
    return 'Cet élément n’est pas visible.'
  }
  return messageForBackofficeError(error)
}

