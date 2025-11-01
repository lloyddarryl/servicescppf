Bonjour {{ $retraite->civilite ?? '' }} {{ $retraite->prenoms }} {{ $retraite->nom }},

========================================
🚨 RAPPEL IMPORTANT
========================================

Votre certificat de vie n'a pas été déposé ou a expiré.

Nous vous rappelons qu'il est OBLIGATOIRE de déposer votre certificat de vie annuel pour continuer à percevoir votre pension de retraite.

----------------------------------------
VOS INFORMATIONS
----------------------------------------
Numéro de pension : {{ $retraite->numero_pension }}
Email : {{ $retraite->email }}
Téléphone : {{ $retraite->telephone ?? 'Non renseigné' }}

----------------------------------------
COMMENT DÉPOSER VOTRE CERTIFICAT
----------------------------------------

En ligne :
1. Connectez-vous à votre espace personnel : {{ config('app.frontend_url') }}/connexion
2. Cliquez sur "Documents" dans le menu
3. Sélectionnez "Déposer un document"
4. Choisissez le type "Certificat de vie"
5. Téléchargez votre certificat (PDF, JPG ou PNG)

Sur place :
Vous pouvez également déposer votre certificat directement à nos bureaux :

CPPF - Siège social
[Adresse complète]
Horaires : Lundi - Vendredi, 8h - 17h

----------------------------------------
IMPORTANT
----------------------------------------
- Le certificat doit être récent (moins de 3 mois)
- Il doit être signé par une autorité compétente
- Le document doit être lisible et en bon état

----------------------------------------
BESOIN D'AIDE ?
----------------------------------------
Notre équipe est disponible pour vous accompagner :

Téléphone : [Numéro de téléphone]
Email : support@cppf.sn
Horaires : Lundi - Vendredi, 8h - 17h

========================================

Ce message a été envoyé automatiquement, merci de ne pas y répondre.

Caisse des Pensions et Prestations Familiales (CPPF)
© {{ date('Y') }} CPPF - Tous droits réservés