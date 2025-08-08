
{{-- resources/views/emails/reclamation-created.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Réclamation</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8fafc;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 10px;
        }
        .title {
            font-size: 20px;
            color: #1f2937;
            margin: 0;
        }
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-urgent { background: #fee2e2; color: #dc2626; }
        .badge-haute { background: #fef3c7; color: #d97706; }
        .badge-normale { background: #dbeafe; color: #2563eb; }
        .badge-basse { background: #dcfce7; color: #16a34a; }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
        }
        .info-item {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
        }
        .info-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 14px;
            color: #1f2937;
            font-weight: 500;
        }
        .description-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            white-space: pre-wrap;
            line-height: 1.6;
        }
        .documents {
            margin: 20px 0;
        }
        .document-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #f3f4f6;
            border-radius: 6px;
            margin-bottom: 8px;
        }
        .document-icon {
            font-size: 18px;
            margin-right: 10px;
        }
        .contact-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
        }
        @media (max-width: 600px) {
            .info-grid { grid-template-columns: 1fr; }
            .container { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🏛️ CPPF</div>
            <h1 class="title">Nouvelle Réclamation Reçue</h1>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Numéro</div>
                <div class="info-value">{{ $reclamation->numero_reclamation }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Type</div>
                <div class="info-value">{{ $typeReclamation['nom'] ?? $reclamation->type_reclamation }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Priorité</div>
                <div class="info-value">
                    <span class="badge badge-{{ $reclamation->priorite }}">
                        {{ $prioriteInfo['nom'] ?? $reclamation->priorite }}
                    </span>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Date</div>
                <div class="info-value">{{ $reclamation->date_soumission->format('d/m/Y à H:i') }}</div>
            </div>
        </div>

        @if($reclamation->sujet_personnalise)
        <div class="info-item" style="margin: 20px 0;">
            <div class="info-label">Sujet personnalisé</div>
            <div class="info-value">{{ $reclamation->sujet_personnalise }}</div>
        </div>
        @endif

        <h3>📝 Description</h3>
        <div class="description-box">{{ $reclamation->description }}</div>

        @if($reclamation->documents && count($reclamation->documents) > 0)
        <div class="documents">
            <h3>📎 Documents joints ({{ count($reclamation->documents) }})</h3>
            @foreach($reclamation->documents as $document)
            <div class="document-item">
                <span class="document-icon">📄</span>
                <div>
                    <strong>{{ $document['nom_original'] }}</strong><br>
                    <small>{{ number_format($document['taille'] / 1024, 1) }} KB • {{ strtoupper($document['type']) }}</small>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <div class="contact-info">
            <h3 style="margin-top: 0; color: #1e40af;">👤 Informations du demandeur</h3>
            <div class="info-grid">
                <div>
                    <div class="info-label">Nom complet</div>
                    <div class="info-value">{{ $user->prenoms }} {{ $user->nom }}</div>
                </div>
                <div>
                    <div class="info-label">Email</div>
                    <div class="info-value">{{ $user->email }}</div>
                </div>
                @if($user->telephone)
                <div>
                    <div class="info-label">Téléphone</div>
                    <div class="info-value">{{ $user->telephone }}</div>
                </div>
                @endif
                <div>
                    <div class="info-label">Type de compte</div>
                    <div class="info-value">{{ $reclamation->user_type === 'agent' ? 'Agent actif' : 'Retraité' }}</div>
                </div>
            </div>
            
            @if($reclamation->user_type === 'agent')
                <div class="info-grid" style="margin-top: 15px;">
                    <div>
                        <div class="info-label">Matricule</div>
                        <div class="info-value">{{ $user->matricule_solde ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="info-label">Direction</div>
                        <div class="info-value">{{ $user->direction ?? 'N/A' }}</div>
                    </div>
                </div>
            @else
                <div class="info-grid" style="margin-top: 15px;">
                    <div>
                        <div class="info-label">N° Pension</div>
                        <div class="info-value">{{ $user->numero_pension ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="info-label">Pension mensuelle</div>
                        <div class="info-value">{{ number_format($user->montant_pension ?? 0, 0, ',', ' ') }} FCFA</div>
                    </div>
                </div>
            @endif
        </div>

        <div class="footer">
            <p>📧 Email reçu le {{ now()->format('d/m/Y à H:i') }}</p>
            <p>Cette réclamation nécessite votre attention et un suivi approprié.</p>
            <p style="margin-top: 20px; font-style: italic;">
                CPPF - Caisse de Pension des Fonctionnaires<br>
                Système de gestion des réclamations
            </p>
        </div>
    </div>
</body>
</html>