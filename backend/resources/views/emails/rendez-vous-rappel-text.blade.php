# RAPPEL DE RENDEZ-VOUS - CPPF

Bonjour {{ $user->prenoms }} {{ $user->nom }},

Nous vous rappelons que vous avez un RENDEZ-VOUS PRÉVU DEMAIN avec nos services.

=== DÉTAILS DE VOTRE RENDEZ-VOUS ===

Numéro de demande : {{ $demande->numero_demande }}
Date et heure : {{ $demande->date_rdv_confirme->format('l d F Y à H:i') }}
@if($demande->lieu_rdv)
Lieu : {{ $demande->lieu_rdv }}
@endif
Motif : {{ $demande->motif_complet }}
@if($demande->commentaires)
Vos commentaires : {{ $demande->commentaires }}
@endif

=== INFORMATIONS IMPORTANTES ===

- Merci d'arriver 15 MINUTES AVANT l'heure prévue
- N'oubliez pas d'apporter une PIÈCE D'IDENTITÉ VALIDE
- Si vous ne pouvez pas vous présenter, veuillez nous en informer au plus tôt

=== CONTACT ===

En cas de problème ou pour toute question :
- Téléphone : +241 XX XX XX XX
- Email : contact@cppf.ga
- Site web : {{ env('APP_URL') }}

=== ACCÈS RAPIDE ===

Voir mes rendez-vous : {{ env('APP_URL') }}/actifs/rendez-vous
Mon tableau de bord : {{ env('APP_URL') }}/dashboard

Nous vous remercions de votre confiance et restons à votre disposition.

---
CAISSE DES PENSIONS ET PRESTATIONS FAMILIALES (CPPF)
Libreville, Gabon | {{ env('APP_URL') }}

CECI EST UN MESSAGE AUTOMATIQUE - Merci de ne pas répondre à cet email.
Pour toute question, utilisez nos canaux de contact habituels.

Message envoyé le {{ now()->format('d/m/Y à H:i') }}