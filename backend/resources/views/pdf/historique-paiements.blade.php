<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Historique complet des versements</title>
    <style>
        @page {
            margin: 15mm;
            @top-right {
                content: "Page " counter(page);
            }
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        
        .header {
            position: relative;
            padding-bottom: 15px;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 15px;
        }
        
        .logo {
            position: absolute;
            top: 15px;
            right: 0;
            width: 80px;
            height: 30px;
        }
        
        .logo img {
            width: 100%;
            height: auto;
        }
        
        .system-info {
            font-size: 10px;
            color: #666;
        }
        
        .system-name {
            font-weight: bold;
            font-size: 12px;
            color: #2c3e50;
        }
        
        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0 10px 0;
            color: #2c3e50;
        }
        
        .retraite-info {
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 20px;
            color: #34495e;
        }
        
        .dossier-info {
            text-align: center;
            font-size: 11px;
            color: #666;
            margin-bottom: 25px;
        }
        
        /* Section d'année */
        .annee-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        
        .annee-header {
            background-color: #34495e;
            color: white;
            padding: 10px;
            margin-bottom: 10px;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
        }
        
        .annee-stats {
            text-align: center;
            background-color: #ecf0f1;
            padding: 8px;
            margin-bottom: 15px;
            font-size: 10px;
            border-left: 4px solid #3498db;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9px;
        }
        
        th {
            background-color: #2c3e50;
            color: white;
            padding: 8px 4px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #2c3e50;
        }
        
        td {
            padding: 6px 4px;
            border: 1px solid #bdc3c7;
            text-align: center;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        tr:nth-child(odd) {
            background-color: #ffffff;
        }
        
        .montant {
            text-align: right;
            font-family: monospace;
            font-weight: bold;
        }
        
        .mode-reglement {
            text-align: left;
            font-size: 8px;
        }
        
        .etat-verse {
            color: #27ae60;
            font-weight: bold;
        }
        
        .etat-en-attente {
            color: #f39c12;
            font-weight: bold;
        }
        
        .etat-rejete {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .footer {
            position: fixed;
            bottom: 10mm;
            left: 0;
            width: 100%;
            border-top: 1px solid #ddd;
            padding-top: 8px;
            font-size: 10px;
            color: #666;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .summary {
            background-color: #ecf0f1;
            padding: 10px;
            margin: 20px 0;
            border-left: 4px solid #3498db;
        }
        
        .summary-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        
        .grand-total {
            background-color: #2c3e50;
            color: white;
            padding: 15px;
            margin-top: 30px;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
        }
    </style>
</head>
<body>
    @php
        // Organiser les paiements par année (du plus récent au moins récent)
        $paiementsParAnnee = $paiements->groupBy(function($paiement) {
            return $paiement->date_paiement->year;
        })->sortKeysDesc();
        
        // Calculer les totaux généraux
        $totalGeneral = $paiements->sum('montant_net');
        $nombreTotalVersements = $paiements->count();
        $nombreTotalVerses = $paiements->where('etat_paiement', 'verse')->count();
    @endphp

    <!-- En-tête -->
    <div class="header">
        <div class="logo">
            <img src="{{ public_path('images/cppf.png') }}" alt="Logo CPPF" style="height: 70px;">
        </div>
    </div>

    <!-- Titre -->
    <h1 class="title">HISTORIQUE COMPLET DES VERSEMENTS</h1>

    <!-- Informations du retraité -->
    <div class="retraite-info">
        {{ $retraite->titre_civilite ?? 'M.' }} {{ $retraite->prenoms }} {{ $retraite->nom }}
    </div>
    
    <div class="dossier-info">
        Dossier de pension n° {{ $retraite->numero_pension ?? 'N/A' }}
    </div>

    <!-- Résumé général -->
    <div class="summary">
        <div class="summary-title">Résumé général :</div>
        <div>
            <strong>Période couverte :</strong> {{ $paiementsParAnnee->keys()->min() }} - {{ $paiementsParAnnee->keys()->max() }}<br>
            <strong>Total versements :</strong> {{ $nombreTotalVersements }}<br>
            <strong>Versements effectués :</strong> {{ $nombreTotalVerses }}<br>
            <strong>Montant total :</strong> {{ number_format($totalGeneral, 0, ',', ' ') }} FCFA
        </div>
    </div>

    <!-- Affichage par année (du plus récent au moins récent) -->
    @foreach($paiementsParAnnee as $annee => $paiementsAnnee)
        @php
            $totalAnnee = $paiementsAnnee->sum('montant_net');
            $nombreVersementsAnnee = $paiementsAnnee->count();
            $nombreVersesAnnee = $paiementsAnnee->where('etat_paiement', 'verse')->count();
        @endphp

        <div class="annee-section">
            <!-- En-tête de l'année -->
            <div class="annee-header">
                ANNÉE {{ $annee }}
            </div>

            <!-- Statistiques de l'année -->
            <div class="annee-stats">
                <strong>{{ $nombreVersementsAnnee }} versement(s)</strong> | 
                <strong>{{ $nombreVersesAnnee }} effectué(s)</strong> | 
                <strong>Total : {{ number_format($totalAnnee, 0, ',', ' ') }} FCFA</strong>
            </div>

            <!-- Tableau des paiements de l'année -->
            <table>
                <thead>
                    <tr>
                        <th style="width: 10%;">Date</th>
                        <th style="width: 12%;">N° titre</th>
                        <th style="width: 8%;">État</th>
                        <th style="width: 8%;">Régime</th>
                        <th style="width: 12%;">Disponibilité</th>
                        <th style="width: 18%;">Mode de règlement</th>
                        <th style="width: 12%;">Montant net</th>
                        <th style="width: 10%;">Référence</th>
                        <th style="width: 10%;">Observations</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($paiementsAnnee->sortByDesc('date_paiement') as $paiement)
                        <tr>
                            <td>{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                            <td><strong>{{ $paiement->numero_titre }}</strong></td>
                            <td class="etat-{{ str_replace('_', '-', $paiement->etat_paiement) }}">
                                {{ ucfirst(str_replace('_', ' ', $paiement->etat_paiement)) }}
                            </td>
                            <td>{{ $paiement->regime }}</td>
                            <td>{{ $paiement->disponibilite }}</td>
                            <td class="mode-reglement">{{ $paiement->mode_reglement }}</td>
                            <td class="montant">{{ number_format($paiement->montant_net, 0, ',', ' ') }}</td>
                            <td style="font-size: 8px;">{{ $paiement->reference_bancaire ?: '-' }}</td>
                            <td style="font-size: 8px;">{{ $paiement->observations ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Totaux de l'année -->
            @if($nombreVersementsAnnee > 1)
            <div style="text-align: right; margin-bottom: 20px; font-weight: bold; background: #f8f9fa; padding: 8px; border: 1px solid #dee2e6;">
                Sous-total {{ $annee }} : {{ number_format($totalAnnee, 0, ',', ' ') }} FCFA
            </div>
            @endif
        </div>

        <!-- Saut de page après chaque année (sauf la dernière) -->
        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

    <!-- Total général final -->
    <div class="grand-total">
        TOTAL GÉNÉRAL : {{ number_format($totalGeneral, 0, ',', ' ') }} FCFA
        <br>
        {{ $nombreTotalVersements }} versements sur {{ $paiementsParAnnee->keys()->count() }} année(s)
        ({{ $paiementsParAnnee->keys()->min() }} - {{ $paiementsParAnnee->keys()->max() }})
    </div>

    <!-- Pied de page -->
    <div class="footer">
        <div class="footer-content">
            <span>Édité le : {{ now()->format('d/m/Y à H:i') }}</span>
            <span>SYSTÈME AUTOMATIQUE - CPPF</span>
            <span>Document complet</span>
        </div>
    </div>
</body>
</html>