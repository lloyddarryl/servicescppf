<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau(x) Document(s) Déposé(s) - CPPF</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8fafc;
        }
        
        .email-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 16px;
        }
        
        .content {
            padding: 30px;
        }
        
        .alert {
            background: #dbeafe;
            border: 1px solid #93c5fd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
        }
        
        .alert-icon {
            font-size: 20px;
            margin-right: 10px;
        }
        
        .retraite-info {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .retraite-info h3 {
            color: #1e3a8a;
            margin-top: 0;
            font-size: 18px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
        }
        
        .info-label {
            font-weight: 600;
            color: #4b5563;
            min-width: 120px;
        }
        
        .info-value {
            color: #1f2937;
            font-weight: 500;
        }
        
        .documents-section h3 {
            color: #1e3a8a;
            font-size: 18px;
            margin-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }
        
        .document-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: #fafafa;
            transition: all 0.2s ease;
        }
        
        .document-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .document-icon {
            font-size: 24px;
            margin-right: 12px;
        }
        
        .document-name {
            font-weight: 600;
            color: #1f2937;
            font-size: 16px;
        }
        
        .document-type {
            background: #3b82f6;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            margin-left: auto;
        }
        
        .document-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .detail-item {
            font-size: 14px;
        }
        
        .detail-label {
            color: #6b7280;
            font-weight: 500;
        }
        
        .detail-value {
            color: #1f2937;
            font-weight: 600;
        }
        
        .certificat-vie {
            border-left: 4px solid #10b981;
            background: #f0fdf4;
        }
        
        .certificat-vie .document-type {
            background: #10b981;
        }
        
        .autre-document {
            border-left: 4px solid #f59e0b;
        }
        
        .autre-document .document-type {
            background: #f59e0b;
        }
        
        .stats-section {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 500;
        }
        
        .footer {
            background: #f1f5f9;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        
        .footer p {
            margin: 5px 0;
            color: #6b7280;
            font-size: 14px;
        }
        
        .footer .timestamp {
            font-weight: 600;
            color: #4b5563;
        }
        
        .expiration-warning {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            color: #92400e;
            border-radius: 6px;
            padding: 10px;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .expiration-info {
            background: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
            border-radius: 6px;
            padding: 10px;
            font-size: 14px;
            margin-top: 10px;
        }

        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            
            .content {
                padding: 20px;
            }
            
            .info-grid,
            .document-details,
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>CPPF - e-Services</h1>
            <p>Nouveau(x) Document(s) Déposé(s)</p>
        </div>
        
        <div class="content">
            <div class="alert">
                <span class="alert-icon">📋</span>
                <strong>{{ count($documents) === 1 ? 'Un nouveau document a' : count($documents) . ' nouveaux documents ont' }} été déposé{{ count($documents) > 1 ? 's' : '' }}</strong> 
                par un retraité sur la plateforme e-Services.
            </div>
            
            <div class="retraite-info">
                <h3>👤 Informations du Retraité</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Nom complet :</span>
                        <span class="info-value">{{ $retraite['nom_complet'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">N° Pension :</span>
                        <span class="info-value">{{ $retraite['numero_pension'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email :</span>
                        <span class="info-value">{{ $retraite['email'] ?? 'Non renseigné' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Téléphone :</span>
                        <span class="info-value">{{ $retraite['telephone'] ?? 'Non renseigné' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Situation :</span>
                        <span class="info-value">{{ $retraite['situation_matrimoniale'] ?? 'Non renseigné' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Date retraite :</span>
                        <span class="info-value">{{ $retraite['date_retraite'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Pension :</span>
                        <span class="info-value">{{ $retraite['montant_pension'] }}</span>
                    </div>
                </div>
            </div>
            
            <div class="documents-section">
                <h3>📄 Document{{ count($documents) > 1 ? 's' : '' }} déposé{{ count($documents) > 1 ? 's' : '' }}</h3>
                
                @foreach($documents as $document)
                    <div class="document-card {{ $document['type'] === 'Certificat de Vie' ? 'certificat-vie' : 'autre-document' }}">
                        <div class="document-header">
                            <span class="document-icon">
                                @if($document['type'] === 'Certificat de Vie')
                                    📋
                                @else
                                    📄
                                @endif
                            </span>
                            <span class="document-name">{{ $document['nom_original'] }}</span>
                            <span class="document-type">{{ $document['type'] }}</span>
                        </div>
                        
                        <div class="document-details">
                            <div class="detail-item">
                                <div class="detail-label">Taille :</div>
                                <div class="detail-value">{{ $document['taille'] }}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Déposé le :</div>
                                <div class="detail-value">{{ $document['date_depot'] }}</div>
                            </div>
                            @if($document['autorite_emission'])
                                <div class="detail-item">
                                    <div class="detail-label">Autorité :</div>
                                    <div class="detail-value">{{ $document['autorite_emission'] }}</div>
                                </div>
                            @endif
                            @if($document['description'])
                                <div class="detail-item">
                                    <div class="detail-label">Description :</div>
                                    <div class="detail-value">{{ $document['description'] }}</div>
                                </div>
                            @endif
                        </div>
                        
                        @if($document['date_expiration'])
                            @if(\Carbon\Carbon::parse($document['date_expiration'])->isPast())
                                <div class="expiration-warning">
                                    ⚠️ <strong>Attention :</strong> Ce certificat a expiré le {{ $document['date_expiration'] }}
                                </div>
                            @elseif(\Carbon\Carbon::parse($document['date_expiration'])->diffInDays() <= 30)
                                <div class="expiration-warning">
                                    ⚠️ <strong>Attention :</strong> Ce certificat expire le {{ $document['date_expiration'] }} 
                                    (dans {{ \Carbon\Carbon::parse($document['date_expiration'])->diffInDays() }} jours)
                                </div>
                            @else
                                <div class="expiration-info">
                                    ✅ <strong>Valide jusqu'au :</strong> {{ $document['date_expiration'] }}
                                </div>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>
            
            <div class="stats-section">
                <h3>📊 Statistiques du Retraité</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value">{{ $statistiques['total_documents'] }}</div>
                        <div class="stat-label">Total Documents</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">{{ $statistiques['certificats_vie'] }}</div>
                        <div class="stat-label">Certificats de Vie</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">{{ $statistiques['autres_documents'] }}</div>
                        <div class="stat-label">Autres Documents</div>
                    </div>
                    @if($statistiques['documents_expires'] > 0)
                        <div class="stat-item">
                            <div class="stat-value" style="color: #ef4444;">{{ $statistiques['documents_expires'] }}</div>
                            <div class="stat-label">Documents Expirés</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>CPPF - Caisse des Pensions et Prestations Familiales des agents de l'Etat.</strong></p>
            <p>Plateforme e-Services</p>
            <p class="timestamp">Email généré automatiquement le {{ $timestamp }}</p>
        </div>
    </div>
</body>
</html>