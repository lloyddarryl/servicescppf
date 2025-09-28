<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Agent;
use App\Models\Retraite;
use Carbon\Carbon;
use Illuminate\Support\Str; 
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $userType = $user instanceof Agent ? 'actif' : 'retraite';
    
        if ($userType === 'actif') {
            return $this->agentDashboard($request);
        } else {
            return $this->retraiteDashboard($request);
        }
    }

    /**
     * Dashboard pour agents actifs avec données dynamiques - VERSION CORRIGÉE
     */
    public function agentDashboard(Request $request)
    {
        $agent = $request->user();
        
        // Calcul dynamique des années et mois de service
        $dateService = Carbon::parse($agent->date_prise_service);
        $anneesService = $dateService->diffInYears(now());
        $moisService = $dateService->diffInMonths(now()) % 12;
        
        // CORRECTION : Utiliser DIRECTEMENT la colonne total_cotisations de carrieres
        $totalCotisations = $agent->carrieresValides()->sum('total_cotisations');
        
        // Si pas de données dans carrieres, fallback sur agent
        if ($totalCotisations == 0) {
            $totalCotisations = $agent->total_cotisations ?? 0;
        }

        $stats = [
            'cotisations_totales' => $totalCotisations,
            'duree_service_annees' => $anneesService,
            'duree_service_mois' => $moisService,
            'attestations_demandees' => $this->compterAttestationsDemandees($agent),
            'dossiers_en_cours' => $this->compterDossiersEnCours($agent),
            'rendez_vous_pris' => $this->compterRendezVousPris($agent),
            'reclamations_total' => $this->compterReclamations($agent),
        ];
        
        // Activités récentes avec gestion d'erreur renforcée
        $activites = $this->getActivitesRecentesDynamiquesFixed($agent);
        
        // Notifications RDV avec limite pour l'affichage
        $notificationsRdv = $this->getRdvNotifications($agent);
        
        // ✅ CORRECTION : S'assurer que les notifications sont dans le bon format
        if (!is_array($notificationsRdv) || empty($notificationsRdv)) {
            $notificationsRdv = null; // Sera géré côté frontend
        }

        // Services disponibles
        $services = [
            [
                'id' => 'simulateur_pension',
                'name' => 'Simulateur de Pension',
                'description' => 'Estimez votre pension de retraite.',
                'icon' => 'cog',
                'available' => true,
                'priority' => 1,
                'badge' => 'Article 94',
                'color' => 'blue',
                'subtitle' => 'Nouvelle réglementation'
            ],
            [
                'id' => 'grappe_familiale',
                'name' => 'Grappe Familiale',
                'description' => 'Gérer vos ayants droit et bénéficiaires',
                'icon' => 'users',
                'available' => true,
                'color' => 'green'
            ],
            [
                'id' => 'cotisations',
                'name' => 'Suivi des Cotisations',
                'description' => 'Consulter l\'historique de vos cotisations',
                'icon' => 'chart',
                'available' => true,
                'color' => 'purple'
            ],
            [
                'id' => 'prise_rdv',
                'name' => 'Prise de Rendez-vous',
                'description' => 'Réserver un rendez-vous avec un conseiller',
                'icon' => 'calendar',
                'available' => true,
                'color' => 'orange'
            ],
            [
                'id' => 'reclamations',
                'name' => 'Réclamations',
                'description' => 'Gérer vos réclamations et demandes',
                'icon' => 'reclamation',
                'available' => true,
                'color' => 'red'
            ]           
        ];

        return response()->json([
            'success' => true,
            'user_type' => 'actif',
            'user' => [
                'id' => $agent->id,
                'matricule_solde' => $agent->matricule_solde,
                'nom_complet' => $agent->prenoms . ' ' . $agent->nom,
                'nom' => $agent->nom,
                'prenoms' => $agent->prenoms,
                'sexe' => $agent->sexe,
                'poste' => $agent->poste,
                'direction' => $agent->direction,
                'grade' => $agent->grade,
                'email' => $agent->email,
                'telephone' => $agent->telephone,
                'date_prise_service' => $agent->date_prise_service,
                'annees_service' => $anneesService,
                'mois_service' => $moisService,
                'status' => $agent->status,
                'email_verified' => !is_null($agent->email_verified_at),
                'phone_verified' => !is_null($agent->phone_verified_at),
            ],
            'dashboard' => [
                'stats' => $stats,
                'activites_recentes' => $activites,
                'services_disponibles' => $services,
                'notifications_rdv' => $notificationsRdv, 
                'info_article94' => [
                    'titre' => 'Nouvelle Réglementation Article 94',
                    'description' => 'Calcul des pensions selon la formule : Années de service × 1,8%',
                    'coefficients_actuels' => [
                        '2025' => '91%',
                        '2026' => '94%', 
                        '2027' => '96%',
                        '2028' => '98%',
                        '2029+' => '100%'
                    ]
                ]
            ]
        ]);
    }

    /**
 * Dashboard pour retraités - VERSION CORRIGÉE
 */
public function retraiteDashboard(Request $request)
{
    $retraite = $request->user();
    
    // Calcul EXACT des années depuis la retraite
    $anneesRetraite = $retraite->date_retraite ? 
        Carbon::parse($retraite->date_retraite)->diffInYears(now()) : 0;
    
    // Calcul EXACT des mois depuis la retraite
    $moisRetraite = $retraite->date_retraite ? 
        Carbon::parse($retraite->date_retraite)->diffInMonths(now()) : 0;

    // ✅ CORRECTION: Calculs précis et arrondis
    $pensionMensuelle = $retraite->montant_pension ?? 0;
    $totalPercu = $pensionMensuelle * $moisRetraite; // Utiliser moisRetraite au lieu d'années*12
    
    // Statistiques pour retraités - ARRONDIES
    $stats = [
        'pension_mensuelle' => round($pensionMensuelle),
        'pensions_recues' => round($moisRetraite), // Nombre de mois exact, pas années*12
        'total_percu' => round($totalPercu), // Arrondi pour éviter les décimales
        'certificats_valides' => $this->compterCertificatsValidesReel($retraite)
    ];

    // ✅ Activités récentes sans erreur
    try {
        $activites = $this->getActivitesRecentesRetraiteFixed($retraite);
    } catch (\Exception $e) {
        Log::error('Erreur activités retraité:', ['error' => $e->getMessage()]);
        $activites = []; // Fallback vide
    }
    
    // ✅ Notifications RDV avec gestion d'erreur
    try {
        $notificationsRdv = $this->getRdvNotifications($retraite);
    } catch (\Exception $e) {
        Log::error('Erreur notifications RDV:', ['error' => $e->getMessage()]);
        $notificationsRdv = null;
    }

    // Services disponibles pour retraités
    $services = [
        [
            'id' => 'historique-paiements',
            'name' => 'Historique de Paiements',
            'description' => 'Consulter l\'historique de vos paiements',
            'icon' => 'banknotes',
            'available' => true
        ],
        [
            'id' => 'grappe_familiale',
            'name' => 'Grappe Familiale',
            'description' => 'Gérer vos ayants droit et bénéficiaires',
            'icon' => 'users',
            'available' => true,
            'color' => 'green'
        ],
        [
            'id' => 'documents',
            'name' => 'Documents',
            'description' => 'Gérer vos documents',
            'icon' => 'document-check',
            'available' => true
        ],
        [
            'id' => 'rendez-vous',
            'name' => 'Rendez-vous',
            'description' => 'Prendre rendez-vous',
            'icon' => 'calendar',
            'available' => true
        ],
        [
            'id' => 'reclamations',
            'name' => 'Réclamations',
            'description' => 'Gérer vos réclamations',
            'icon' => 'reclamation',
            'available' => true
        ]
    ];

    // ✅ Debug des calculs
    Log::info('Calculs retraité Dashboard:', [
        'pension_mensuelle' => $pensionMensuelle,
        'mois_retraite' => $moisRetraite,
        'total_percu_calcule' => $totalPercu,
        'total_percu_arrondi' => round($totalPercu),
        'date_retraite' => $retraite->date_retraite
    ]);

    return response()->json([
        'success' => true,
        'user_type' => 'retraite',
        'user' => [
            'id' => $retraite->id,
            'numero_pension' => $retraite->numero_pension,
            'nom_complet' => $retraite->prenoms . ' ' . $retraite->nom,
            'nom' => $retraite->nom,
            'prenoms' => $retraite->prenoms,
            'sexe' => $retraite->sexe,
            'email' => $retraite->email,
            'telephone' => $retraite->telephone,
            'montant_pension' => round($retraite->montant_pension ?? 0),
            'date_retraite' => $retraite->date_retraite,
            'annees_retraite' => $anneesRetraite,
            'mois_retraite' => $moisRetraite, // ✅ Ajout
            'email_verified' => !is_null($retraite->email_verified_at),
            'phone_verified' => !is_null($retraite->phone_verified_at),
        ],
        'dashboard' => [
            'stats' => $stats,
            'activites_recentes' => $activites,
            'services_disponibles' => $services,
            'notifications_rdv' => $notificationsRdv
        ]
    ]);
}
    // NOUVELLES MÉTHODES POUR DONNÉES DYNAMIQUES

    /**
     * VERSION CORRIGÉE : Obtenir les activités récentes pour agents actifs
     */
    private function getActivitesRecentesDynamiquesFixed($agent)
    {
        $activites = collect();
        
        try {
            \Log::info('Début récupération activités agent dynamiques', [
                'agent_id' => $agent->id
            ]);

            // 1. ACTIVITÉS DES COTISATIONS (depuis carrieres)
            try {
                $carrieres = $agent->carrieresValides()
                    ->orderBy('date_debut', 'desc')
                    ->limit(3)
                    ->get();
                    
                foreach ($carrieres as $carriere) {
                    $activites->push([
                        'id' => 'carriere_' . $carriere->id,
                        'type' => 'cotisation',
                        'description' => "Cotisation enregistrée - " . 
                            number_format($carriere->retenue, 0, ',', ' ') . " FCFA" .
                            " (Grade: " . ($carriere->grade ?? 'N/A') . ")",
                        'date' => $carriere->date_debut,
                        'status' => $this->mapStatutCarriereToStatus($carriere->statut),
                        'metadata' => [
                            'montant' => $carriere->retenue,
                            'grade' => $carriere->grade,
                            'indice' => $carriere->indice,
                            'periode' => $carriere->date_debut->format('m/Y')
                        ]
                    ]);
                }
                
                \Log::info('Activités cotisations ajoutées', [
                    'count' => $carrieres->count()
                ]);
            } catch (\Exception $e) {
                \Log::error('Erreur récupération carrières:', [
                    'error' => $e->getMessage()
                ]);
            }

            // 2. ACTIVITÉS DES SIMULATIONS
            try {
                if (class_exists('\App\Models\SimulationPension')) {
                    $simulationsRecentes = \App\Models\SimulationPension::where('agent_id', $agent->id)
                        ->orderBy('created_at', 'desc')
                        ->limit(2)
                        ->get();
                    
                    foreach ($simulationsRecentes as $simulation) {
                        $montantPension = $simulation->pension_apres_coefficient ?? $simulation->pension_totale ?? 0;
                        $montantFormate = number_format($montantPension, 0, ',', ' ') . ' FCFA';
                        
                        $activites->push([
                            'id' => 'simulation_' . $simulation->id,
                            'type' => 'simulation',
                            'description' => "Simulation de pension réalisée - Pension estimée: " . $montantFormate,
                            'date' => $simulation->created_at,
                            'status' => 'completed',
                            'metadata' => [
                                'pension_estimee' => $montantPension,
                                'duree_service' => $simulation->duree_service_simulee,
                                'date_retraite_prevue' => $simulation->date_retraite_prevue
                            ]
                        ]);
                    }
                    
                    \Log::info('Activités simulations ajoutées', [
                        'count' => $simulationsRecentes->count()
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Erreur récupération simulations:', [
                    'error' => $e->getMessage()
                ]);
            }

            // 3. ACTIVITÉS DES RENDEZ-VOUS
            try {
                if (class_exists('\App\Models\RendezVousDemande')) {
                    $rdvRecents = \App\Models\RendezVousDemande::where('user_id', $agent->id)
                        ->where('user_type', 'agent')
                        ->orderBy('date_soumission', 'desc')
                        ->limit(3)
                        ->get();
                    
                    foreach ($rdvRecents as $rdv) {
                        $activites->push([
                            'id' => 'rdv_' . $rdv->id,
                            'type' => 'rendez_vous',
                            'description' => $this->getDescriptionActiviteRdv($rdv),
                            'date' => $rdv->date_soumission,
                            'status' => $this->mapStatutRdvToStatus($rdv->statut),
                            'metadata' => [
                                'numero_demande' => $rdv->numero_demande,
                                'motif' => $rdv->motif_complet ?? $rdv->motif,
                                'statut' => $rdv->statut,
                                'date_demandee' => $rdv->date_demandee->format('d/m/Y'),
                            ]
                        ]);
                    }
                    
                    \Log::info('Activités RDV ajoutées', [
                        'count' => $rdvRecents->count()
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Erreur récupération RDV:', [
                    'error' => $e->getMessage()
                ]);
            }

            // 4. ACTIVITÉS DES RÉCLAMATIONS
            try {
                if (class_exists('\App\Models\Reclamation')) {
                    $reclamationsRecentes = \App\Models\Reclamation::where('user_id', $agent->id)
                        ->where('user_type', 'agent')
                        ->orderBy('created_at', 'desc')
                        ->limit(2)
                        ->get();
                    
                    foreach ($reclamationsRecentes as $reclamation) {
                        $activites->push([
                            'id' => 'reclamation_' . $reclamation->id,
                            'type' => 'reclamation',
                            'description' => $this->getDescriptionActiviteReclamation($reclamation),
                            'date' => $reclamation->created_at,
                            'status' => $this->mapStatutReclamationToStatus($reclamation->statut),
                            'metadata' => [
                                'numero_reclamation' => $reclamation->numero_reclamation,
                                'type_reclamation' => $reclamation->type_reclamation,
                                'statut' => $reclamation->statut
                            ]
                        ]);
                    }
                    
                    \Log::info('Activités réclamations ajoutées', [
                        'count' => $reclamationsRecentes->count()
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Erreur récupération réclamations:', [
                    'error' => $e->getMessage()
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Erreur globale récupération activités agent:', [
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        // Si pas assez d'activités réelles, ajouter des exemples
        if ($activites->count() < 3) {
            $activites = $activites->merge($this->getActivitesComplementairesAgent($agent));
        }
        
        $result = $activites->sortByDesc('date')->take(5)->values()->toArray();
        
        \Log::info('Activités finales agent retournées', [
            'count' => count($result),
            'types' => array_count_values(array_column($result, 'type'))
        ]);
        
        return $result;
    }

    /**
     * VERSION CORRIGÉE : Obtenir les activités récentes pour retraités
     */
    private function getActivitesRecentesRetraiteFixed($retraite)
    {
        $activites = collect();
        
        try {
            \Log::info('Début récupération activités retraité', [
                'retraite_id' => $retraite->id
            ]);

            // 1. ACTIVITÉS DES PENSIONS (historique paiements)
            try {
                if (class_exists('\App\Models\HistoriquePaiement')) {
                    $paiementsRecents = \App\Models\HistoriquePaiement::where('retraite_id', $retraite->id)
                        ->where('etat_paiement', 'verse')
                        ->orderBy('date_paiement', 'desc')
                        ->limit(3)
                        ->get();
                    
                    foreach ($paiementsRecents as $paiement) {
                        $activites->push([
                            'id' => 'paiement_' . $paiement->id,
                            'type' => 'pension',
                            'description' => "Pension versée - " . 
                                number_format($paiement->montant_net, 0, ',', ' ') . " FCFA" .
                                " (Mois: " . $paiement->mois_paiement . "/" . $paiement->annee_paiement . ")",
                            'date' => $paiement->date_paiement,
                            'status' => 'completed',
                            'metadata' => [
                                'montant' => $paiement->montant_net,
                                'mois' => $paiement->mois_paiement,
                                'annee' => $paiement->annee_paiement,
                                'reference' => $paiement->reference_paiement
                            ]
                        ]);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Erreur récupération paiements retraité:', [
                    'error' => $e->getMessage()
                ]);
                
                // Fallback : pension mensuelle simulée
                $activites->push([
                    'id' => 'pension_mensuelle_' . now()->format('Ym'),
                    'type' => 'pension',
                    'description' => "Pension mensuelle - " . 
                        number_format($retraite->montant_pension ?? 0, 0, ',', ' ') . " FCFA",
                    'date' => now()->subDays(rand(1, 5)),
                    'status' => 'completed',
                    'metadata' => [
                        'montant' => $retraite->montant_pension ?? 0
                    ]
                ]);
            }

            // 2. ACTIVITÉS DES DOCUMENTS
            try {
                if (class_exists('\App\Models\DocumentRetraite')) {
                    $documentsRecents = \App\Models\DocumentRetraite::where('retraite_id', $retraite->id)
                        ->where('statut', 'actif')
                        ->orderBy('date_depot', 'desc')
                        ->limit(3)
                        ->get();
                    
                    foreach ($documentsRecents as $document) {
                        $activites->push([
                            'id' => 'document_' . $document->id,
                            'type' => 'document',
                            'description' => $this->getDescriptionActiviteDocument($document),
                            'date' => $document->date_depot,
                            'status' => $this->mapStatutDocumentToStatus($document),
                            'metadata' => [
                                'type_document' => $document->type_document,
                                'nom_type' => $document->nom_type,
                                'date_expiration' => $document->date_expiration?->format('d/m/Y')
                            ]
                        ]);
                    }
                    
                    \Log::info('Activités documents ajoutées', [
                        'count' => $documentsRecents->count()
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Erreur récupération documents retraité:', [
                    'error' => $e->getMessage()
                ]);
            }

            // 3. ACTIVITÉS DES RENDEZ-VOUS (pour retraités)
            try {
                if (class_exists('\App\Models\RendezVousDemande')) {
                    $rdvRecents = \App\Models\RendezVousDemande::where('user_id', $retraite->id)
                        ->where('user_type', 'retraite')
                        ->orderBy('date_soumission', 'desc')
                        ->limit(2)
                        ->get();
                    
                    foreach ($rdvRecents as $rdv) {
                        $activites->push([
                            'id' => 'rdv_' . $rdv->id,
                            'type' => 'rendez_vous',
                            'description' => $this->getDescriptionActiviteRdv($rdv),
                            'date' => $rdv->date_soumission,
                            'status' => $this->mapStatutRdvToStatus($rdv->statut),
                            'metadata' => [
                                'numero_demande' => $rdv->numero_demande,
                                'motif' => $rdv->motif_complet ?? $rdv->motif,
                                'statut' => $rdv->statut
                            ]
                        ]);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Erreur récupération RDV retraité:', [
                    'error' => $e->getMessage()
                ]);
            }

            // 4. ACTIVITÉS DES RÉCLAMATIONS (pour retraités)
            try {
                if (class_exists('\App\Models\Reclamation')) {
                    $reclamationsRecentes = \App\Models\Reclamation::where('user_id', $retraite->id)
                        ->where('user_type', 'retraite')
                        ->orderBy('created_at', 'desc')
                        ->limit(2)
                        ->get();
                    
                    foreach ($reclamationsRecentes as $reclamation) {
                        $activites->push([
                            'id' => 'reclamation_' . $reclamation->id,
                            'type' => 'reclamation',
                            'description' => $this->getDescriptionActiviteReclamation($reclamation),
                            'date' => $reclamation->created_at,
                            'status' => $this->mapStatutReclamationToStatus($reclamation->statut),
                            'metadata' => [
                                'numero_reclamation' => $reclamation->numero_reclamation,
                                'type_reclamation' => $reclamation->type_reclamation
                            ]
                        ]);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Erreur récupération réclamations retraité:', [
                    'error' => $e->getMessage()
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Erreur globale récupération activités retraité:', [
                'retraite_id' => $retraite->id,
                'error' => $e->getMessage()
            ]);
        }

        // Si pas assez d'activités, ajouter des activités de base
        if ($activites->count() < 3) {
            $activites = $activites->merge($this->getActivitesComplementairesRetraite($retraite));
        }

        $result = $activites->sortByDesc('date')->take(5)->values()->toArray();
        
        \Log::info('Activités finales retraité retournées', [
            'count' => count($result),
            'types' => array_count_values(array_column($result, 'type'))
        ]);

        return $result;
    }

    // MÉTHODES UTILITAIRES

    /**
     * Compter le nombre de rendez-vous pris par l'agent
     */
    private function compterRendezVousPris($agent)
    {
        try {
            if (class_exists('\App\Models\RendezVousDemande')) {
                $userType = $agent instanceof Agent ? 'agent' : 'retraite';
                return \App\Models\RendezVousDemande::where('user_id', $agent->id)
                    ->where('user_type', $userType)
                    ->whereIn('statut', ['accepte', 'en_attente', 'reporte'])
                    ->count();
            }
            return 0;
        } catch (\Exception $e) {
            \Log::error('Erreur comptage RDV:', ['user_id' => $agent->id, 'error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Compter le nombre total de réclamations
     */
    private function compterReclamations($agent)
    {
        try {
            if (class_exists('\App\Models\Reclamation')) {
                $userType = $agent instanceof Agent ? 'agent' : 'retraite';
                return \App\Models\Reclamation::where('user_id', $agent->id)
                    ->where('user_type', $userType)
                    ->count();
            }
            return 0;
        } catch (\Exception $e) {
            \Log::error('Erreur comptage réclamations:', ['user_id' => $agent->id, 'error' => $e->getMessage()]);
            return 0;
        }
    }

    private function compterAttestationsDemandees($agent)
    {
        try {
            if (class_exists('\App\Models\SimulationPension')) {
                return \App\Models\SimulationPension::where('agent_id', $agent->id)->count();
            }
            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function compterDossiersEnCours($agent)
    {
        $dossiers = 0;
        
        try {
            $userType = $agent instanceof Agent ? 'agent' : 'retraite';
            
            if (class_exists('\App\Models\Reclamation')) {
                $dossiers += \App\Models\Reclamation::where('user_id', $agent->id)
                    ->where('user_type', $userType)
                    ->whereIn('statut', ['en_attente', 'en_cours', 'en_revision'])
                    ->count();
            }
            
            if (class_exists('\App\Models\RendezVousDemande')) {
                $dossiers += \App\Models\RendezVousDemande::where('user_id', $agent->id)
                    ->where('user_type', $userType)
                    ->where('statut', 'en_attente')
                    ->count();
            }
        } catch (\Exception $e) {
            \Log::error('Erreur comptage dossiers en cours:', ['error' => $e->getMessage()]);
        }
        
        return $dossiers;
    }

    private function compterCertificatsValidesReel($retraite)
    {
        try {
            if (class_exists('\App\Models\DocumentRetraite')) {
                return \App\Models\DocumentRetraite::where('retraite_id', $retraite->id)
                    ->where('type_document', 'certificat_vie')
                    ->where('statut', 'actif')
                    ->whereDate('date_expiration', '>', now())
                    ->count();
            }
            return 1; // Valeur par défaut
        } catch (\Exception $e) {
            return 1;
        }
    }

    // MÉTHODES DE MAPPING

    /**
     * Mapper le statut de carrière vers le statut d'activité
     */
    private function mapStatutCarriereToStatus($statut)
    {
        switch (strtoupper($statut ?? '')) {
            case 'VALIDE':
                return 'completed';
            case 'SUSPENDU':
                return 'warning';
            case 'ANNULE':
                return 'warning';
            default:
                return 'completed';
        }
    }

    /**
     * Mapper le statut de cotisation vers le statut d'activité
     */
    private function mapStatutCotisationToStatus($statut)
    {
        switch ($statut) {
            case 'actif':
                return 'completed';
            case 'valide':
                return 'completed';
            case 'suspendu':
                return 'warning';
            case 'annule':
                return 'warning';
            default:
                return 'pending';
        }
    }

    /**
     * Mapper le statut de réclamation vers le statut d'activité
     */
    private function mapStatutReclamationToStatus($statut)
    {
        switch ($statut) {
            case 'en_attente':
                return 'pending';
            case 'en_cours':
            case 'en_revision':
                return 'in_progress';
            case 'resolu':
            case 'ferme':
                return 'completed';
            case 'rejete':
                return 'warning';
            default:
                return 'pending';
        }
    }

    /**
     * Mapper le statut de RDV vers le statut d'activité
     */
    private function mapStatutRdvToStatus($statut)
    {
        switch ($statut) {
            case 'en_attente':
                return 'pending';
            case 'accepte':
                return 'completed';
            case 'refuse':
            case 'annule':
                return 'warning';
            case 'reporte':
                return 'in_progress';
            default:
                return 'pending';
        }
    }

    /**
     * Mapper le statut de document vers le statut d'activité
     */
    private function mapStatutDocumentToStatus($document)
    {
        if ($document->type_document === 'certificat_vie') {
            if ($document->is_expire) {
                return 'warning'; // Document expiré
            } elseif ($document->expire_bientot) {
                return 'in_progress'; // Expire bientôt
            } else {
                return 'completed'; // Valide
            }
        }
        
        return 'completed'; // Autres documents
    }

    // MÉTHODES DE DESCRIPTION

    /**
     * Générer la description d'une activité de réclamation
     */
    private function getDescriptionActiviteReclamation($reclamation)
    {
        $typeNoms = [
            'cotisation' => 'Cotisations',
            'pension' => 'Pension',
            'prestation' => 'Prestations',
            'document' => 'Documents',
            'autre' => 'Autre'
        ];
        
        $typeNom = $typeNoms[$reclamation->type_reclamation] ?? 'Réclamation';
        
        switch ($reclamation->statut) {
            case 'en_attente':
                return "Réclamation déposée: {$typeNom} (N° {$reclamation->numero_reclamation})";
            case 'en_cours':
                return "Réclamation en cours de traitement: {$typeNom}";
            case 'resolu':
                return "Réclamation résolue: {$typeNom}";
            case 'ferme':
                return "Réclamation fermée: {$typeNom}";
            case 'rejete':
                return "Réclamation rejetée: {$typeNom}";
            default:
                return "Réclamation: {$typeNom}";
        }
    }

    /**
     * Générer la description d'une activité de rendez-vous
     */
    private function getDescriptionActiviteRdv($rdv)
    {
        switch ($rdv->statut) {
            case 'en_attente':
                return "Demande de rendez-vous déposée (N° {$rdv->numero_demande})";
            case 'accepte':
                if ($rdv->date_rdv_confirme) {
                    return "Rendez-vous confirmé pour le " . $rdv->date_rdv_confirme->format('d/m/Y');
                }
                return "Rendez-vous accepté (N° {$rdv->numero_demande})";
            case 'refuse':
                return "Demande de rendez-vous refusée (N° {$rdv->numero_demande})";
            case 'reporte':
                return "Rendez-vous reporté (N° {$rdv->numero_demande})";
            case 'annule':
                return "Rendez-vous annulé (N° {$rdv->numero_demande})";
            default:
                return "Rendez-vous (N° {$rdv->numero_demande})";
        }
    }

    /**
     * Générer la description d'une activité de document
     */
    private function getDescriptionActiviteDocument($document)
    {
        $typeNom = $document->nom_type ?? $document->type_document;
        $nomFichier = Str::limit($document->nom_original, 30);
        
        $description = "Document déposé: {$typeNom} - {$nomFichier}";
        
        // Ajouter des informations sur l'état du document
        if ($document->type_document === 'certificat_vie') {
            if ($document->is_expire) {
                $description .= " (EXPIRÉ)";
            } elseif ($document->expire_bientot) {
                $jours = $document->jours_avant_expiration;
                $description .= " (expire dans {$jours}j)";
            } else {
                $description .= " (valide)";
            }
        }
        
        return $description;
    }

    // MÉTHODES D'ACTIVITÉS COMPLÉMENTAIRES

    /**
     * Activités complémentaires pour agents si pas assez d'activités réelles
     */
    private function getActivitesComplementairesAgent($agent)
    {
        $activitesComplementaires = collect();
        
        // Ajouter la dernière cotisation si elle existe
        if ($agent->derniere_cotisation_date) {
            $activitesComplementaires->push([
                'id' => 'derniere_cotisation',
                'type' => 'cotisation',
                'description' => 'Dernière cotisation enregistrée - ' . 
                    number_format($agent->retenue_mensuelle ?? 0, 0, ',', ' ') . ' FCFA',
                'date' => $agent->derniere_cotisation_date,
                'status' => 'completed'
            ]);
        }
        
        // Ajouter des activités exemple si vraiment aucune donnée
        if ($activitesComplementaires->isEmpty()) {
            $activitesComplementaires->push([
                'id' => 'activite_exemple',
                'type' => 'cotisation',
                'description' => 'Cotisation mensuelle active - ' . 
                    number_format($agent->retenue_mensuelle ?? 45000, 0, ',', ' ') . ' FCFA',
                'date' => now()->subDays(15),
                'status' => 'completed'
            ]);
        }
        
        return $activitesComplementaires;
    }

    /**
     * Activités complémentaires pour retraités si pas assez d'activités réelles
     */
    private function getActivitesComplementairesRetraite($retraite)
    {
        $activitesComplementaires = collect();
        
        // Pension mensuelle par défaut
        $activitesComplementaires->push([
            'id' => 'pension_mensuelle_' . now()->format('Ym'),
            'type' => 'pension',
            'description' => 'Pension mensuelle - ' . 
                number_format($retraite->montant_pension ?? 0, 0, ',', ' ') . ' FCFA',
            'date' => now()->subDays(rand(1, 5)),
            'status' => 'completed',
            'metadata' => [
                'montant' => $retraite->montant_pension ?? 0
            ]
        ]);

        // Certificat de vie si applicable
        $activitesComplementaires->push([
            'id' => 'certificat_vie_rappel',
            'type' => 'certificat',
            'description' => 'Certificat de vie valide',
            'date' => now()->subDays(rand(30, 60)),
            'status' => 'completed'
        ]);
        
        return $activitesComplementaires;
    }

    // NOTIFICATIONS RDV

    /**
     * ✅ CORRECTION ÉGALEMENT POUR LES RDV EN ATTENTE
     */
    private function getRdvNotifications($user)
    {
        $notifications = [];
        
        try {
            $userType = $user instanceof Agent ? 'agent' : 'retraite';
            
            // RDV confirmés à venir (limité à 3 pour éviter l'encombrement)
            $rdvConfirmes = \App\Models\RendezVousDemande::where('user_id', $user->id)
                ->where('user_type', $userType)
                ->where('statut', 'accepte')
                ->whereNotNull('date_rdv_confirme')
                ->where('date_rdv_confirme', '>', now())
                ->orderBy('date_rdv_confirme')
                ->limit(3)
                ->get();

            foreach ($rdvConfirmes as $rdv) {
                $notification = $this->createRdvNotification($rdv);
                if ($notification) {
                    $notifications[] = $notification;
                }
            }

            // ✅ RDV en attente AVEC DATE/HEURE ORIGINALE
            $rdvEnAttente = \App\Models\RendezVousDemande::where('user_id', $user->id)
                ->where('user_type', $userType)
                ->where('statut', 'en_attente')
                ->orderBy('date_soumission', 'desc')
                ->limit(2)
                ->get();

            foreach ($rdvEnAttente as $rdv) {
                $notifications[] = [
                    'id' => 'rdv_attente_' . $rdv->id,
                    'type' => 'rdv_attente',
                    'titre' => 'Demande en attente',
                    'message' => "Votre demande de RDV n° {$rdv->numero_demande} est en cours d'examen",
                    'date_creation' => $rdv->date_soumission,
                    
                    // ✅ AJOUT DES CHAMPS DATE/HEURE DEMANDÉES ORIGINALES
                    'date_rdv' => $rdv->date_demandee->format('Y-m-d'),
                    'heure_rdv' => $rdv->heure_demandee, // Déjà au format HH:MM
                    
                    'numero_demande' => $rdv->numero_demande,
                    'priorite' => 'normale',
                    'couleur' => '#F59E0B',
                    'icone' => '⏳',
                    'motif' => $rdv->motif_complet,
                    'lieu_rdv' => null, // Pas encore défini
                    'actions' => [
                        [
                            'label' => 'Voir détails',
                            'url' => $userType === 'agent' ? '/actifs/rendez-vous' : '/retraites/rendez-vous',
                            'type' => 'primary'
                        ]
                    ]
                ];
            }

        } catch (\Exception $e) {
            \Log::error('Erreur notifications RDV:', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            // Retourner un format par défaut en cas d'erreur
            return [
                'notifications' => [],
                'totaux' => [
                    'rdv_confirmes' => 0,
                    'rdv_en_attente' => 0,
                    'total' => 0
                ],
                'affichage_limite' => false
            ];
        }

        // Ajouter un compteur total si plus de notifications
        $totalRdvConfirmes = 0;
        $totalRdvEnAttente = 0;
        
        try {
            $userType = $user instanceof Agent ? 'agent' : 'retraite';
            
            $totalRdvConfirmes = \App\Models\RendezVousDemande::where('user_id', $user->id)
                ->where('user_type', $userType)
                ->where('statut', 'accepte')
                ->whereNotNull('date_rdv_confirme')
                ->where('date_rdv_confirme', '>', now())
                ->count();
                
            $totalRdvEnAttente = \App\Models\RendezVousDemande::where('user_id', $user->id)
                ->where('user_type', $userType)
                ->where('statut', 'en_attente')
                ->count();
        } catch (\Exception $e) {
            // Ignorer les erreurs de comptage
        }

        return [
            'notifications' => $notifications,
            'totaux' => [
                'rdv_confirmes' => $totalRdvConfirmes,
                'rdv_en_attente' => $totalRdvEnAttente,
                'total' => $totalRdvConfirmes + $totalRdvEnAttente
            ],
            'affichage_limite' => count($notifications) < ($totalRdvConfirmes + $totalRdvEnAttente)
        ];
    }

    /**
     * Créer une notification pour un RDV confirmé - VERSION CORRIGÉE AVEC DATE/HEURE
     */
    private function createRdvNotification($rdv)
    {
        $dateRdv = $rdv->date_rdv_confirme;
        $maintenant = now();
        
        $diffEnJours = $maintenant->diffInDays($dateRdv, false);
        $diffEnHeures = $maintenant->diffInHours($dateRdv, false);
        
        if ($diffEnJours < 0) {
            return null;
        }
        
        $urgence = $this->determinerUrgenceRdv($diffEnJours, $diffEnHeures);
        
        return [
            'id' => 'rdv_confirme_' . $rdv->id,
            'type' => 'rdv_confirme',
            'titre' => $urgence['titre'],
            'message' => $this->formatMessageRdvCompact($rdv, $diffEnJours, $diffEnHeures),
            'date_creation' => $rdv->date_rdv_confirme,
            
            // ✅ AJOUT DES CHAMPS MANQUANTS POUR LE FRONTEND
            'date_rdv' => $dateRdv->format('Y-m-d'), // Format pour JavaScript
            'heure_rdv' => $dateRdv->format('H:i'),  // Format HH:MM
            
            // Autres champs existants
            'priorite' => $urgence['priorite'],
            'couleur' => $urgence['couleur'],
            'icone' => $urgence['icone'],
            'delai_jours' => $diffEnJours,
            'delai_heures' => $diffEnHeures,
            'numero_demande' => $rdv->numero_demande,
            'lieu_rdv' => $rdv->lieu_rdv,
            'motif' => $rdv->motif_complet,
            'actions' => [
                [
                    'label' => 'Voir détails',
                    'url' => ($rdv->user_type === 'agent') ? '/actifs/rendez-vous' : '/retraites/rendez-vous',
                    'type' => 'primary'
                ]
            ]
        ];
    }

    /**
     * Message compact pour éviter l'encombrement
     */
    private function formatMessageRdvCompact($rdv, $jours, $heures)
    {
        $dateFormatee = $rdv->date_rdv_confirme->format('d/m à H:i');
        
        if ($heures <= 2) {
            $delai = "dans " . ceil($heures) . "h";
        } elseif ($jours <= 1) {
            $delai = "demain";
        } elseif ($jours <= 7) {
            $delai = "dans " . ceil($jours) . "j";
        } else {
            $delai = "le " . $rdv->date_rdv_confirme->format('d/m');
        }
        
        return "RDV {$delai} ({$dateFormatee})";
    }

    /**
     * Déterminer l'urgence d'un RDV selon le délai
     */
    private function determinerUrgenceRdv($jours, $heures)
    {
        if ($heures <= 2) {
            return [
                'titre' => '🚨 RDV IMMINENT',
                'priorite' => 'critique',
                'couleur' => '#DC2626',
                'icone' => '🚨'
            ];
        } elseif ($heures <= 6) {
            return [
                'titre' => '⚡ RDV Aujourd\'hui',
                'priorite' => 'urgent',
                'couleur' => '#EA580C',
                'icone' => '⚡'
            ];
        } elseif ($jours <= 1) {
            return [
                'titre' => '🔥 RDV Demain',
                'priorite' => 'urgent',
                'couleur' => '#F59E0B',
                'icone' => '🔥'
            ];
        } elseif ($jours <= 3) {
            return [
                'titre' => '⚠️ RDV Cette Semaine',
                'priorite' => 'haute',
                'couleur' => '#EAB308',
                'icone' => '⚠️'
            ];
        } else {
            return [
                'titre' => '📅 RDV Programmé',
                'priorite' => 'normale',
                'couleur' => '#3B82F6',
                'icone' => '📅'
            ];
        }
    }

    // MÉTHODES API EXISTANTES

    public function getAttestations(Request $request)
    {
        $attestations = [
            [
                'id' => 1,
                'type' => 'cotisations',
                'titre' => 'Attestation de Cotisations 2024',
                'date_creation' => now()->subDays(10),
                'status' => 'disponible',
                'url_download' => '/api/attestations/1/download'
            ],
            [
                'id' => 2,
                'type' => 'emploi',
                'titre' => 'Attestation d\'Emploi',
                'date_creation' => now()->subDays(30),
                'status' => 'disponible',
                'url_download' => '/api/attestations/2/download'
            ]
        ];

        return response()->json([
            'success' => true,
            'attestations' => $attestations
        ]);
    }

    public function requestAttestation(Request $request)
    {
        $request->validate([
            'type' => 'required|in:cotisations,emploi,pension',
            'motif' => 'required|string|max:500'
        ]);

        $attestation = [
            'id' => rand(1000, 9999),
            'type' => $request->type,
            'titre' => 'Attestation de ' . ucfirst($request->type) . ' - ' . now()->format('Y-m-d'),
            'date_creation' => now(),
            'status' => 'en_cours',
            'motif' => $request->motif,
            'url_download' => null
        ];

        return response()->json([
            'success' => true,
            'message' => 'Votre demande d\'attestation a été soumise avec succès',
            'attestation' => $attestation
        ]);
    }

    /**
     * Obtenir les cotisations réelles (agents actifs)
     */
    public function getCotisations(Request $request)
    {
        $agent = $request->user();
        
        try {
            $cotisations = $agent->carrieresValides()
                               ->orderBy('date_debut', 'desc')
                               ->paginate(10);

            // Statistiques depuis carrieres uniquement
            $totalCotisations = $agent->carrieresValides()->sum('total_cotisations');
            $retenueMoyenne = $agent->carrieresValides()->avg('retenue') ?? 0;
            $nombrePeriodes = $agent->carrieresValides()->count();
            $dureeServiceMois = $agent->carrieresValides()->sum('nombre_mois');

            $statistiques = [
                'total_cotisations' => $totalCotisations,
                'retenue_mensuelle_actuelle' => $agent->retenue_mensuelle ?? 0,
                'nombre_periodes' => $nombrePeriodes,
                'duree_service_total' => $dureeServiceMois,
                'cotisation_moyenne' => $retenueMoyenne,
                'premiere_cotisation' => $agent->carrieresValides()
                                             ->orderBy('date_debut', 'asc')
                                             ->first()?->date_debut?->format('d/m/Y'),
                'derniere_cotisation' => $agent->carrieresValides()
                                             ->orderBy('date_debut', 'desc')
                                             ->first()?->date_debut?->format('d/m/Y'),
                'droit_pension' => ($dureeServiceMois >= 180) ? 'OUI' : 'NON'
            ];

            // Données pour graphique des 6 derniers mois
            $graphique = [];
            for ($i = 5; $i >= 0; $i--) {
                $mois = now()->subMonths($i);
                $carriere = $agent->carrieresValides()
                                  ->where('date_debut', '<=', $mois->endOfMonth())
                                  ->where(function($q) use ($mois) {
                                      $q->whereNull('date_fin')
                                        ->orWhere('date_fin', '>=', $mois->startOfMonth());
                                  })
                                  ->first();
                
                $graphique[] = [
                    'mois' => $mois->format('M Y'),
                    'retenue' => $carriere ? $carriere->retenue : 0,
                    'grade' => $carriere ? $carriere->grade : null,
                    'statut' => $carriere ? $carriere->statut : 'aucune'
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'agent_info' => [
                        'nom_complet' => $agent->prenoms . ' ' . $agent->nom,
                        'matricule_solde' => $agent->matricule_solde,
                        'num_affiliation' => $agent->num_affiliation,
                        'grade_actuel' => $agent->grade,
                        'indice_actuel' => $agent->indice,
                        'statut' => $agent->status
                    ],
                    'cotisations' => $cotisations->items(),
                    'pagination' => [
                        'current_page' => $cotisations->currentPage(),
                        'per_page' => $cotisations->perPage(),
                        'total' => $cotisations->total(),
                        'last_page' => $cotisations->lastPage()
                    ],
                    'statistiques' => $statistiques,
                    'graphique' => $graphique,
                    'resume' => [
                        'total_cotisations_formatees' => number_format($statistiques['total_cotisations'], 0, ',', ' ') . ' FCFA',
                        'retenue_actuelle_formatee' => number_format($statistiques['retenue_mensuelle_actuelle'], 0, ',', ' ') . ' FCFA',
                        'duree_service_formatee' => $this->formatDureeService($statistiques['duree_service_total']),
                        'est_eligible_pension' => $statistiques['droit_pension'] === 'OUI'
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur récupération cotisations:', [
                'agent_id' => $agent->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des cotisations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Formater la durée de service
     */
    private function formatDureeService($dureeEnMois)
    {
        if (!$dureeEnMois) return '0 mois';
        
        $annees = floor($dureeEnMois / 12);
        $mois = $dureeEnMois % 12;
        
        $result = [];
        if ($annees > 0) {
            $result[] = $annees . ($annees > 1 ? ' ans' : ' an');
        }
        if ($mois > 0) {
            $result[] = $mois . ' mois';
        }
        
        return implode(' ', $result) ?: '0 mois';
    }

    public function getCarriere(Request $request)
    {
        $agent = $request->user();
        
        $carriere = [
            'informations_generales' => [
                'date_prise_service' => $agent->date_prise_service,
                'grade_actuel' => $agent->grade,
                'poste_actuel' => $agent->poste,
                'direction_actuelle' => $agent->direction,
                'anciennete' => Carbon::parse($agent->date_prise_service)->diffInYears(now()) . ' ans'
            ],
            'historique_postes' => [
                [
                    'poste' => $agent->poste,
                    'grade' => $agent->grade,
                    'date_debut' => $agent->date_prise_service,
                    'date_fin' => null,
                    'status' => 'actuel'
                ]
            ]
        ];

        return response()->json([
            'success' => true,
            'carriere' => $carriere
        ]);
    }

    public function getDocuments(Request $request)
    {
        $documents = [
            [
                'id' => 1,
                'nom' => 'Attestation de Cotisations 2024',
                'type' => 'attestation',
                'date_creation' => now()->subDays(10),
                'url_download' => '/api/documents/1/download'
            ]
        ];

        return response()->json([
            'success' => true,
            'documents' => $documents
        ]);
    }

    public function uploadDocument(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'type' => 'required|string',
            'description' => 'required|string|max:255'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document téléchargé avec succès'
        ]);
    }

    public function getNotifications(Request $request)
    {
        $notifications = [
            [
                'id' => 1,
                'type' => 'info',
                'message' => 'Votre attestation est prête',
                'date' => now()->subDays(2),
                'read' => false
            ]
        ];

        return response()->json([
            'success' => true,
            'notifications' => $notifications
        ]);
    }

    public function markNotificationRead(Request $request, $id)
    {
        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue'
        ]);
    }

    // MÉTHODES POUR RETRAITÉS

    public function getPensionInfo(Request $request)
    {
        $retraite = $request->user();
        
        $versements = [
            [
                'mois' => 'Juin 2025',
                'montant' => $retraite->montant_pension,
                'date_versement' => '2025-06-05',
                'status' => 'verse'
            ],
            [
                'mois' => 'Mai 2025',
                'montant' => $retraite->montant_pension,
                'date_versement' => '2025-05-05',
                'status' => 'verse'
            ],
            [
                'mois' => 'Avril 2025',
                'montant' => $retraite->montant_pension,
                'date_versement' => '2025-04-05',
                'status' => 'verse'
            ]
        ];

        return response()->json([
            'success' => true,
            'pension_info' => [
                'montant_mensuel' => $retraite->montant_pension,
                'prochaine_date' => '2025-07-05',
                'versements_recents' => $versements
            ]
        ]);
    }

    public function getPensionHistorique(Request $request)
    {
        $retraite = $request->user();
        
        $historique = [];
        for ($i = 0; $i < 12; $i++) {
            $date = now()->subMonths($i);
            $historique[] = [
                'mois' => $date->format('F Y'),
                'montant' => $retraite->montant_pension,
                'date_versement' => $date->format('Y-m-d'),
                'status' => 'verse'
            ];
        }

        return response()->json([
            'success' => true,
            'historique' => $historique
        ]);
    }

    public function getCertificatsVie(Request $request)
    {
        $certificats = [
            [
                'id' => 1,
                'date_soumission' => now()->subDays(30),
                'status' => 'valide',
                'date_expiration' => now()->addDays(90),
                'autorite' => 'Mairie de Libreville'
            ],
            [
                'id' => 2,
                'date_soumission' => now()->subDays(150),
                'status' => 'expire',
                'date_expiration' => now()->subDays(30),
                'autorite' => 'Mairie de Libreville'
            ]
        ];

        return response()->json([
            'success' => true,
            'certificats' => $certificats
        ]);
    }

    public function submitCertificatVie(Request $request)
    {
        $request->validate([
            'certificat' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'autorite' => 'required|string|max:255',
            'date_emission' => 'required|date'
        ]);

        $certificat = [
            'id' => rand(1000, 9999),
            'date_soumission' => now(),
            'status' => 'en_cours',
            'date_expiration' => now()->addDays(120),
            'autorite' => $request->autorite
        ];

        return response()->json([
            'success' => true,
            'message' => 'Certificat de vie soumis avec succès',
            'certificat' => $certificat
        ]);
    }

    public function getCertificatStatus(Request $request, $id)
    {
        $certificat = [
            'id' => $id,
            'status' => 'valide',
            'date_validation' => now()->subDays(5),
            'commentaire' => 'Certificat validé avec succès'
        ];

        return response()->json([
            'success' => true,
            'certificat' => $certificat
        ]);
    }

    public function getAttestationsRetraite(Request $request)
    {
        $attestations = [
            [
                'id' => 1,
                'type' => 'pension',
                'titre' => 'Attestation de Pension 2024',
                'date_creation' => now()->subDays(10),
                'status' => 'disponible',
                'url_download' => '/api/attestations/1/download'
            ]
        ];

        return response()->json([
            'success' => true,
            'attestations' => $attestations
        ]);
    }

    public function requestAttestationRetraite(Request $request)
    {
        $request->validate([
            'type' => 'required|in:pension,vie,revenus',
            'motif' => 'required|string|max:500'
        ]);

        $attestation = [
            'id' => rand(1000, 9999),
            'type' => $request->type,
            'titre' => 'Attestation de ' . ucfirst($request->type) . ' - ' . now()->format('Y-m-d'),
            'date_creation' => now(),
            'status' => 'en_cours',
            'motif' => $request->motif,
            'url_download' => null
        ];

        return response()->json([
            'success' => true,
            'message' => 'Votre demande d\'attestation a été soumise avec succès',
            'attestation' => $attestation
        ]);
    }

    public function getHistorique(Request $request)
    {
        $historique = [
            [
                'id' => 1,
                'type' => 'pension',
                'description' => 'Pension mensuelle versée',
                'date' => now()->subDays(5),
                'montant' => 150000
            ],
            [
                'id' => 2,
                'type' => 'certificat',
                'description' => 'Certificat de vie validé',
                'date' => now()->subDays(30),
                'montant' => null
            ]
        ];

        return response()->json([
            'success' => true,
            'historique' => $historique
        ]);
    }

    public function getSuiviPaiements(Request $request)
    {
        $retraite = $request->user();
        
        $paiements = [];
        for ($i = 0; $i < 6; $i++) {
            $date = now()->subMonths($i);
            $paiements[] = [
                'mois' => $date->format('F Y'),
                'montant' => $retraite->montant_pension,
                'date_versement' => $date->format('Y-m-d'),
                'status' => 'verse',
                'reference' => 'PEN-' . $date->format('Ym') . '-' . $retraite->id
            ];
        }

        return response()->json([
            'success' => true,
            'paiements' => $paiements
        ]);
    }

    public function getDocumentsRetraite(Request $request)
    {
        $documents = [
            [
                'id' => 1,
                'nom' => 'Attestation de Pension 2024',
                'type' => 'attestation',
                'date_creation' => now()->subDays(10),
                'url_download' => '/api/documents/1/download'
            ],
            [
                'id' => 2,
                'nom' => 'Certificat de Vie Valide',
                'type' => 'certificat',
                'date_creation' => now()->subDays(30),
                'url_download' => '/api/documents/2/download'
            ]
        ];

        return response()->json([
            'success' => true,
            'documents' => $documents
        ]);
    }

    public function uploadDocumentRetraite(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'type' => 'required|string',
            'description' => 'required|string|max:255'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document téléchargé avec succès'
        ]);
    }

    public function getNotificationsRetraite(Request $request)
    {
        $notifications = [
            [
                'id' => 1,
                'type' => 'info',
                'message' => 'Votre pension a été versée',
                'date' => now()->subDays(2),
                'read' => false
            ],
            [
                'id' => 2,
                'type' => 'reminder',
                'message' => 'Prochain certificat de vie requis dans 2 mois',
                'date' => now()->subDays(1),
                'read' => false
            ]
        ];

        return response()->json([
            'success' => true,
            'notifications' => $notifications
        ]);
    }

    public function markNotificationReadRetraite(Request $request, $id)
    {
        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue'
        ]);
    }

    // DASHBOARD ÉTENDU

    public function getExtendedDashboard(Request $request)
    {
        $user = $request->user();
        $userType = $user instanceof Agent ? 'actif' : 'retraite';

        if ($userType === 'actif') {
            return $this->getExtendedAgentDashboard($user);
        } else {
            return $this->getExtendedRetraiteDashboard($user);
        }
    }

    private function getExtendedAgentDashboard($agent)
    {
        $basicData = $this->agentDashboard(request())->getData();
        
        $simulatorData = $this->getSimulatorPreview($agent);
        $careerData = $this->getCareerSummary($agent);
        
        $services = $basicData->dashboard->services_disponibles;
        
        array_unshift($services, [
            'id' => 'simulateur_pension',
            'name' => 'Simulateur de Pension',
            'description' => 'Estimez votre future pension de retraite',
            'icon' => 'calculator',
            'available' => true,
            'priority' => 1,
            'badge' => 'Nouveau'
        ]);

        $extendedStats = array_merge((array)$basicData->dashboard->stats, [
            'pension_estimee' => $simulatorData['pension_estimee'],
            'annees_restantes' => $simulatorData['annees_restantes'],
            'taux_remplacement' => $simulatorData['taux_remplacement']
        ]);

        return response()->json([
            'success' => true,
            'user_type' => 'actif',
            'user' => $basicData->user,
            'dashboard' => [
                'stats' => $extendedStats,
                'activites_recentes' => $basicData->dashboard->activites_recentes,
                'services_disponibles' => $services,
                'simulateur_preview' => $simulatorData,
                'carriere_summary' => $careerData,
                'widgets' => [
                    'pension_countdown' => [
                        'years_left' => $simulatorData['annees_restantes'],
                        'months_left' => $simulatorData['mois_restants'],
                        'retirement_date' => $simulatorData['date_retraite']
                    ],
                    'salary_evolution' => $careerData['evolution_salaire'],
                    'service_duration' => $careerData['duree_service']
                ]
            ]
        ]);
    }

    private function getExtendedRetraiteDashboard($retraite)
    {
        $basicData = $this->retraiteDashboard(request())->getData();
        
        $pensionAnalysis = $this->getPensionAnalysis($retraite);
        
        return response()->json([
            'success' => true,
            'user_type' => 'retraite',
            'user' => $basicData->user,
            'dashboard' => [
                'stats' => $basicData->dashboard->stats,
                'activites_recentes' => $basicData->dashboard->activites_recentes,
                'services_disponibles' => $basicData->dashboard->services_disponibles,
                'pension_analysis' => $pensionAnalysis,
                'widgets' => [
                    'pension_details' => [
                        'montant_mensuel' => $retraite->montant_pension,
                        'prochaine_revalorisation' => $this->getNextRevalorisation(),
                        'cumul_percu' => $this->getCumulPercu($retraite)
                    ]
                ]
            ]
        ]);
    }

    private function getSimulatorPreview($agent)
    {
        try {
            $dateNaissance = $this->estimateBirthDate($agent);
            $age = Carbon::parse($dateNaissance)->age;
            $ageRetraite = 60;
            $anneesRestantes = max(0, $ageRetraite - $age);
            $moisRestants = $anneesRestantes * 12;
            
            $dureeService = Carbon::parse($agent->date_prise_service)->diffInYears(now());
            $dureeServiceRetraite = $dureeService + $anneesRestantes;
            
            $indice = $agent->indice ?? 1001;
            $salaireActuel = $indice * 500;
            
            $tauxLiquidation = $this->calculateTauxLiquidationArticle94($dureeServiceRetraite);
            $pensionBase = ($salaireActuel * $tauxLiquidation) / 100;
            
            $anneeRetraite = now()->addYears($anneesRestantes)->year;
            $coefficientTemporel = $this->getCoefficientTemporelPreview($anneeRetraite);
            
            $pensionApresCoeff = ($pensionBase * $coefficientTemporel) / 100;
            
            $tauxRemplacement = ($pensionApresCoeff / $salaireActuel) * 100;
            
            return [
                'pension_estimee' => round($pensionApresCoeff),
                'pension_base' => round($pensionBase),
                'coefficient_temporel' => $coefficientTemporel,
                'annees_restantes' => $anneesRestantes,
                'mois_restants' => $moisRestants,
                'taux_remplacement' => round($tauxRemplacement, 1),
                'taux_liquidation' => round($tauxLiquidation, 1),
                'date_retraite' => Carbon::parse($dateNaissance)->addYears(60)->format('Y-m-d'),
                'eligible' => $dureeServiceRetraite >= 15,
                'duree_service_retraite' => $dureeServiceRetraite,
                'annee_retraite' => $anneeRetraite,
                'methode' => 'Article 94',
                'formule' => 'Années × 1,8%'
            ];
            
        } catch (\Exception $e) {
            \Log::error('Erreur simulation preview Article 94:', ['error' => $e->getMessage()]);
            
            return [
                'pension_estimee' => 0,
                'annees_restantes' => 0,
                'mois_restants' => 0,
                'taux_remplacement' => 0,
                'eligible' => false,
                'error' => 'Simulation non disponible'
            ];
        }
    }

    private function getCoefficientTemporelPreview($annee)
    {
        $coefficients = [
            2024 => 89, 2025 => 91, 2026 => 94, 
            2027 => 96, 2028 => 98
        ];
        
        return $annee >= 2029 ? 100 : ($coefficients[$annee] ?? 100);
    }

    private function getCareerSummary($agent)
    {
        $dureeService = Carbon::parse($agent->date_prise_service)->diffInYears(now());
        $salaireActuel = ($agent->indice ?? 1001) * 500;
        $salaireInitial = 400 * 500;
        
        return [
            'duree_service' => [
                'annees' => $dureeService,
                'mois' => Carbon::parse($agent->date_prise_service)->diffInMonths(now()),
                'debut' => $agent->date_prise_service
            ],
            'evolution_salaire' => [
                'initial' => $salaireInitial,
                'actuel' => $salaireActuel,
                'progression' => round((($salaireActuel - $salaireInitial) / $salaireInitial) * 100, 1)
            ],
            'grade_actuel' => $agent->grade,
            'direction' => $agent->direction,
            'indice' => $agent->indice ?? 1001
        ];
    }

    private function getPensionAnalysis($retraite)
    {
        $anneesRetraite = Carbon::parse($retraite->date_retraite)->diffInYears(now());
        $totalPercu = $retraite->montant_pension * $anneesRetraite * 12;
        
        return [
            'annees_retraite' => $anneesRetraite,
            'total_percu' => $totalPercu,
            'moyenne_mensuelle' => $retraite->montant_pension,
            'evolution_pension' => [
                'initial' => $retraite->montant_pension * 0.9,
                'actuel' => $retraite->montant_pension,
                'revalorisation' => '10%'
            ]
        ];
    }

    private function getNextRevalorisation()
    {
        return now()->addMonths(6)->format('Y-m-d');
    }

    private function getCumulPercu($retraite)
    {
        $moisRetraite = Carbon::parse($retraite->date_retraite)->diffInMonths(now());
        return $retraite->montant_pension * $moisRetraite;
    }

    private function estimateBirthDate($agent)
    {
        return Carbon::parse($agent->date_prise_service)->subYears(25)->format('Y-m-d');
    }

    private function calculateTauxLiquidationArticle94($dureeService)
    {
        if ($dureeService < 15) return 0;
        return $dureeService * 1.8;
    }
}