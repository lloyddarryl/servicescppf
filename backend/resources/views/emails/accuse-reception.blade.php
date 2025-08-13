<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accusé de réception - Réclamation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #1E3A8A, #3B82F6); color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; margin: -20px -20px 20px -20px; }
        .success-icon { font-size: 3rem; margin-bottom: 10px; }
        .info-box { background: #f0f9ff; padding: 15px; border-radius: 6px; margin: 15px 0; border-left: 4px solid #3B82F6; }
        .important-box { background: #fef3c7; padding: 15px; border-radius: 6px; margin: 15px 0; border-left: 4px solid #F59E0B; }
        .footer { text-align: center; margin-top: 30px; padding: 20px; border-top: 1px solid #eee; color: #666; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; color: white; }
        .badge-success { background: #10B981; }
        .badge-priority { background: #F59E0B; }
        .attachment-notice { background: #e0f2fe; padding: 12px; border-radius: 6px; border-left: 4px solid #0288d1; margin: 15px 0; }
        .button { display: inline-block; padding: 12px 24px; background: #1E3A8A; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 10px 0; }
        .button:hover { background: #1E40AF; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Accusé de Réception</h1>
            <p>Votre réclamation a été enregistrée avec succès</p>
        </div>

        <div class="info-box">
            <h3> Bonjour {{ $user->prenoms }} {{ $user->nom }},</h3>
            <p>Nous accusons réception de votre réclamation soumise via la plateforme e-Services CPPF.</p>
        </div>

        <div class="info-box">
            <h3>📋 Détails de votre réclamation</h3>
            <p><strong>Numéro de référence :</strong> <span class="badge badge-success">{{ $reclamation->numero_reclamation }}</span></p>
            <p><strong>Type :</strong> {{ $typeReclamation['nom'] ?? $reclamation->type_reclamation }}</p>
            @if($reclamation->sujet_personnalise)
                <p><strong>Sujet :</strong> {{ $reclamation->sujet_personnalise }}</p>
            @endif
            <p><strong>Priorité :</strong> <span class="badge badge-priority">{{ strtoupper($prioriteInfo['nom'] ?? $reclamation->priorite) }}</span></p>
            <p><strong>Date de soumission :</strong> {{ $reclamation->date_soumission->format('d/m/Y à H:i') }}</p>
            <p><strong>Statut actuel :</strong> <span class="badge badge-success">En attente de traitement</span></p>
        </div>

        <div class="attachment-notice">
            <h3>📎 Accusé de réception joint</h3>
            <p><strong>Un accusé de réception officiel au format PDF est joint à cet email.</strong></p>
            <p>Ce document contient tous les détails de votre réclamation et fait foi de votre demande auprès de la CPPF.</p>
            <p style="font-size: 0.9em; color: #666; margin-top: 10px;">
                💡 <em>Conservez précieusement ce document pour vos dossiers personnels.</em>
            </p>
        </div>

        @if($reclamation->documents && count($reclamation->documents) > 0)
            <div class="info-box">
                <h3>📄 Documents que vous avez joints</h3>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    @foreach($reclamation->documents as $document)
                        <li>{{ $document['nom_original'] }} ({{ number_format($document['taille'] / 1024, 1) }} KB)</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="important-box">
            <h3> Prochaines étapes</h3>
            <p><strong>1. Accusé de traitement :</strong> Un agent va examiner votre réclamation sous 48-72h ouvrables.</p>
            <p><strong>2. Suivi en temps réel :</strong> Connectez-vous à votre espace e-Services pour suivre l'évolution.</p>
            <p><strong>3. Réponse personnalisée :</strong> Vous recevrez une réponse détaillée par email.</p>
        </div>

        <div style="text-align: center; margin: 25px 0;">
            <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/reclamations" class="button">
                🔗 Suivre ma réclamation en ligne
            </a>
        </div>

        <div class="info-box">
            <h3>📞 Besoin d'aide ?</h3>
            <p>Si vous avez des questions concernant votre réclamation, vous pouvez :</p>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Consulter votre espace e-Services en ligne</li>
                <li>Nous contacter par email : <strong>contact@cppf.ga</strong></li>
                <li>Appeler notre service client : <strong>+241 01 XX XX XX</strong></li>
            </ul>
            <p style="font-size: 0.9em; color: #666; margin-top: 15px;">
                <strong>Référence à mentionner :</strong> {{ $reclamation->numero_reclamation }}
            </p>
        </div>

        <div class="footer">
            <p><strong>Merci de votre confiance</strong></p>
            <p style="font-size: 0.9em;">
                <strong>CAISSE DE PENSION DU PERSONNEL DE LA FONCTION PUBLIQUE</strong><br>
                e-Services | Libreville, Gabon<br>
                Email: contact@cppf.ga | Web: www.cppf.ga
            </p>
            <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">
            <p style="font-size: 0.8em; color: #999;">
                Cet email a été généré automatiquement. Merci de ne pas y répondre directement.<br>
                Pour toute correspondance, utilisez les coordonnées mentionnées ci-dessus.
            </p>
        </div>
    </div>
</body>
</html>