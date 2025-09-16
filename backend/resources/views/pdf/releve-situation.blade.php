<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relevé de Situation Individuelle</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            margin: 0;
            padding: 15px;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 10px;
        }
        
        .header h1 {
            color: #1e40af;
            font-size: 14px;
            margin: 0 0 5px 0;
            font-weight: bold;
        }
        
        .header p {
            margin: 2px 0;
            font-size: 9px;
            color: #666;
        }
        
        .logo {
            float: right;
            margin-top: -60px;
        }
        
        .info-generale {
            background-color: #f8fafc;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
        }
        
        .info-row {
            display: inline-block;
            width: 48%;
            margin: 3px 0;
            vertical-align: top;
        }
        
        .info-row strong {
            color: #1e40af;
            font-weight: bold;
        }
        
        .section-title {
            background-color: #1e40af;
            color: white;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: bold;
            margin: 20px 0 10px 0;
            border-radius: 3px;
        }
        
        .cotisations-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 10px;
        }
        
        .cotisations-table th {
            background-color: #e5e7eb;
            border: 1px solid #d1d5db;
            padding: 6px 4px;
            text-align: center;
            font-weight: bold;
            font-size: 9px;
        }
        
        .cotisations-table td {
            border: 1px solid #d1d5db;
            padding: 5px 4px;
            text-align: center;
            font-size: 9px;
        }
        
        .cotisations-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        .total-row {
            background-color: #dbeafe !important;
            font-weight: bold;
        }
        
        .resume {
            background-color: #f0f9ff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #1e40af;
        }
        
        .resume h3 {
            color: #1e40af;
            margin: 0 0 10px 0;
            font-size: 13px;
        }
        
        .resume-item {
            margin: 5px 0;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
        }
        
        .signature-box {
            border: 1px solid #d1d5db;
            width: 200px;
            height: 80px;
            float: right;
            text-align: center;
            padding: 10px;
            margin-top: 10px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .status-radie {
            color: #dc2626;
            font-weight: bold;
        }
        
        .status-actif {
            color: #059669;
            font-weight: bold;
        }
        
        @media print {
            body { margin: 0; }
            .page-break { page-break-before: always; }
        }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        <h1>Caisse des Pensions et des Prestations Familiales des Agents de l'Etat</h1>
        <p>Solidaires aujourd'hui, pour un Avenir Prospère.</p>
        <p>BP: 3932 - Tel: +241 04 62 52 19 / 01 73 07 65</p>
        <p>E-Mail : contact@cppf.ga - Site : www.cppf.ga</p>
        
        <div class="logo">
            <img src="{{ public_path('images/cppf.png') }}" alt="Logo CPPF" style="height: 70px;">
        </div>
        
        <p style="margin-top: 10px; font-size: 10px;">
            Édité le {{ $date_generation }} par: {{ $operateur ?? 'Système' }}
        </p>
    </div>
    
    <!-- Titre du document -->
    <div style="text-align: center; margin: 20px 0;">
        <h2 style="color: #1e40af; margin: 0; font-size: 16px;">RELEVÉ DE SITUATION INDIVIDUELLE</h2>
    </div>
    
    <!-- Informations générales de l'agent -->
    <div class="info-generale">
        <div class="info-row">
            <strong>Nom et Prénom:</strong> {{ $agent['nom_complet'] }}
        </div>
        <div class="info-row">
            <strong>Numéro d'Affiliation:</strong> {{ $agent['num_affiliation'] }}
        </div>
        <div class="info-row">
            <strong>Matricule solde:</strong> {{ $agent['matricule_solde'] }}
        </div>
        <div class="info-row">
            <strong>Situation matrimoniale:</strong> {{ strtoupper($agent['situation_matrimoniale'] ?? 'NON PRÉCISÉE') }}
        </div>
        <div class="info-row">
            <strong>Sexe:</strong> {{ $agent['sexe'] }}
        </div>
        <div class="info-row">
            <strong>Né(e) le:</strong> {{ $agent['date_naissance'] }}
        </div>
        <div class="info-row">
            <strong>Recruté le:</strong> {{ $agent['date_recrutement'] }}
        </div>
        <div class="info-row">
            <strong>Statut:</strong> 
            <span class="{{ $agent['statut'] === 'RADIÉ' ? 'status-radie' : 'status-actif' }}">
                {{ $agent['statut'] }}
            </span>
        </div>
        @if($agent['date_radiation'])
        <div class="info-row">
            <strong>Radié le:</strong> {{ $agent['date_radiation'] }}
        </div>
        @endif
        <div style="clear: both; margin-top: 10px;">
            <strong>Relevé de situation à date:</strong> {{ $date_generation }}
        </div>
    </div>
    
    <!-- Tableau des cotisations -->
    <div class="section-title">DÉCOMPTE CPPF - Régime général</div>
    
    <table class="cotisations-table">
        <thead>
            <tr>
                <th>Période</th>
                <th>Position</th>
                <th>Établissement</th>
                <th>Corps</th>
                <th>Grade</th>
                <th>Indice</th>
                <th>Retenue</th>
                <th>Durée</th>
            </tr>
        </thead>
        <tbody>
            @foreach($carrieres as $carriere)
            <tr>
                <td>{{ $carriere['periode'] }}</td>
                <td>{{ $carriere['position'] }}</td>
                <td style="font-size: 8px;">{{ $carriere['etablissement'] }}</td>
                <td style="font-size: 8px;">{{ $carriere['corps'] }}</td>
                <td>{{ $carriere['grade'] }}</td>
                <td>{{ $carriere['indice'] }}</td>
                <td>{{ $carriere['retenue'] }}</td>
                <td>{{ $carriere['duree'] }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="6" style="text-align: right; font-weight: bold;">TOTAL</td>
                <td style="font-weight: bold;">
                    {{ isset($statistiques['total_cotisations']) ? number_format($statistiques['total_cotisations'], 0, ',', ' ') : '0' }}
                </td>
                <td style="font-weight: bold;">
                    {{ isset($statistiques['duree_totale_mois']) ? $statistiques['duree_totale_mois'] . ' mois' : '0 mois' }}
                </td>
            </tr>
        </tbody>
    </table>
    
    <!-- Section périodes antérieures (si applicable) -->
    <div class="section-title">
        PÉRIODE(S) ANTÉRIEURE(S) AU DÉCOMPTE CPPF VALIDÉE(S) SUR LA BASE DE L'ÉTAT GÉNÉRAL DES SERVICES(EGS) / ÉTAT SIGNALÉTIQUE ET DES SERVICES(ESS)
    </div>
    
    <table class="cotisations-table">
        <thead>
            <tr>
                <th>Période</th>
                <th>Position</th>
                <th>Corps</th>
                <th>Référence</th>
                <th>Retenue</th>
                <th>Durée</th>
            </tr>
        </thead>
        <tbody>
            <tr class="total-row">
                <td colspan="5" style="text-align: right; font-weight: bold;">TOTAL</td>
                <td style="font-weight: bold;">0 mois</td>
            </tr>
        </tbody>
    </table>
    
    <!-- Résumé final -->
    <div class="resume">
        <h3>DÉCOMPTE GÉNÉRAL</h3>
        <div class="resume-item">
            - Durée des services validés : {{ $resume['duree_service_formatee'] }}
        </div>
        <div class="resume-item">
            - Droit à pension: <strong>{{ $resume['droit_pension'] }}</strong>
        </div>
        @if(isset($statistiques['total_cotisations']) && $statistiques['total_cotisations'] > 0)
        <div class="resume-item">
            - Total des cotisations versées : {{ number_format($statistiques['total_cotisations'], 0, ',', ' ') }} FCFA
        </div>
        @endif
        @if($resume['derniere_cotisation'])
        <div class="resume-item">
            - Dernière cotisation : {{ $resume['derniere_cotisation'] }}
        </div>
        @endif
    </div>
    
    <!-- Pied de page et signature -->
    <div class="footer">
        <div style="float: left; font-size: 9px; margin-top: 20px;">
            <p><strong>Caisse des Pensions et des Prestations Familiales des Agents de l'Etat</strong></p>
            <p>Solidaires aujourd'hui, pour un Avenir Prospère.</p>
            <p>BP: 3932 - Tel: +241 04 62 52 19 / 01 73 07 65</p>
            <p>E-Mail : contact@cppf.ga - Site : www.cppf.ga</p>
        </div>
        
        <div class="signature-box">
            <p style="margin: 0; font-size: 9px;">Fait à Libreville, le {{ $date_generation }}</p>
            <br>
            <p style="margin: 0; font-size: 9px; font-weight: bold;">Le Directeur Financier</p>
            <p style="margin: 5px 0 0 0; font-size: 8px;">Pour le Directeur Général, et par délégation.</p>
        </div>
        
        <div style="clear: both;"></div>
    </div>
</body>
</html>