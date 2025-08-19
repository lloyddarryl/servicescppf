<?php
// ================================================================
// 2. reclamation-created.blade.php - MISE À JOUR POUR L'ADMIN
// ================================================================
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle réclamation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header { background: #1E3A8A; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; margin: -20px -20px 20px -20px; }
        .info-box { background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 15px 0; border-left: 4px solid #1E3A8A; }
        .footer { text-align: center; margin-top: 30px; padding: 20px; border-top: 1px solid #eee; color: #666; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; background: #F59E0B; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1> Nouvelle Réclamation CPPF</h1>
            <p>Une nouvelle réclamation a été soumise sur la plateforme e-Services</p>
        </div>

        <div class="info-box">
            <h3> Informations de la réclamation</h3>
            <p><strong>Numéro :</strong> {{ $reclamation->numero_reclamation }}</p>
            <p><strong>Type :</strong> {{ $typeReclamation['nom'] ?? $reclamation->type_reclamation }}</p>
            @if($reclamation->sujet_personnalise)
                <p><strong>Sujet :</strong> {{ $reclamation->sujet_personnalise }}</p>
            @endif
            <p><strong>Priorité :</strong> <span class="badge">{{ strtoupper($prioriteInfo['nom'] ?? $reclamation->priorite) }}</span></p>
            <p><strong>Date de soumission :</strong> {{ $reclamation->date_soumission->format('d/m/Y à H:i') }}</p>
            <p><strong>Statut :</strong> En attente de traitement</p>
        </div>

        <div class="info-box">
            <h3> Informations du demandeur</h3>
            <p><strong>Identité complète :</strong> 
                {{ $user->sexe && strtoupper($user->sexe) === 'M' ? 'M.' : ($user->situation_matrimoniale && in_array(strtolower($user->situation_matrimoniale), ['mariee', 'marie']) ? 'Mme' : 'Mlle') }} 
                {{ $user->prenoms }} {{ $user->nom }}
            </p>
            <p><strong>Email :</strong> {{ $user->email }}</p>
            @if($user->telephone)
                <p><strong>Téléphone :</strong> {{ $user->telephone }}</p>
            @endif
            
            {{-- ✅ NOUVEAU : Informations de civilité --}}
            @if($user->sexe)
                <p><strong>Sexe :</strong> {{ strtoupper($user->sexe) === 'M' || strtoupper($user->sexe) === 'MASCULIN' ? 'Masculin' : 'Féminin' }}</p>
            @endif
            @if($user->situation_matrimoniale)
                <p><strong>Situation matrimoniale :</strong> 
                    @switch(strtolower($user->situation_matrimoniale))
                        @case('celibataire') Célibataire @break
                        @case('marie') @case('mariee') Marié(e) @break
                        @case('divorce') @case('divorcee') Divorcé(e) @break
                        @case('veuf') @case('veuve') Veuf/Veuve @break
                        @case('concubinage') En concubinage @break
                        @case('separe') @case('separee') Séparé(e) @break
                        @default {{ ucfirst($user->situation_matrimoniale) }}
                    @endswitch
                </p>
            @endif
            
            <p><strong>Type de compte :</strong> {{ $reclamation->user_type === 'agent' ? 'Agent actif' : 'Retraité' }}</p>
            @if($reclamation->user_type === 'agent' && isset($user->matricule_solde))
                <p><strong>Matricule :</strong> {{ $user->matricule_solde }}</p>
                <p><strong>Poste :</strong> {{ $user->poste ?? 'Non spécifié' }}</p>
                <p><strong>Direction :</strong> {{ $user->direction ?? 'Non spécifiée' }}</p>
            @elseif($reclamation->user_type === 'retraite' && isset($user->numero_pension))
                <p><strong>N° Pension :</strong> {{ $user->numero_pension }}</p>
            @endif
        </div>

        <div class="info-box">
            <h3> Description du problème</h3>
            <div style="background: white; padding: 15px; border-radius: 4px; border: 1px solid #ddd;">
                <p style="margin: 0; white-space: pre-wrap;">{{ $reclamation->description }}</p>
            </div>
        </div>

        @if($reclamation->documents && count($reclamation->documents) > 0)
            <div class="info-box">
                <h3>📎 Documents joints ({{ count($reclamation->documents) }} fichier(s))</h3>
                @foreach($reclamation->documents as $index => $document)
                    <p style="margin: 5px 0;">
                        <strong>{{ $index + 1 }}.</strong> {{ $document['nom_original'] }} 
                        <span style="color: #666; font-size: 0.9em;">({{ number_format($document['taille'] / 1024, 1) }} KB - {{ strtoupper($document['type']) }})</span>
                    </p>
                @endforeach
                <p style="margin-top: 10px; font-size: 0.9em; color: #666;">
                    📎 Les documents sont disponibles dans l'interface d'administration pour traitement.
                </p>
            </div>
        @endif

        <div class="footer">
            <p><strong> Action requise :</strong> Cette réclamation nécessite votre attention et un traitement dans les meilleurs délais.</p>
            <hr style="margin: 20px 0;">
            <p style="font-size: 0.9em; color: #888;">
                Cette notification a été générée automatiquement par le système e-Services CPPF.<br>
                Pour répondre au demandeur, utilisez l'email : <strong>{{ $user->email }}</strong>
            </p>
        </div>
    </div>
</body>
</html>

