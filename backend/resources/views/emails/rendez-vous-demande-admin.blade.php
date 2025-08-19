{{-- resources/views/emails/rendez-vous-demande-admin.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle demande de rendez-vous</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
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
        .demande-details {
            background-color: #edf2f7;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .motif-badge {
            display: inline-block;
            background-color: #667eea;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        .status-badge {
            display: inline-block;
            background-color: #f6ad55;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        .commentaires {
            background-color: #f0fff4;
            border: 1px solid #9ae6b4;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        .actions {
            background-color: #fff5f5;
            border: 1px solid #feb2b2;
            border-radius: 8px;
            padding: 20px;
            margin-top: 25px;
        }
        .actions h3 {
            color: #c53030;
            margin-top: 0;
        }
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
        <!-- En-tête -->
        <div class="email-header">
            <h1>📅 Nouvelle Demande de Rendez-vous</h1>
            <p class="subtitle">CPPF e-Services - Administration</p>
        </div>

        <!-- Corps du message -->
        <div class="email-body">
            <!-- Informations de la demande -->
            <div class="section">
                <h2 class="section-title">📋 Détails de la Demande</h2>
                <div class="demande-details">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Numéro de demande</div>
                            <div class="info-value"><strong>{{ $demande->numero_demande }}</strong></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Date et heure demandées</div>
                            <div class="info-value">{{ $demande->date_heure_formatee }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Motif</div>
                            <div class="info-value">
                                <span class="motif-badge">
                                    {{ $demande->motif_info['icon'] ?? '📋' }} {{ $demande->motif_complet }}
                                </span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Statut</div>
                            <div class="info-value">
                                <span class="status-badge">{{ $demande->statut_info['nom'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations du demandeur -->
            <div class="section">
                <h2 class="section-title">👤 Informations du Demandeur</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Nom complet</div>
                        <div class="info-value">{{ $user->prenoms }} {{ $user->nom }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Type de compte</div>
                        <div class="info-value">
                            {{ $demande->user_type === 'agent' ? '👔 Agent actif' : '🏖️ Retraité' }}
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value">{{ $demande->user_email }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Téléphone</div>
                        <div class="info-value">{{ $demande->user_telephone ?? 'Non renseigné' }}</div>
                    </div>
                </div>

                @if($demande->user_type === 'agent')
                    <div class="info-grid" style="margin-top: 15px;">
                        <div class="info-item">
                            <div class="info-label">Matricule</div>
                            <div class="info-value">{{ $user->matricule_solde ?? 'N/A' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Direction</div>
                            <div class="info-value">{{ $user->direction ?? 'N/A' }}</div>
                        </div>
                    </div>
                @else
                    <div class="info-grid" style="margin-top: 15px;">
                        <div class="info-item">
                            <div class="info-label">Numéro de pension</div>
                            <div class="info-value">{{ $user->numero_pension ?? 'N/A' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Date de retraite</div>
                            <div class="info-value">{{ $user->date_retraite ? \Carbon\Carbon::parse($user->date_retraite)->format('d/m/Y') : 'N/A' }}</div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Commentaires -->
            @if($demande->commentaires)
            <div class="section">
                <h2 class="section-title">💬 Commentaires</h2>
                <div class="commentaires">
                    <p><strong>Message du demandeur :</strong></p>
                    <p>{{ $demande->commentaires }}</p>
                </div>
            </div>
            @endif

            <!-- Actions à effectuer -->
            <div class="actions">
                <h3>🎯 Actions Requises</h3>
                <p><strong>Cette demande nécessite votre attention :</strong></p>
                <ul>
                    <li>Examiner la disponibilité du créneau demandé</li>
                    <li>Vérifier la nature de la demande</li>
                    <li>Répondre par email à <strong>{{ $demande->user_email }}</strong></li>
                    <li>Mettre à jour le statut si nécessaire</li>
                </ul>
                
                <p><strong>⚠️ Important :</strong> Veuillez traiter cette demande dans les meilleurs délais et répondre directement au demandeur par email.</p>
            </div>

            <!-- Informations de soumission -->
            <div class="section">
                <h2 class="section-title">🕒 Informations de Soumission</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Date de soumission</div>
                        <div class="info-value">{{ $demande->date_soumission->format('d/m/Y à H:i:s') }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Temps écoulé</div>
                        <div class="info-value">{{ $demande->temps_ecoule }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pied de page -->
        <div class="email-footer">
            <p><strong>CPPF e-Services</strong> - Système de Gestion des Rendez-vous</p>
            <p>Caisse de Pension de la Fonction Publique du Gabon</p>
            <p>📧 <a href="mailto:{{ env('MAIL_FROM_ADDRESS') }}">{{ env('MAIL_FROM_ADDRESS') }}</a></p>
            <p style="margin-top: 15px; font-size: 12px; opacity: 0.8;">
                Cet email a été généré automatiquement. Veuillez ne pas répondre à cet email.
            </p>
        </div>
    </div>
</body>
</html>