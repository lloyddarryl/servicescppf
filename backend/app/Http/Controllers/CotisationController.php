<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Agent;
use App\Models\Carriere;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class CotisationController extends Controller
{
    public function index(Request $request)
    {
        $agent = $request->user();
        
        $annee = $request->input('annee');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);

        $query = $agent->carrieresValides();

        if ($annee) {
            $query->whereYear('date_debut', $annee);
        }

        $carrieres = $query->paginate($perPage);
        $statistiques = Carriere::getStatistiquesAgent($agent->id);
        $donneesGraphique = $this->getDonneesGraphique($agent->id);

        $anneesDisponibles = $agent->carrieresValides()
                                  ->selectRaw('YEAR(date_debut) as annee')
                                  ->distinct()
                                  ->orderByDesc('annee')
                                  ->pluck('annee');

        return response()->json([
            'success' => true,
            'data' => [
                'agent' => [
                    'id' => $agent->id,
                    'nom_complet' => $agent->prenoms . ' ' . $agent->nom,
                    'matricule_solde' => $agent->matricule_solde,
                    'num_affiliation' => $agent->num_affiliation,
                    'grade' => $agent->grade_actuel,
                    'indice' => $agent->indice_actuel,
                    'statut' => $agent->statut_format,
                    'duree_service' => $agent->duree_service_formatee,
                    'retenue_mensuelle' => $agent->retenue_mensuelle,
                    'sexe' => $agent->sexe,
                    'situation_matrimoniale' => $agent->situation_matrimoniale,
                    'prenoms' => $agent->prenoms,
                    'nom' => $agent->nom,
                    'type_compte' => 'AGENT ACTIF'
                ],
                'cotisations' => $carrieres->items(),
                'pagination' => [
                    'current_page' => $carrieres->currentPage(),
                    'per_page' => $carrieres->perPage(),
                    'total' => $carrieres->total(),
                    'last_page' => $carrieres->lastPage(),
                    'from' => $carrieres->firstItem(),
                    'to' => $carrieres->lastItem()
                ],
                'statistiques' => [
                    'nombre_periodes' => $statistiques['nombre_carrieres'],
                    'duree_totale' => floor($statistiques['duree_totale_mois'] / 12) . ' ans ' . 
                                    ($statistiques['duree_totale_mois'] % 12) . ' mois',
                    'total_cotisations' => number_format($statistiques['total_cotisations'], 0, ',', ' ') . ' FCFA',
                    'cotisation_moyenne' => number_format($statistiques['cotisation_moyenne'], 0, ',', ' ') . ' FCFA',
                    'cotisation_actuelle' => number_format($agent->retenue_mensuelle ?? 0, 0, ',', ' ') . ' FCFA',
                    'premiere_cotisation' => $statistiques['premiere_carriere'] ? 
                                           Carbon::parse($statistiques['premiere_carriere'])->format('d/m/Y') : 'N/A',
                    'derniere_cotisation' => $statistiques['derniere_carriere'] ? 
                                           Carbon::parse($statistiques['derniere_carriere'])->format('d/m/Y') : 'N/A',
                    'droit_pension' => $statistiques['duree_totale_mois'] >= 180 ? 'OUI' : 'NON'
                ],
                'graphique' => $donneesGraphique,
                'filtres' => [
                    'annees_disponibles' => $anneesDisponibles,
                    'statuts_disponibles' => ['actif', 'valide', 'suspendu', 'annule']
                ]
            ]
        ]);
    }

    private function getDonneesGraphique($agentId, $nbMois = 12)
    {
        $carrieres = Carriere::where('agent_id', $agentId)
                            ->where('statut', 'VALIDE')
                            ->orderBy('date_debut')
                            ->get();

        if ($carrieres->isEmpty()) {
            return [];
        }

        $donnees = [];
        $maintenant = Carbon::now();

        // Générer les 12 derniers mois
        for ($i = $nbMois - 1; $i >= 0; $i--) {
            $mois = $maintenant->copy()->subMonths($i);
            $debutMois = $mois->copy()->startOfMonth();
            $finMois = $mois->copy()->endOfMonth();

            // Trouver la carrière active pour ce mois
            $carriereActive = $carrieres->filter(function($carriere) use ($debutMois, $finMois) {
                $debut = $carriere->date_debut;
                $fin = $carriere->date_fin ?: Carbon::now();
                
                // La carrière est active si elle couvre ce mois
                return $debut <= $finMois && $fin >= $debutMois;
            })->first();

            $donnees[] = [
                'mois' => $mois->format('M Y'),
                'mois_complet' => $mois->format('F Y'),
                'retenue' => $carriereActive ? (float) $carriereActive->retenue : 0,
                'grade' => $carriereActive ? $carriereActive->grade : null,
                'indice' => $carriereActive ? $carriereActive->indice : null,
                'statut' => $carriereActive ? 'actif' : 'aucune'
            ];
        }

        return $donnees;
    }

    public function genererRelevePDF(Request $request)
    {
        try {
            $agent = $request->user();
            
            $donneesReleve = $agent->genererReleveSituation();
            
            $donneesReleve['date_generation'] = now()->format('d/m/Y');
            $donneesReleve['heure_generation'] = now()->format('H:i');
            $donneesReleve['operateur'] = 'D005-15';
            
            $statistiques = Carriere::getStatistiquesAgent($agent->id);
            $donneesReleve['statistiques'] = [
                'total_cotisations' => $statistiques['total_cotisations'],
                'duree_totale_mois' => $statistiques['duree_totale_mois'],
                'nombre_carrieres' => $statistiques['nombre_carrieres']
            ];
            
            $pdf = Pdf::loadView('pdf.releve-situation', $donneesReleve);
            $pdf->setPaper('A4', 'portrait');
            
            $nomFichier = 'releve_situation_' . $agent->matricule_solde . '_' . now()->format('Ymd_His') . '.pdf';
            
            return $pdf->download($nomFichier);
            
        } catch (\Exception $e) {
            \Log::error('Erreur génération PDF relevé:', [
                'agent_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du relevé PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        $agent = $request->user();
        
        $carriere = Carriere::where('agent_id', $agent->id)
                           ->where('id', $id)
                           ->first();
        
        if (!$carriere) {
            return response()->json([
                'success' => false,
                'message' => 'Cotisation non trouvée'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'cotisation' => $carriere,
                'details' => [
                    'periode_formatee' => $carriere->periode_formatee,
                    'retenue_formatee' => $carriere->retenue_formatee,
                    'salaire_brut' => number_format($carriere->salaire_brut, 0, ',', ' ') . ' FCFA',
                    'taux_cotisation' => number_format($carriere->taux_cotisation, 2) . '%',
                    'duree_formatee' => $carriere->duree_formatee,
                    'is_active' => $carriere->isActive()
                ]
            ]
        ]);
    }

    public function search(Request $request)
    {
        $agent = $request->user();
        $terme = $request->input('q');
        
        if (!$terme) {
            return response()->json([
                'success' => false,
                'message' => 'Terme de recherche requis'
            ], 400);
        }
        
        $resultats = $agent->carrieresValides()
                          ->where(function($query) use ($terme) {
                              $query->where('etablissement', 'LIKE', "%{$terme}%")
                                    ->orWhere('corps', 'LIKE', "%{$terme}%")
                                    ->orWhere('position', 'LIKE', "%{$terme}%")
                                    ->orWhere('grade', $terme)
                                    ->orWhere('indice', $terme);
                          })
                          ->orderBy('date_debut', 'desc')
                          ->paginate(10);
        
        return response()->json([
            'success' => true,
            'data' => $resultats,
            'terme_recherche' => $terme
        ]);
    }

    public function statistiques(Request $request)
    {
        $agent = $request->user();
        $statistiques = Carriere::getStatistiquesAgent($agent->id);
        
        $parAnnee = $agent->carrieresValides()
                         ->selectRaw('YEAR(date_debut) as annee, 
                                    SUM(retenue) as total_retenue,
                                    SUM(nombre_mois) as total_mois,
                                    COUNT(*) as nb_carrieres')
                         ->groupBy('annee')
                         ->orderByDesc('annee')
                         ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'generales' => $statistiques,
                'par_annee' => $parAnnee,
            ]
        ]);
    }

    public function exportExcel(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Export Excel à implémenter'
        ], 501);
    }
}