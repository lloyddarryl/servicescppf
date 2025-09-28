<?php
// app/Http/Controllers/HistoriquePaiementController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HistoriquePaiement;
use Illuminate\Support\Facades\Log;

class HistoriquePaiementController extends Controller
{
    public function index(Request $request)
    {
        try {
            $retraite = $request->user();
            
            $request->validate([
                'annee' => 'nullable|integer|min:2000|max:' . (date('Y') + 1),
                'mois' => 'nullable|integer|min:1|max:12',
                'etat' => 'nullable|string',
                'per_page' => 'nullable|integer|min:5|max:50'
            ]);

            // MODIFICATION : Ne pas définir d'année par défaut
            $annee = $request->get('annee'); // Supprimé : date('Y')
            $mois = $request->get('mois');
            $etat = $request->get('etat');
            $perPage = $request->get('per_page', 12);

            $query = $retraite->historiquePaiements()->orderBy('date_paiement', 'desc');

            // MODIFICATION : Appliquer le filtre d'année seulement si spécifié
            if ($annee) {
                $query->whereYear('date_paiement', $annee);
            }
            
            if ($mois) {
                $query->whereMonth('date_paiement', $mois);
            }
            
            if ($etat) {
                $query->where('etat_paiement', $etat);
            }

            // MODIFICATION : Pour la pagination, si aucune année spécifiée, récupérer TOUTES les données
            if (!$annee && !$mois && !$etat) {
                // Si aucun filtre, récupérer toutes les données sans pagination pour permettre l'affichage par année
                $paiements = collect($query->get());
                $paginationData = [
                    'current_page' => 1,
                    'per_page' => $paiements->count(),
                    'total' => $paiements->count(),
                    'last_page' => 1,
                    'from' => 1,
                    'to' => $paiements->count()
                ];
            } else {
                // Sinon, utiliser la pagination normale
                $paiementsQuery = $query->paginate($perPage);
                $paiements = collect($paiementsQuery->items());
                $paginationData = [
                    'current_page' => $paiementsQuery->currentPage(),
                    'per_page' => $paiementsQuery->perPage(),
                    'total' => $paiementsQuery->total(),
                    'last_page' => $paiementsQuery->lastPage(),
                    'from' => $paiementsQuery->firstItem(),
                    'to' => $paiementsQuery->lastItem()
                ];
            }

            // MODIFICATION : Calculer les statistiques selon le contexte
            $queryStats = $retraite->historiquePaiements();
            if ($annee) {
                $queryStats->whereYear('date_paiement', $annee);
            }
            if ($mois) {
                $queryStats->whereMonth('date_paiement', $mois);
            }
            if ($etat) {
                $queryStats->where('etat_paiement', $etat);
            }

            $totalPaiements = $queryStats->count();
            $montantTotal = $queryStats->sum('montant_net');
            $moyenneMensuelle = $totalPaiements > 0 ? $montantTotal / $totalPaiements : 0;
            
            $statistiques = [
                'total_paiements' => $totalPaiements,
                'montant_total' => $montantTotal,
                'moyenne_mensuelle' => $moyenneMensuelle
            ];
            
            // MODIFICATION : Paiements par mois seulement si une année est spécifiée
            $paiementsParMois = $annee ? $this->getPaiementsMensuelsDeLaBD($retraite->id, $annee) : null;
            $anneesDisponibles = $this->getAnneesDisponibles($retraite->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'paiements' => $paiements->toArray(),
                    'pagination' => $paginationData,
                    'resume' => [
                        'retraite_info' => [
                            'nom_complet' => $retraite->prenoms . ' ' . $retraite->nom,
                            'numero_pension' => $retraite->numero_pension,
                            'montant_pension' => $retraite->montant_pension,
                            'titre_civilite' => $retraite->titre_civilite ?? 'M.'
                        ],
                        'statistiques_annee' => $statistiques,
                        'paiements_par_mois' => $paiementsParMois,
                        'annees_disponibles' => $anneesDisponibles,
                        // NOUVEAU : Indiquer si on affiche toutes les années
                        'affichage_toutes_annees' => !$annee
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération historique paiements:', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique'
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $retraite = $request->user();
            $paiement = $retraite->historiquePaiements()->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => ['paiement' => $paiement]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement non trouvé'
            ], 404);
        }
    }

    public function telechargerPDF(Request $request)
{
    try {
        $retraite = $request->user();
        
        $request->validate([
            'annee' => 'nullable|integer|min:2000|max:' . date('Y'),
            'mois' => 'nullable|integer|min:1|max:12'
        ]);

        // RÉCUPÉRER TOUS LES VERSEMENTS de la personne si aucune année spécifiée
        $query = $retraite->historiquePaiements()->orderBy('date_paiement', 'desc');
        
        // Appliquer les filtres seulement s'ils sont fournis
        if ($request->annee) {
            $query->whereYear('date_paiement', $request->annee);
        }
        
        if ($request->mois) {
            $query->whereMonth('date_paiement', $request->mois);
        }

        $paiements = $query->get();
        
        if ($paiements->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun paiement trouvé pour cette période'
            ], 404);
        }

        $filtres = $request->only(['annee', 'mois']);
        
        // CORRECTION : Toujours passer $paiements à la vue
        // Si une année est spécifiée, on passe directement $paiements
        // Sinon, on organise par année ET on passe aussi $paiements
        $paiementsOrganises = null;
        if (!$request->annee) {
            $paiementsOrganises = $paiements->groupBy(function($paiement) {
                return $paiement->date_paiement->year;
            })->sortKeysDesc();
        }
        
        $html = view('pdf.historique-paiements', [
            'retraite' => $retraite,
            'paiements' => $paiements, // TOUJOURS passer cette variable
            'paiements_par_annee' => $paiementsOrganises,
            'filtres' => $filtres,
            'toutes_annees' => !$request->annee,
            'annee_specifique' => $request->annee // Nouvelle variable pour clarifier
        ])->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        
        $filename = $this->genererNomFichierPDF($retraite, $filtres);

        return $pdf->download($filename);

    } catch (\Exception $e) {
        Log::error('Erreur génération PDF historique:', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'request_data' => $request->all()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la génération du PDF: ' . $e->getMessage()
        ], 500);
    }
}

    public function statistiques(Request $request)
    {
        try {
            $retraite = $request->user();
            
            $request->validate([
                'annee' => 'nullable|integer|min:2000|max:' . (date('Y') + 1)
            ]);

            $annee = $request->get('annee');
            
            $query = $retraite->historiquePaiements();
            if ($annee) {
                $query->whereYear('date_paiement', $annee);
            }

            $totalPaiements = $query->count();
            $montantTotal = $query->sum('montant_net');
            $moyenneMensuelle = $totalPaiements > 0 ? $montantTotal / $totalPaiements : 0;

            // Récupérer l'évolution mensuelle pour l'année
            $evolutionMensuelle = $annee ? $this->getPaiementsMensuelsDeLaBD($retraite->id, $annee) : null;

            return response()->json([
                'success' => true,
                'data' => [
                    'statistiques_generales' => [
                        'total_paiements' => $totalPaiements,
                        'montant_total' => $montantTotal,
                        'moyenne_mensuelle' => $moyenneMensuelle
                    ],
                    'evolution_mensuelle' => $evolutionMensuelle,
                    'periode' => [
                        'annee' => $annee ?: 'Toutes',
                        'generee_le' => now()->format('d/m/Y H:i')
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur statistiques paiements:', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des statistiques'
            ], 500);
        }
    }

    public function rechercher(Request $request)
    {
        try {
            $retraite = $request->user();
            
            $request->validate([
                'q' => 'required|string|min:2',
                'per_page' => 'nullable|integer|min:5|max:50'
            ]);

            $terme = $request->get('q');
            $perPage = $request->get('per_page', 12);

            // RECHERCHE DANS TOUTES LES ANNÉES par défaut
            $query = $retraite->historiquePaiements()
                ->where(function($q) use ($terme) {
                    $q->where('numero_titre', 'like', "%{$terme}%")
                      ->orWhere('mode_reglement', 'like', "%{$terme}%")
                      ->orWhere('regime', 'like', "%{$terme}%")
                      ->orWhere('nom_beneficiaire', 'like', "%{$terme}%")
                      ->orWhere('prenoms_beneficiaire', 'like', "%{$terme}%")
                      ->orWhere('etat_paiement', 'like', "%{$terme}%")
                      ->orWhere('disponibilite', 'like', "%{$terme}%")
                      ->orWhere('type_paiement', 'like', "%{$terme}%")
                      ->orWhere('complement_nom', 'like', "%{$terme}%")
                      ->orWhere('observations', 'like', "%{$terme}%");
                })
                ->orderBy('date_paiement', 'desc');

            // Pour la recherche, récupérer tous les résultats sans pagination pour permettre l'affichage par année
            $paiements = $query->get();

            // Simuler la pagination pour compatibilité avec le frontend
            $total = $paiements->count();
            $paginationData = [
                'current_page' => 1,
                'per_page' => $total,
                'total' => $total,
                'last_page' => 1,
                'from' => $total > 0 ? 1 : null,
                'to' => $total
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'paiements' => $paiements->toArray(),
                    'pagination' => $paginationData,
                    'terme_recherche' => $terme
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur recherche paiements:', [
                'error' => $e->getMessage(),
                'terme' => $request->get('q')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche'
            ], 500);
        }
    }

    // NOUVELLE MÉTHODE : Pour obtenir tous les paiements organisés par année
    public function obtenirToutesLesAnnees(Request $request)
    {
        try {
            $retraite = $request->user();
            
            $request->validate([
                'mois' => 'nullable|integer|min:1|max:12',
                'etat' => 'nullable|string'
            ]);

            $mois = $request->get('mois');
            $etat = $request->get('etat');

            $query = $retraite->historiquePaiements()->orderBy('date_paiement', 'desc');
            
            if ($mois) {
                $query->whereMonth('date_paiement', $mois);
            }
            
            if ($etat) {
                $query->where('etat_paiement', $etat);
            }

            $paiements = $query->get();
            
            // Organiser par année
            $paiementsParAnnee = $paiements->groupBy(function($paiement) {
                return $paiement->date_paiement->year;
            });

            // Trier les années par ordre décroissant
            $paiementsParAnnee = $paiementsParAnnee->sortKeysDesc();

            $anneesDisponibles = $this->getAnneesDisponibles($retraite->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'paiements_par_annee' => $paiementsParAnnee,
                    'annees_disponibles' => $anneesDisponibles,
                    'total_annees' => $paiementsParAnnee->count()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération toutes les années:', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données'
            ], 500);
        }
    }

    // MÉTHODE MANQUANTE CORRIGÉE
    private function getPaiementsMensuelsDeLaBD($retraiteId, $annee = null)
    {
        $anneeActuelle = $annee ?: date('Y');
        
        $paiements = HistoriquePaiement::where('retraite_id', $retraiteId)
            ->whereYear('date_paiement', $anneeActuelle)
            ->orderBy('date_paiement')
            ->get()
            ->keyBy(function($paiement) {
                return $paiement->date_paiement->month;
            });

        $moisData = [];
        for ($mois = 1; $mois <= 12; $mois++) {
            $paiementMois = $paiements->get($mois);
            
            $moisData[] = [
                'mois' => $mois,
                'nom_mois' => \Carbon\Carbon::create()->month($mois)->locale('fr')->format('F'),
                'annee' => $anneeActuelle,
                'montant' => $paiementMois?->montant_net ?? 0,
                'date_paiement' => $paiementMois?->date_paiement?->format('d/m/Y'),
                'numero_titre' => $paiementMois?->numero_titre,
                'etat' => $paiementMois?->etat_paiement ?? 'non_verse',
                'verse' => !is_null($paiementMois)
            ];
        }

        return $moisData;
    }

    private function getAnneesDisponibles($retraiteId)
    {
        return HistoriquePaiement::where('retraite_id', $retraiteId)
            ->selectRaw('DISTINCT YEAR(date_paiement) as annee')
            ->orderBy('annee', 'desc')
            ->pluck('annee')
            ->toArray();
    }

    // Méthode utilitaire pour formater la période dans le nom de fichier
    private function genererNomFichierPDF($retraite, $filtres)
    {
        $date = now()->format('Y-m-d');
        $nom = str_replace([' ', '.', '/'], ['_', '', '_'], $retraite->nom);
        
        $suffixe = '';
        if (isset($filtres['annee']) && $filtres['annee']) {
            $suffixe .= '_' . $filtres['annee'];
        } else {
            $suffixe .= '_toutes_annees';
        }
        
        if (isset($filtres['mois']) && $filtres['mois']) {
            $suffixe .= '_' . str_pad($filtres['mois'], 2, '0', STR_PAD_LEFT);
        }

        return "historique_versements_{$nom}_{$date}{$suffixe}.pdf";
    }
}