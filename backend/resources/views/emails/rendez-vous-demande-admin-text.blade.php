NOUVELLE DEMANDE DE RENDEZ-VOUS - CPPF e-Services
=================================================

DÉTAILS DE LA DEMANDE
---------------------
Numéro : {{ $demande->numero_demande }}
Statut : {{ $demande->statut_info['nom'] ?? 'En attente' }}

DEMANDEUR
---------
Nom : {{ $demande->user_prenoms }} {{ $demande->user_nom }}
Type : {{ $demande->user_type === 'agent' ? 'Agent actif' : 'Retraité' }}
Email : {{ $demande->user_email }}
Téléphone : {{ $demande->user_telephone ?? 'Non renseigné' }}

RENDEZ-VOUS SOUHAITÉ
-------------------
Date : {{ $demande->date_demandee->format('d/m/Y') }}
Heure : {{ $demande->heure_demandee }}
Motif : {{ $demande->motif_complet }}

@if($demande->commentaires)
COMMENTAIRES
-----------
{{ $demande->commentaires }}
@endif

INFORMATIONS
-----------
Date de soumission : {{ $demande->date_soumission->format('d/m/Y à H:i') }}

ACTION REQUISE : Veuillez traiter cette demande dans l'interface d'administration.

---
Email automatique généré par CPPF e-Services
Ne pas répondre à cet email