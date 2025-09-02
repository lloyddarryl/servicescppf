<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rappel Rendez-vous - CPPF</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .reminder-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 25px;
            color: #374151;
        }
        .rdv-card {
            background: linear-gradient(135deg, #fef3c7, #fef9e7);
            border-left: 4px solid #f59e0b;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .rdv-title {
            color: #b45309;
            font-weight: 600;
            font-size: 20px;
            margin-bottom: 15px;
        }
        .rdv-detail {
            display: flex;
            align-items: center;
            margin: 10px 0;
            font-size: 16px;
            color: #374151;
        }
        .rdv-detail-icon {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .important-note {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            color: #7f1d1d;
        }
        .actions {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            margin: 0 10px;
        }
        .button-secondary {
            background: #6b7280;
        }
        .footer {
            background: #f9fafb;
            padding: 20px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            border-top: 1px solid #e5e7eb;
        }
        .no-reply {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 10px;
            margin-top: 20px;
            font-size: 14px;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="reminder-icon">🔔</div>
            <h1>Rappel de Rendez-vous</h1>
            <p>Caisse de Pension de la Fonction Publique</p>
        </div>

        <div class="content">
            <div class="greeting">
                Bonjour {{ $user->prenoms }} {{ $user->nom }},
            </div>

            <p>Nous vous rappelons que vous avez un <strong>rendez-vous prévu demain</strong> avec nos services.</p>

            <div class="rdv-card">
                <div class="rdv-title">📅 Détails de votre rendez-vous</div>
                
                <div class="rdv-detail">
                    <span class="rdv-detail-icon">🆔</span>
                    <strong>Numéro de demande :</strong> {{ $demande->numero_demande }}
                </div>

                <div class="rdv-detail">
                    <span class="rdv-detail-icon">📅</span>
                    <strong>Date et heure :</strong> 
                    {{ $demande->date_rdv_confirme->format('l d F Y à H:i') }}
                </div>

                @if($demande->lieu_rdv)
                <div class="rdv-detail">
                    <span class="rdv-detail-icon">📍</span>
                    <strong>Lieu :</strong> {{ $demande->lieu_rdv }}
                </div>
                @endif

                <div class="rdv-detail">
                    <span class="rdv-detail-icon">📋</span>
                    <strong>Motif :</strong> {{ $demande->motif_complet }}
                </div>

                @if($demande->commentaires)
                <div class="rdv-detail">
                    <span class="rdv-detail-icon">💬</span>
                    <strong>Vos commentaires :</strong> {{ $demande->commentaires }}
                </div>
                @endif
            </div>

            <div class="important-note">
                <strong>⚠️ Important :</strong>
                <ul>
                    <li>Merci d'arriver <strong>15 minutes avant</strong> l'heure prévue</li>
                    <li>N'oubliez pas d'apporter une <strong>pièce d'identité valide</strong></li>
                    <li>Si vous ne pouvez pas vous présenter, veuillez nous en informer au plus tôt</li>
                </ul>
            </div>

            <div class="actions">
                <a href="{{ env('APP_URL') }}/actifs/rendez-vous" class="button">
                    Voir mes rendez-vous
                </a>
                <a href="{{ env('APP_URL') }}/dashboard" class="button button-secondary">
                    Mon tableau de bord
                </a>
            </div>

            <p>En cas de problème ou pour toute question, vous pouvez :</p>
            <ul>
                <li>📱 Nous appeler au : <strong>+241 XX XX XX XX</strong></li>
                <li>✉️ Nous écrire à : <strong>contact@cppf.ga</strong></li>
                <li>🌐 Consulter votre espace personnel sur notre site</li>
            </ul>

            <p>Nous vous remercions de votre confiance et restons à votre disposition.</p>

            <div class="no-reply">
                <strong>📧 Ceci est un message automatique</strong><br>
                Merci de ne pas répondre à cet email. Pour toute question, utilisez nos canaux de contact habituels.
            </div>
        </div>

        <div class="footer">
            <p><strong>Caisse de Pension de la Fonction Publique du Gabon</strong></p>
            <p>Libreville, Gabon | {{ env('APP_URL') }}</p>
            <p><small>Ce message a été envoyé le {{ now()->format('d/m/Y à H:i') }}</small></p>
        </div>
    </div>
</body>
</html>