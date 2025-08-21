{{-- resources/views/emails/rendez-vous-reponse-user.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réponse à votre demande de rendez-vous</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .email-header {
            padding: 30px;
            text-align: center;
            color: white;
        }
        .header-accepte { background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); }
        .header-refuse { background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%); }
        .header-reporte { background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%); }
        .header-annule { background: linear-gradient(135deg, #a0aec0 0%, #718096 100%); }
        
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-header .subtitle {
            margin: 8px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .status-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .email-body {
            padding: 30px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .info-item {
            background-color: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .info-label {
            font-weight: 600;
            color: #4a5568;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 16px;
            color: #2d3748;
        }
        .reponse-admin {
            background-color: #edf2f7;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .rdv-confirme {
            background-color: #f0fff4;
            border: 2px solid #68d391;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .rdv-confirme h3 {
            color: #38a169;
            margin-top: 0;
        }
        .next-steps {
            background-color: #fff5f5;
            border: 1px solid #feb2b2;
            border-radius: 8px;
            padding: 20px;
            margin-top: 25px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-accepte { background-color: #c6f6d5; color: #22543d; }
        .badge-refuse { background-color: #fed7d7; color: #742a2a; }
        .badge-reporte { background-color: #feebc8; color: #7c2d12; }
        .badge-annule { background-color: #e2e8f0; color: #2d3748; }
        
        .email-footer {
            background-color: #2d3748;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 14px;
        }
        .email-footer a {
            color: #90cdf4;
            text-decoration: none;
        }
        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- En-tête dynamique selon le statut -->
        <div class="email-header header-{{ $demande->statut }}">
            <div class="status-icon">
                @if($demande->statut === 'accepte')
                    ✅
                @elseif($demande->statut === 'refuse')
                    ❌
                @elseif($demande->statut === 'reporte')
                    📅
                @elseif($demande->statut === 'annule')
                    🚫
                @endif
            </div>
            <h1>
                @if($demande->statut === 'accepte')
                    Rendez-vous Confirmé
                @elseif($demande->statut === 'refuse')
                    Demande Refusée
                @elseif($demande->statut === 'reporte')
                    Rendez-vous Reporté
                @elseif($demande->statut === 'annule')
                    Rendez-vous Annulé
                @endif
            </h1>
            <p class="subtitle">{{ $demande->numero_demande }}</p>
        </div>

        <!-- Corps du message -->
        <div class="email-body">
            <!-- Salutation personnalisée -->
            <div class="section">
                <p style="font-size: 16px; margin-bottom: 20px;">
                    Bonjour <strong>{{ $user->prenoms }} {{ $user->nom }}</strong>,
                </p>
                
                <p style="font-size: 16px;">
                    @if($demande->statut === 'accepte')
                        Nous avons le plaisir de vous informer que votre demande de rendez-vous a été <strong style="color: #38a169;">acceptée</strong>.
                    @elseif($demande->statut === 'refuse')
                        Nous vous informons que votre demande de rendez-vous a été <strong style="color: #e53e3e;">refusée</strong>.
                    @elseif($demande->statut === 'reporte')
                        Nous vous informons que votre rendez-vous a été <strong style="color: #dd6b20;">reporté</strong>.
                    @elseif($demande->statut === 'annule')
                        Nous vous informons que votre rendez-vous a été <strong style="color: #718096;">annulé</strong>.
                    @endif
                </p>
            </div>

            <!-- Détails de la demande originale -->
            <div class="section">
                <h2 class="section-title">📋 Votre Demande</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Numéro de demande</div>
                        <div class="info-value">{{ $demande->numero_demande }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Date demandée</div>
                        <div class="info-value">{{ $demande->date_heure_formatee }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Motif</div>
                        <div class="info-value">{{ $demande->motif_complet }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Statut</div>
                        <div class="info-value">
                            <span class="status-badge badge-{{ $demande->statut }}">
                                {{ $demande->statut_info['nom'] }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rendez-vous confirmé (si accepté) -->
            @if($demande->statut === 'accepte' && $demande->date_rdv_confirme)
            <div class="rdv-confirme">
                <h3>🎯 Détails de votre Rendez-vous</h3>
                <div class="info-grid">
                    <div class="info-item" style="border-left-color: #38a169;">
                        <div class="info-label">Date et heure confirmées</div>
                        <div class="info-value" style="font-size: 18px; font-weight: 600; color: #38a169;">
                            {{ \Carbon\Carbon::parse($demande->date_rdv_confirme)->format('d/m/Y à H:i') }}
                        </div>
                    </div>
                    @if($demande->lieu_rdv)
                    <div class="info-item" style="border-left-color: #38a169;">
                        <div class="info-label">Lieu</div>
                        <div class="info-value" style="font-weight: 600;">
                            {{ $demande->lieu_rdv }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Réponse de l'administration -->
            @if($demande->reponse_admin)
            <div class="section">
                <h2 class="section-title">💬 Message de l'Administration</h2>
                <div class="reponse-admin">
                    <p style="margin: 0; font-style: italic;">{{ $demande->reponse_admin }}</p>
                </div>
            </div>
            @endif

            <!-- Prochaines étapes selon le statut -->
            <div class="next-steps">
                <h3 style="margin-top: 0; color: #c53030;">
                    @if($demande->statut === 'accepte')
                        📝 Prochaines Étapes
                    @elseif($demande->statut === 'refuse')
                        🔄 Que Faire Maintenant ?
                    @elseif($demande->statut === 'reporte')
                        🔄 Prochaines Étapes
                    @elseif($demande->statut === 'annule')
                        🔄 Nouvelle Demande
                    @endif
                </h3>
                
                @if($demande->statut === 'accepte')
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>Notez bien la date et l'heure de votre rendez-vous</li>
                        <li>Préparez les documents nécessaires selon votre motif</li>
                        <li>Arrivez 10 minutes avant l'heure prévue</li>
                        @if($demande->lieu_rdv)
                        <li>Rendez-vous à : <strong>{{ $demande->lieu_rdv }}</strong></li>
                        @endif
                        <li>En cas d'empêchement, contactez-nous au plus tôt</li>
                    </ul>
                @elseif($demande->statut === 'refuse')
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>Vous pouvez soumettre une nouvelle demande avec une autre date</li>
                        <li>Consultez les créneaux disponibles sur votre espace personnel</li>
                        <li>Pour toute question, contactez notre service client</li>
                    </ul>
                @elseif($demande->statut === 'reporte')
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>Une nouvelle date vous sera proposée prochainement</li>
                        <li>Vous recevrez une notification par email</li>
                        <li>Vous pouvez également soumettre une nouvelle demande</li>
                    </ul>
                @elseif($demande->statut === 'annule')
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>Vous pouvez soumettre une nouvelle demande quand vous le souhaitez</li>
                        <li>Consultez votre espace personnel pour voir les créneaux disponibles</li>
                        <li>Nous restons à votre disposition pour tout renseignement</li>
                    </ul>
                @endif
            </div>

            <!-- Informations de contact -->
            <div class="section">
                <h2 class="section-title">📞 Nous Contacter</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value">{{ env('MAIL_FROM_ADDRESS') }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Espace personnel</div>
                        <div class="info-value">
                            <a href="{{ env('FRONTEND_URL', 'http://localhost:3000') }}/dashboard" 
                               style="color: #667eea; text-decoration: none;">
                                Accéder à mon compte
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message de fin -->
            <div style="text-align: center; padding: 20px; background-color: #f7fafc; border-radius: 8px; margin-top: 25px;">
                <p style="margin: 0; font-style: italic; color: #4a5568;">
                    Merci de votre confiance en nos services.
                </p>
                <p style="margin: 5px 0 0 0; font-weight: 600; color: #2d3748;">
                    L'équipe CPPF e-Services
                </p>
            </div>
        </div>

        <!-- Pied de page -->
        <div class="email-footer">
            <p><strong>CPPF e-Services</strong> - Système de Gestion des Rendez-vous</p>
            <p>Caisse des Pensions et Prestations Familiales des agents de l'Etat.</p>
            <p>📧 <a href="mailto:{{ env('MAIL_FROM_ADDRESS') }}">{{ env('MAIL_FROM_ADDRESS') }}</a></p>
            <p style="margin-top: 15px; font-size: 12px; opacity: 0.8;">
                Cet email a été généré automatiquement le {{ now()->format('d/m/Y à H:i') }}.
            </p>
        </div>
    </div>
</body>
</html>