{{-- resources/views/emails/rendez-vous-annulation-admin.blade.php --}}

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rendez-vous annulé par l'utilisateur</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .info-block { background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545; }
        .status { display: inline-block; padding: 5px 10px; border-radius: 3px; font-weight: bold; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .footer { background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚫 Rendez-vous annulé</h1>
        <p>CPPF e-Services - Notification d'annulation</p>
    </div>

    <div class="content">
        <div class="warning">
            <strong>⚠️ Annulation par l'utilisateur</strong><br>
            Un utilisateur vient d'annuler sa demande de rendez-vous.
        </div>

        <h2>Détails du rendez-vous annulé</h2>
        
        <div class="info-block">
            <strong>Numéro de demande :</strong> {{ $demande->numero_demande }}<br>
            <strong>Statut :</strong> <span class="status status-cancelled">🚫 Annulé</span>
        </div>

        <div class="info-block">
            <strong>Utilisateur :</strong><br>
            👤 {{ $demande->user_prenoms }} {{ $demande->user_nom }}<br>
            📧 {{ $demande->user_email }}<br>
            📞 {{ $demande->user_telephone ?? 'Non renseigné' }}<br>
            🏷️ Type : {{ $demande->user_type === 'agent' ? 'Agent actif' : 'Retraité' }}
        </div>

        <div class="info-block">
            <strong>Rendez-vous qui était prévu :</strong><br>
            📅 Date : {{ $demande->date_demandee ? $demande->date_demandee->format('d/m/Y') : 'Date non disponible' }}<br>
            🕐 Heure : {{ $demande->heure_demandee ? substr($demande->heure_demandee, 0, 5) : 'Heure non disponible' }}<br>
            🎯 Motif : {{ $demande->motif_complet ?? 'Motif non spécifié' }}
        </div>

        @if($demande->commentaires)
        <div class="info-block">
            <strong>Commentaires initiaux :</strong><br>
            {{ $demande->commentaires }}
        </div>
        @endif

        @if($motifAnnulation)
        <div class="info-block">
            <strong>Motif d'annulation :</strong><br>
            {{ $motifAnnulation }}
        </div>
        @endif

        <div class="info-block">
            <strong>Chronologie :</strong><br>
            📝 Demande soumise le : {{ $demande->date_soumission ? $demande->date_soumission->format('d/m/Y à H:i') : 'Date inconnue' }}<br>
            🚫 Annulée le : {{ now()->format('d/m/Y à H:i') }}
        </div>

        <p><strong>Action :</strong> Ce créneau est maintenant disponible pour d'autres demandes.</p>
    </div>

    <div class="footer">
        <p>Email automatique généré par CPPF e-Services<br>
        Ne pas répondre à cet email</p>
    </div>
</body>
</html>