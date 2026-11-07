<script setup lang="ts">
definePageMeta({
  layout: 'public',
})

const selectedPath = ref('lecteur')
const retrying = ref(false)
const demoEmail = ref('')
const demoPassword = ref('')
const demoCountry = ref('')
const demoConsent = ref(false)
const demoPrivacy = ref(false)

const demoPendingTexts = [
  {
    id: '01DEMOTEXTVERSION000000001',
    type: 'cgu',
    version: '2.0',
    langue: 'fr',
    requis_pour_activation: true,
    contenu: 'Texte de démonstration d’une nouvelle version des conditions générales.',
  },
]

const demoConsents = [
  {
    id: '01DEMOCONSENT000000000001',
    texte_version_id: '01DEMOTEXTVERSION000000001',
    type: 'cgu',
    version: '1.0',
    langue: 'fr',
    nature: 'acceptation',
    accepte_a: '2026-09-01T10:00:00Z',
    retire_a: null,
  },
  {
    id: '01DEMOCONSENT000000000002',
    texte_version_id: '01DEMOTEXTVERSION000000002',
    type: 'newsletter',
    version: '1.0',
    langue: 'fr',
    nature: 'consentement_optionnel',
    accepte_a: '2026-09-01T10:00:00Z',
    retire_a: null,
  },
]

const demoOrganization = {
  id: '01DEMOORGANISATION00000001',
  raison_sociale: 'Maison du Fleuve',
  pays: 'CD',
  ville: 'Kinshasa',
  statut_operationnel: 'active',
  type: {
    id: '01DEMOTYPEORGANISATION00001',
    code: 'editeur',
    libelle: 'Éditeur',
  },
}

const demoOrganizationTypes = [
  { id: '01DEMOTYPEORGANISATION00001', code: 'editeur', libelle: 'Éditeur' },
]

const demoOrgDraft = ref({
  type_organisation_id: '',
  raison_sociale: '',
  pays: '',
  ville: '',
  email_pro: '',
  site: '',
})

const demoFiscalDraft = ref({
  identifiant_fiscal: '',
  pays_fiscal: 'CD',
  nom_legal: 'Amina Kabila',
})

const demoPayoutDraft = ref({
  canal: 'virement',
  prestataire: '',
  titulaire: 'Amina Kabila',
  pays: 'CD',
  devise: 'USD',
  reference_prestataire: 'REF-PAY-001',
  affichage_masque: 'Compte se terminant par 001',
})

const demoDossier = {
  id: '01DEMODOSSIERKYC0000000001',
  numero: 'KV-DEMO00001',
  version: 1,
  niveau: 'partenaire_commercial',
  statut: 'draft',
  sujet: {
    type: 'particulier' as const,
    organisation_id: null,
    raison_sociale: null,
  },
}

const demoRequirements = [
  {
    id: '01DEMOEXIGENCE0000000000001',
    code: 'identite_personne',
    version_regles: '1.0',
    volet: 'identite',
    nature_preuve: 'identite_personne',
    obligatoire: true,
    autorise_non_applicable: false,
  },
]

const demoPiece = {
  id: '01DEMOPIECE0000000000000001',
  type_doc: 'identite_personne',
  statut_verification: 'pending',
  depose_a: '2026-09-16T10:00:00Z',
  numero: null,
  autorite: null,
}

const demoSubjectOptions = [
  {
    id: 'particulier',
    kind: 'particulier' as const,
    label: 'Compte personnel',
    description: 'Dossier partenaire commercial pour ce compte.',
    niveau: 'partenaire_commercial' as const,
  },
]

const demoControl = {
  id: '01DEMOCONTROLE000000000001',
  resultat: 'pending',
  observation: null,
  justification_non_applicable: null,
  controle_a: null,
  exigence: {
    id: '01DEMOEXIGENCE0000000000001',
    code: 'identite_representant',
    volet: 'identite',
    obligatoire: true,
    autorise_non_applicable: false,
  },
}

const demoTask = {
  id: '01DEMOTACHE000000000000001',
  etat_travail: 'a_affecter',
  priorite: 'normale',
  motif_priorite: null,
  responsable_id: null,
  cree_a: '2026-09-16T08:00:00Z',
  affecte_a: null,
  premiere_prise_a: null,
  echeance_prise_a: '2026-09-16T16:00:00Z',
  echeance_reponse_a: '2026-09-17T16:00:00Z',
  clos_a: null,
}

const demoTaskRow = {
  tache: demoTask,
  dossier: demoDossier,
  conflit_interet: false,
  capacites: {
    consulter: true,
    affecter: true,
    prendre_en_charge: false,
  },
}

const demoSession = {
  id: '01DEMOSESSION0000000000001',
  courante: true,
  portee: 'normal',
  statut: 'active',
  ouverte_a: '2026-09-15T08:00:00Z',
  derniere_activite_a: '2026-09-15T17:00:00Z',
  expire_absolue_a: '2026-09-16T12:00:00Z',
  expire_inactivite_a: '2026-09-15T18:00:00Z',
  contexte: 'Navigateur de démonstration',
}

const demoPermissionOptions = [
  { code: 'controler_kyc', libelle: 'Contrôler un dossier' },
  { code: 'suspendre_compte', libelle: 'Suspendre un compte' },
]

const demoAttributions = [
  {
    id: '01DEMOATTRIBUTION0000000001',
    permission: 'controler_kyc',
    libelle: 'Contrôler un dossier',
    etat: 'effective',
    attribue_a: '2026-09-16T08:00:00Z',
    expire_a: null,
    revoque_a: null,
    motif: 'Attribution de démonstration.',
  },
  {
    id: '01DEMOATTRIBUTION0000000002',
    permission: 'suspendre_compte',
    libelle: 'Suspendre un compte',
    etat: 'revoquee',
    attribue_a: '2026-09-01T08:00:00Z',
    expire_a: null,
    revoque_a: '2026-09-10T12:00:00Z',
    motif: 'Fin de la période de démonstration.',
  },
]

const demoSupportMessages = [
  {
    id: '01DEMOECHANGE0000000000001',
    origine: 'demandeur',
    visibilite: 'demandeur',
    contenu: 'Je n’arrive plus à ouvrir une session normale.',
    date: '2026-09-16T08:00:00Z',
    canal: 'formulaire_public',
  },
  {
    id: '01DEMOECHANGE0000000000002',
    origine: 'agent',
    visibilite: 'interne',
    contenu: 'Note interne de démonstration.',
    date: '2026-09-16T09:00:00Z',
    canal: 'backoffice',
  },
]

const demoSupportPieces = [
  {
    id: '01DEMOPIECE000000000000001',
    type: 'identite',
    acces: 'demandeur',
    echange_id: null,
    depose_a: '2026-09-16T08:10:00Z',
  },
  {
    id: '01DEMOPIECE000000000000002',
    type: 'complement',
    acces: 'interne',
    echange_id: null,
    depose_a: '2026-09-16T09:10:00Z',
  },
]

function onRetry() {
  retrying.value = true
}
</script>

<template>
  <main class="mx-auto flex max-w-4xl flex-col gap-section px-page py-section">
    <PageHeader
      title="Primitives UI"
      description="Page de développement : états des composants nécessaires au choix de parcours. Ce n’est pas un écran métier."
    />
    <Breadcrumbs
      :items="[
        { label: 'Mon compte', to: '/compte' },
        { label: 'Vérification', to: '/compte/verification' },
        { label: 'Dossier DEMO-1' },
      ]"
    />
    <CopyReference value="DEMO-1" />

    <Accordion
      title="École"
      icon="graduation-cap"
      :open="true"
    >
      <p class="font-sans text-sm text-text-muted">
        Accordéon ouvert : liste compacte des pièces d’un type.
      </p>
    </Accordion>
    <Accordion
      title="Université"
      icon="graduation-cap"
    >
      <p class="font-sans text-sm text-text-muted">
        Accordéon fermé.
      </p>
    </Accordion>

    <InlineAlert tone="info" title="Information">
      Exemple de message d’information renvoyé par le serveur.
    </InlineAlert>
    <InlineAlert tone="success" title="Compte créé">
      Exemple de confirmation après une réponse de succès.
    </InlineAlert>
    <InlineAlert tone="warning" title="Résultat incertain">
      Exemple d’avertissement lorsque le résultat n’est pas confirmé.
    </InlineAlert>
    <InlineAlert tone="error" title="Création refusée">
      Exemple de message d’erreur renvoyé par le serveur.
    </InlineAlert>

    <Stepper
      label="Démonstration de la barre d’étapes"
      :steps="[
        { id: 'parcours', label: 'Choix du parcours', status: 'current' },
        { id: 'informations', label: 'Informations personnelles', status: 'upcoming' },
        { id: 'securite', label: 'Sécurité du compte', status: 'upcoming' },
        { id: 'verification', label: 'Vérification e-mail', status: 'upcoming' },
        { id: 'activation', label: 'Activation', status: 'upcoming' },
      ]"
    />

    <RadioGroup
      v-model="selectedPath"
      legend="Cartes tuiles"
      name="parcours-demo-tiles"
      variant="cards"
    >
      <ChoiceCard
        variant="tile"
        value="lecteur"
        title="Lecteur"
        subtitle="Compte personnel"
        description="Consulter et acheter des livres."
      >
        <template #icon>
          <UiIcon name="book-open" size="lg" />
        </template>
      </ChoiceCard>
      <ChoiceCard
        variant="tile"
        value="auteur"
        title="Auteur"
        subtitle="Compte personnel"
        description="Publier et créer votre profil."
      >
        <template #icon>
          <UiIcon name="pen-line" size="lg" />
        </template>
      </ChoiceCard>
      <ChoiceCard
        variant="tile"
        value="hors-v1"
        title="Parcours indisponible"
        subtitle="Organisation"
        description="Exemple d’option désactivée."
        disabled
        disabled-reason="Ce parcours n’est pas proposé pour le moment."
      >
        <template #icon>
          <UiIcon name="building-2" size="lg" />
        </template>
      </ChoiceCard>
    </RadioGroup>

    <RadioGroup v-model="selectedPath" legend="Parcours" name="parcours-demo">
      <ChoiceCard
        value="lecteur"
        title="Lecteur"
        description="Consulter et acheter des livres."
      />
      <ChoiceCard
        value="auteur"
        title="Auteur"
        description="Un même compte personnel peut cumuler plusieurs usages."
      />
      <ChoiceCard
        value="hors-v1"
        title="Parcours indisponible"
        description="Exemple d’option désactivée."
        disabled
        disabled-reason="Ce parcours n’est pas proposé pour le moment."
      />
    </RadioGroup>

    <div class="grid gap-stack sm:max-w-md">
      <FormField label="Pays" required>
        <template #default="{ id, describedBy, invalid }">
          <SelectInput
            :id="id"
            v-model="demoCountry"
            icon="globe"
            placeholder="Choisir un pays"
            :options="[
              { value: 'CD', label: 'République démocratique du Congo' },
              { value: 'FR', label: 'France' },
              { value: 'CA', label: 'Canada' },
              { value: 'BE', label: 'Belgique' },
            ]"
            :invalid="invalid"
            :aria-describedby="describedBy"
          />
        </template>
      </FormField>
      <FormField label="Adresse e-mail" required hint="Démonstration de champ.">
        <template #default="{ id, describedBy, invalid }">
          <EmailInput
            :id="id"
            v-model="demoEmail"
            placeholder="votre.email@exemple.com"
            :invalid="invalid"
            :aria-describedby="describedBy"
          />
        </template>
      </FormField>
      <FormField label="Mot de passe" required>
        <template #default="{ id }">
          <PasswordInput
            :id="id"
            v-model="demoPassword"
            placeholder="Votre mot de passe"
          />
        </template>
      </FormField>
      <PasswordRequirements :password="demoPassword" />
      <ConsentControl
        v-model="demoConsent"
        required
        label="J’accepte les Conditions générales d’utilisation"
        consult-label="Consulter les CGU"
        consult-title="Conditions générales d’utilisation"
        consult-version="1.0"
        consult-content="Texte de démonstration des conditions générales d’utilisation."
      />
      <ConsentControl
        v-model="demoPrivacy"
        required
        label="J’ai lu et j’accepte la Politique de confidentialité"
        consult-label="Consulter la politique"
        consult-title="Politique de confidentialité"
        consult-version="1.0"
        consult-content="Texte de démonstration de la politique de confidentialité."
      />
      <EmailActivationPanel
        email-masque="a***@exemple.com"
        :wait-seconds="12"
      />
      <EmailActivationPanel
        variant="confirm"
        email-masque="a***@exemple.com"
        expire-label="10 septembre 2026, 20:00 UTC"
      />
      <LoginForm initial-email="a***@exemple.com" />
      <RecoveryRequestForm />
      <RecoveryRequestResult />
      <PasswordResetForm email-masque="a***@exemple.com" expire-label="15 septembre 2026, 12:00 UTC" />
      <ChangePasswordForm />
      <PendingLegalTextsPanel :texts="demoPendingTexts" />
      <ConsentHistory :consents="demoConsents" />
      <AuthorActivationPanel
        :path="{ etat: 'non_active', fiche: null, demande: null }"
        can-enable
      />
      <AuthorActivationPanel
        :path="{
          etat: 'active',
          fiche: { id: '01DEMOAUTEURFICHE000000001', nom_affiche: 'Amina K.', statut_publication: 'draft' },
          demande: null,
        }"
      />
      <AuthorActivationPanel
        :path="{
          etat: 'revendication_en_examen',
          fiche: null,
          demande: { reference: 'RA-DEMO00001' },
        }"
      />
      <OrganizationCard :organization="demoOrganization" />
      <OrganizationCard
        :organization="demoOrganization"
        selectable
      />
      <OrganizationLookup
        :matches="[demoOrganization]"
        load-state="ready"
      />
      <OrganizationForm
        :types="demoOrganizationTypes"
        :draft="demoOrgDraft"
        @update:draft="demoOrgDraft = $event"
      />
      <RepresentativeTransferRequest can-transfer />
      <RepresentativeTransferComparison />
      <VerificationDossierCard :dossier="demoDossier" selectable />
      <VerificationSubjectSelector :options="demoSubjectOptions" model-value="particulier" />
      <VerificationRequirements
        :requirements="demoRequirements"
        :pieces="[demoPiece]"
        :gaps="[{ code: 'profil_fiscal', volet: 'fiscal', motif: 'profil_fiscal_manquant' }]"
      />
      <FileDropzone label="Pièce d’identité" hint="PDF, JPEG ou PNG, 8 Mo au plus." />
      <EvidenceFileRow :piece="demoPiece" />
      <FiscalProfileForm :draft="demoFiscalDraft" @update:draft="demoFiscalDraft = $event" />
      <PayoutAccountForm :draft="demoPayoutDraft" @update:draft="demoPayoutDraft = $event" />
      <DossierSubmissionDialog
        :complete="false"
        :gaps="[{ code: 'profil_fiscal', volet: 'fiscal', motif: 'profil_fiscal_manquant' }]"
      />
      <PriorityBadge priorite="urgente" motif="Délai de complément proche." />
      <DeadlineIndicator label="Prise en charge" iso="2026-09-16T16:00:00Z" />
      <ServiceDeadlinePanel :task="demoTask" />
      <AssignmentPanel :can-assign="true" :assigned-to-self="false" />
      <ConflictOfInterestAlert :visible="true" />
      <ServiceTaskQueue :rows="[demoTaskRow]" />
      <InternalPermissionGrantForm
        :options="demoPermissionOptions"
        can-grant
      />
      <InternalPermissionHistory
        :rows="demoAttributions"
        can-revoke
      />
      <ServiceConfigurationEditor can-approve />
      <SubstituteAssignmentForm can-assign />
      <AcknowledgementBadge niveau="engage_local" />
      <TransmissionTable
        :rows="[{
          id: '01DEMOTRANSMISSION000000001',
          etat: 'failed',
          canal: 'email',
          type: 'defi_email_activation',
          operation: 'activation',
          destination_masquee: 'a***@example.test',
          nombre_tentatives: 3,
          prochaine_tentative_a: null,
          niveau_acquittement: null,
          envoye_a: null,
        }]"
        can-retry
      />
      <SupportConversation :messages="demoSupportMessages" />
      <SupportEvidencePanel
        :pieces="demoSupportPieces"
        can-deposit
        can-internal
        selected-name="identite.pdf"
      />
      <SupportReplyComposer can-reply can-internal />
      <AccountClosureRequest />
      <ClaimantIdentityReview can-verify />
      <VerificationControlGrid :controls="[demoControl]" can-record />
      <VerificationDecisionPanel can-decide controls-complete />
      <VerificationCapabilityPanel />
      <RenewalPanel />
      <VerificationRestrictionPanel />
      <AccountSessionList
        :sessions="[demoSession]"
        load-state="ready"
      />
      <AccountStatusPanel statut="active" />
      <AccessScopePanel portee="normal" />
      <StatusBadge label="Actif" tone="success" />
      <StatusBadge dot label="En cours" tone="success" />
      <StatusBadge dot label="En retard" tone="danger" />
      <StatusBadge icon="success" label="Vérifiée" tone="success" />
      <StatusBadge icon="clock" label="En cours" tone="warning" />
      <StatusBadge icon="minus-circle" label="Non vérifiée" tone="neutral" />
      <StatusBadge icon="error" label="Expirée" tone="danger" />
      <RestrictionBanner title="Activation encore nécessaire">
        Exemple de restriction de portée.
      </RestrictionBanner>
      <ForbiddenState />
      <SessionExpiredState />
      <AppSidebar
        label="Administration"
        :items="[
          { id: 'accueil', label: 'Accueil', to: '/dev/primitives', current: true, icon: 'layout-dashboard' },
          { id: 'taches', label: 'File de tâches', to: '/administration/taches', icon: 'file-check' },
        ]"
      />
      <div class="grid gap-gutter sm:grid-cols-2">
        <SummaryStatCard
          title="État du compte"
          icon="user"
          badge-label="Actif"
          badge-tone="success"
          hint="Votre compte est en règle."
        />
        <MetricCard
          title="Dossiers KYC à traiter"
          icon="file-check"
          :value="18"
          hint="5 non affectés"
        />
        <MetricCard
          title="Transmissions à reprendre"
          icon="mail"
          unavailable
          hint="Attribution de reprise des envois requise."
        />
        <SectionCard title="Profil et identité">
          <p class="font-sans text-sm text-text-muted">
            Carte de section utilisée dans l’espace client.
          </p>
        </SectionCard>
      </div>
      <ErrorSummary
        :errors="demoConsent ? [] : [{ id: 'demo', message: 'Exemple de résumé d’erreur.' }]"
      />
    </div>

    <div class="flex flex-wrap items-start gap-gutter">
      <UiButton>Continuer vers l’inscription</UiButton>
      <UiButton variant="secondary">Action secondaire</UiButton>
      <UiButton variant="outline">Créer un compte</UiButton>
      <UiButton loading>Envoi</UiButton>
      <UiButton disabled disabled-reason="Choisissez un parcours disponible.">
        Continuer vers l’inscription
      </UiButton>
    </div>

    <p>
      <UiLink to="/">Retour à l’accueil</UiLink>
    </p>

    <ErrorState
      title="Impossible de charger les parcours"
      message="La liste n’a pas pu être obtenue. Vérifiez votre connexion, puis réessayez."
      retry-label="Réessayer"
      :retrying="retrying"
      @retry="onRetry"
    />

    <EmptyState
      title="Aucun parcours disponible"
      message="Aucun parcours d’inscription n’est proposé pour le moment."
    >
      <template #action>
        <UiLink to="/">Retour à l’accueil</UiLink>
      </template>
    </EmptyState>

    <div class="grid gap-stack">
      <Skeleton variant="card" />
      <Skeleton variant="form" />
    </div>
  </main>
</template>
