<p>Bonjour,</p>
<p>Une demande de réinitialisation du mot de passe a été reçue pour votre compte Livreaura.</p>
<p><a href="{{ $actionUrl }}">Choisir un nouveau mot de passe</a></p>
@if ($expiresAtLabel)
<p>Ce lien expire le {{ $expiresAtLabel }}.</p>
@endif
<p>Si vous n’êtes pas à l’origine de cette demande, ignorez ce message. Votre mot de passe actuel reste inchangé.</p>
