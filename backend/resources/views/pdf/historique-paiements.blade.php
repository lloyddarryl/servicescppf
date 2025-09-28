<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Historique des versements</title>
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
        
        .debug {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 10px;
            margin: 10px 0;
            font-size: 10px;
            color: #856404;
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
        
        .etat-verse, .etat-versé {
            color: #27ae60;
            font-weight: bold;
        }
        
        .etat-en-attente {
            color: #f39c12;
            font-weight: bold;
        }
        
        .etat-rejete, .etat-rejeté {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .etat-traite, .etat-traité {
            color: #3498db;
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
        // CORRECTION : Vérification de l'existence et du type de $paiements
        if (!isset($paiements) || $paiements === null) {
            $paiements = collect([]);
        }
        
        // Si on a des paiements organisés par année, les utiliser
        if (isset($paiements_par_annee) && $paiements_par_annee !== null) {
            $paiementsParAnnee = $paiements_par_annee;
        } else {
            // Sinon, organiser les paiements par année
            $paiementsParAnnee = $paiements->groupBy(function($paiement) {
                return $paiement->date_paiement->year;
            })->sortKeysDesc();
        }
        
        // CORRECTION : Fonction robuste pour détecter si un paiement est effectué
        $estEffectue = function($etatPaiement) {
            if (empty($etatPaiement)) return false;
            
            $etat = strtolower(trim($etatPaiement));
            $etatsEffectues = [
                'verse', 'versé', 'VERSE', 'VERSÉ',
                'traite', 'traité', 'TRAITE', 'TRAITÉ',
                'paye', 'payé', 'PAYE', 'PAYÉ',
                'effectue', 'effectué', 'EFFECTUE', 'EFFECTUÉ',
                'complete', 'complété', 'COMPLETE', 'COMPLÉTÉ'
            ];
            
            // Vérification exacte
            foreach ($etatsEffectues as $etatEffectue) {
                if (strtolower($etatEffectue) === $etat) {
                    return true;
                }
            }
            
            // Vérification par contenu (si l'état contient un de ces mots)
            $motsEffectues = ['vers', 'trait', 'pay', 'effect', 'complet'];
            foreach ($motsEffectues as $mot) {
                if (strpos($etat, $mot) !== false) {
                    return true;
                }
            }
            
            return false;
        };
        
        // Calculer les totaux généraux avec la nouvelle logique
        $totalGeneral = $paiements->sum('montant_net') ?? 0;
        $nombreTotalVersements = $paiements->count() ?? 0;
        $nombreTotalVerses = $paiements->filter(function($paiement) use ($estEffectue) {
            return $estEffectue($paiement->etat_paiement);
        })->count();
        
        // Pour diagnostic : collecter les états uniques
        $etatsUniques = $paiements->pluck('etat_paiement')->unique()->filter()->toArray();
        $etatsAvecComptage = [];
        foreach ($etatsUniques as $etat) {
            $comptage = $paiements->where('etat_paiement', $etat)->count();
            $estEffectueStatus = $estEffectue($etat) ? 'OUI' : 'NON';
            $etatsAvecComptage[] = "'{$etat}' ({$comptage}) -> Effectué: {$estEffectueStatus}";
        }
        
        // Déterminer le titre selon le contexte
        $titre = 'HISTORIQUE DES VERSEMENTS';
        if (isset($filtres['annee']) && $filtres['annee']) {
            $titre .= ' - ANNÉE ' . $filtres['annee'];
            if (isset($filtres['mois']) && $filtres['mois']) {
                $moisNoms = [1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 
                            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août', 
                            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'];
                $titre .= ' - ' . strtoupper($moisNoms[$filtres['mois']]);
            }
        } else {
            $titre = 'HISTORIQUE COMPLET DES VERSEMENTS';
        }
    @endphp

   <!-- En-tête -->
    <div class="header">
        <div class="logo">
            <img src="{{ public_path('images/cppf.png') }}" alt="Logo CPPF" style="height: 70px;">
        </div>
    </div>


    <!-- Titre -->
    <h1 class="title">{{ $titre }}</h1>

    <!-- Informations du retraité -->
    <div class="retraite-info">
        {{ $retraite->titre_civilite ?? 'M.' }} {{ $retraite->prenoms }} {{ $retraite->nom }}
    </div>
    
    <div class="dossier-info">
        Dossier de pension n° {{ $retraite->numero_pension ?? 'N/A' }}
        @if(isset($filtres['annee']) && $filtres['annee'])
            - Année {{ $filtres['annee'] }}
            @if(isset($filtres['mois']) && $filtres['mois'])
                - Mois {{ str_pad($filtres['mois'], 2, '0', STR_PAD_LEFT) }}
            @endif
        @endif
    </div>


    @if($nombreTotalVersements > 0)
        <!-- Résumé général -->
        <div class="summary">
            <div class="summary-title">Résumé :</div>
            <div>
                @if($paiementsParAnnee->count() > 1)
                    <strong>Période couverte :</strong> {{ $paiementsParAnnee->keys()->min() }} - {{ $paiementsParAnnee->keys()->max() }}<br>
                @endif
                <strong>Total versements :</strong> {{ $nombreTotalVersements }}<br>
                <strong>Versements effectués :</strong> {{ $nombreTotalVerses }}<br>
                <strong>Montant total :</strong> {{ number_format($totalGeneral, 0, ',', ' ') }} FCFA
            </div>
        </div>

        <!-- Affichage par année -->
        @foreach($paiementsParAnnee as $annee => $paiementsAnnee)
            @php
                $totalAnnee = $paiementsAnnee->sum('montant_net');
                $nombreVersementsAnnee = $paiementsAnnee->count();
                // CORRECTION : Utiliser la fonction d'aide pour compter les effectués
                $nombreVersesAnnee = $paiementsAnnee->filter(function($paiement) use ($estEffectue) {
                    return $estEffectue($paiement->etat_paiement);
                })->count();
            @endphp

            <div class="annee-section">
                @if($paiementsParAnnee->count() > 1)
                <!-- En-tête de l'année (seulement si plusieurs années) -->
                <div class="annee-header">
                    ANNÉE {{ $annee }}
                </div>

                <!-- Statistiques de l'année -->
                <div class="annee-stats">
                    <strong>{{ $nombreVersementsAnnee }} versement(s)</strong> | 
                    <strong>{{ $nombreVersesAnnee }} effectué(s)</strong> | 
                    <strong>Total : {{ number_format($totalAnnee, 0, ',', ' ') }} FCFA</strong>
                </div>
                @endif

                <!-- Tableau des paiements -->
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
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paiementsAnnee->sortByDesc('date_paiement') as $paiement)
                            @php
                                // Normaliser l'état pour les classes CSS
                                $etatNormalise = strtolower(str_replace(['é', 'è', ' ', '_'], ['e', 'e', '-', '-'], $paiement->etat_paiement));
                            @endphp
                            <tr>
                                <td>{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                                <td><strong>{{ $paiement->numero_titre }}</strong></td>
                                <td class="etat-{{ $etatNormalise }}">
                                    {{ ucfirst($paiement->etat_paiement) }}
                                </td>
                                <td>{{ $paiement->regime }}</td>
                                <td>{{ $paiement->disponibilite }}</td>
                                <td class="mode-reglement">{{ $paiement->mode_reglement }}</td>
                                <td class="montant">{{ number_format($paiement->montant_net, 0, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Totaux de l'année (seulement si plusieurs versements et plusieurs années) -->
                @if($nombreVersementsAnnee > 1 && $paiementsParAnnee->count() > 1)
                <div style="text-align: right; margin-bottom: 20px; font-weight: bold; background: #f8f9fa; padding: 8px; border: 1px solid #dee2e6;">
                    Sous-total {{ $annee }} : {{ number_format($totalAnnee, 0, ',', ' ') }} FCFA
                </div>
                @endif
            </div>

            <!-- Saut de page après chaque année (sauf la dernière et sauf si une seule année) -->
            @if(!$loop->last && $paiementsParAnnee->count() > 1)
                <div class="page-break"></div>
            @endif
        @endforeach

        <!-- Total général final (seulement si plusieurs années ou plusieurs versements) -->
        @if($paiementsParAnnee->count() > 1 || $nombreTotalVersements > 1)
        <div class="grand-total">
            TOTAL GÉNÉRAL : {{ number_format($totalGeneral, 0, ',', ' ') }} FCFA
            <br>
            {{ $nombreTotalVersements }} versement(s) | {{ $nombreTotalVerses }} effectué(s)
            @if($paiementsParAnnee->count() > 1)
                sur {{ $paiementsParAnnee->keys()->count() }} année(s)
                ({{ $paiementsParAnnee->keys()->min() }} - {{ $paiementsParAnnee->keys()->max() }})
            @endif
        </div>
        @endif

    @else
        <!-- Aucun paiement trouvé -->
        <div class="summary">
            <div class="summary-title">Aucun versement trouvé</div>
            <div>
                Aucun versement n'a été trouvé pour les critères spécifiés.
            </div>
        </div>
    @endif

    <!-- Pied de page -->
    <div class="footer">
        <div class="footer-content">
            <span>Édité le : {{ now()->format('d/m/Y à H:i') }}</span>
            <span>SYSTÈME AUTOMATIQUE - CPPF</span>
            <span>
                @if(isset($toutes_annees) && $toutes_annees)
                    Document complet
                @else
                    Document filtré
                @endif
            </span>
        </div>
    </div>
</body>
</html>