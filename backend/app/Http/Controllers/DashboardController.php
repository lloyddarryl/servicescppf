<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Agent;
use App\Models\Retraite;
use Carbon\Carbon;

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
     * Dashboard pour agents actifs
     */
    public function agentDashboard(Request $request)
{
    $agent = $request->user();
    
    // Calculer les statistiques
    $anneesService = Carbon::parse($agent->date_prise_service)->diffInYears(now());
    $moisService = Carbon::parse($agent->date_prise_service)->diffInMonths(now()) % 12;
    
    // Données statistiques (améliorer avec de vraies données)
    $stats = [
        'cotisations_totales' => $this->calculerCotisationsTotales($agent),
        'prestations_recues' => $this->calculerPrestationsRecues($agent),
        'attestations_demandees' => $this->compterAttestationsDemandees($agent),
        'dossiers_en_cours' => $this->compterDossiersEnCours($agent),
    ];
    
    // Activités récentes dynamiques
    $activites = $this->getActivitesRecentesAgent($agent);
    
    // Services disponibles mis à jour avec Article 94
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
            'activites_recentes' => $activites, // ✅ Maintenant dynamiques
            'services_disponibles' => $services,
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
     * Dashboard pour retraités
     */
    public function retraiteDashboard(Request $request)
{
    $retraite = $request->user();
    
    // Calculer les statistiques
    $anneesRetraite = Carbon::parse($retraite->date_retraite)->diffInYears(now());
    $moisRetraite = Carbon::parse($retraite->date_retraite)->diffInMonths(now()) % 12;
    $age = Carbon::parse($retraite->date_naissance)->age;
    
    // Données statistiques améliorées
    $stats = [
        'pension_mensuelle' => $retraite->montant_pension,
        'pensions_recues' => $anneesRetraite * 12 + $moisRetraite,
        'total_percu' => ($anneesRetraite * 12 + $moisRetraite) * $retraite->montant_pension,
        'certificats_valides' => $this->compterCertificatsValides($retraite),
    ];
    
    // Activités récentes dynamiques
    $activites = $this->getActivitesRecentesRetraite($retraite);
    
    // Services disponibles
    $services = [
    [
        'id' => 'pension',
        'name' => 'Suivi Pension',
        'description' => 'Consulter vos versements de pension',
        'icon' => 'banknotes',
        'available' => true
    ],
    [
        'id' => 'grappe_familiale',
        'name' => 'Grappe Familiale',
        'description' => 'Gérer vos informations familiales',
        'icon' => 'users', 
        'available' => true
    ],
    [
        'id' => 'documents', 
        'name' => 'Mes Documents',
        'description' => 'Gérer vos documents et attestations',
        'icon' => 'document',
        'available' => true
    ],
    [
        'id' => 'reclamations', 
        'name' => 'Réclamations',
        'description' => 'Gérer vos réclamations et demandes',
        'icon' => 'reclamation',
        'available' => true,
        'color' => 'red'
    ],
    [
            'id' => 'prise_rdv',
            'name' => 'Prise de Rendez-vous',
            'description' => 'Réserver un rendez-vous avec un conseiller',
            'icon' => 'calendar',
            'available' => true,
            'color' => 'orange'
    ]
];

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
            'age' => $age,
            'ancien_poste' => $retraite->ancien_poste,
            'ancienne_direction' => $retraite->ancienne_direction,
            'date_naissance' => $retraite->date_naissance,
            'date_retraite' => $retraite->date_retraite,
            'montant_pension' => $retraite->montant_pension,
            'email' => $retraite->email,
            'telephone' => $retraite->telephone,
            'annees_retraite' => $anneesRetraite,
            'mois_retraite' => $moisRetraite,
            'parcours_professionnel' => $retraite->parcours_professionnel,
            'status' => $retraite->status,
            'email_verified' => !is_null($retraite->email_verified_at),
            'phone_verified' => !is_null($retraite->phone_verified_at),
        ],
        'dashboard' => [
            'stats' => $stats,
            'activites_recentes' => $activites, // ✅ Maintenant dynamiques
            'services_disponibles' => $services,
        ]
    ]);
}
// Méthodes utilitaires pour les statistiques
private function calculerCotisationsTotales($agent)
{
    // TODO: Implémenter avec de vraies données
    $dureeServiceMois = Carbon::parse($agent->date_prise_service)->diffInMonths(now());
    return $dureeServiceMois * 45000; // Cotisation moyenne
}

private function calculerPrestationsRecues($agent)
{
    // TODO: Implémenter avec de vraies données
    return rand(5, 25) * 10000;
}

private function compterAttestationsDemandees($agent)
{
    // TODO: Implémenter avec de vraies données
    return rand(3, 12);
}

private function compterDossiersEnCours($agent)
{
    $dossiers = 0;
    
    // Compter les réclamations en cours
    try {
        $dossiers += \App\Models\Reclamation::pourUtilisateur($agent->id, 'agent')
                                          ->enCours()
                                          ->count();
    } catch (\Exception $e) {
        // Ignorer si la table n'existe pas encore
    }
    
    return $dossiers;
}

private function compterCertificatsValides($retraite)
{
    // TODO: Implémenter avec de vraies données
    return rand(1, 3);
}

    /**
     * Obtenir les attestations (agents actifs)
     */
    public function getAttestations(Request $request)
    {
        // Simulation d'attestations
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

    /**
     * Demander une attestation (agents actifs)
     */
    public function requestAttestation(Request $request)
    {
        $request->validate([
            'type' => 'required|in:cotisations,emploi,pension',
            'motif' => 'required|string|max:500'
        ]);

        // Simulation de création d'attestation
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
     * Obtenir les prestations (agents actifs)
     */
    public function getPrestations(Request $request)
    {
        $prestations = [
            [
                'id' => 1,
                'type' => 'allocation_familiale',
                'nom' => 'Allocation Familiale',
                'montant' => 15000,
                'date_versement' => now()->subDays(15),
                'status' => 'verse'
            ],
            [
                'id' => 2,
                'type' => 'allocation_rentree',
                'nom' => 'Allocation Rentrée Scolaire',
                'montant' => 50000,
                'date_versement' => now()->subDays(60),
                'status' => 'verse'
            ]
        ];

        return response()->json([
            'success' => true,
            'prestations' => $prestations
        ]);
    }

    /**
     * Obtenir les cotisations (agents actifs)
     */
    public function getCotisations(Request $request)
    {
        $cotisations = [
            [
                'id' => 1,
                'mois' => 'Juin 2025',
                'montant' => 45000,
                'date_prelevement' => '2025-06-01',
                'status' => 'prelevee'
            ],
            [
                'id' => 2,
                'mois' => 'Mai 2025',
                'montant' => 45000,
                'date_prelevement' => '2025-05-01',
                'status' => 'prelevee'
            ]
        ];

        return response()->json([
            'success' => true,
            'cotisations' => $cotisations
        ]);
    }

    /**
     * Obtenir la carrière (agents actifs)
     */
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

    /**
     * Obtenir les documents (agents actifs)
     */
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

    /**
     * Upload de document (agents actifs)
     */
    public function uploadDocument(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'type' => 'required|string',
            'description' => 'required|string|max:255'
        ]);

        // Simulation d'upload
        return response()->json([
            'success' => true,
            'message' => 'Document téléchargé avec succès'
        ]);
    }

    /**
     * Obtenir les notifications (agents actifs)
     */
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

    /**
     * Marquer notification comme lue (agents actifs)
     */
    public function markNotificationRead(Request $request, $id)
    {
        // Simulation de marquage comme lu
        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue'
        ]);
    }

    /**
     * Obtenir les informations de pension (retraités)
     */
    public function getPensionInfo(Request $request)
    {
        $retraite = $request->user();
        
        // Simulation des versements récents
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

    /**
     * Obtenir l'historique des pensions (retraités)
     */
    public function getPensionHistorique(Request $request)
    {
        $retraite = $request->user();
        
        // Simulation de l'historique des pensions
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

    /**
     * Obtenir les certificats de vie (retraités)
     */
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

    /**
     * Soumettre un certificat de vie (retraités)
     */
    public function submitCertificatVie(Request $request)
    {
        $request->validate([
            'certificat' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'autorite' => 'required|string|max:255',
            'date_emission' => 'required|date'
        ]);

        // Simulation de soumission
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

    /**
     * Obtenir le statut d'un certificat (retraités)
     */
    public function getCertificatStatus(Request $request, $id)
    {
        // Simulation du statut
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

    /**
     * Obtenir les attestations pour retraités
     */
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

    /**
     * Demander une attestation (retraités)
     */
    public function requestAttestationRetraite(Request $request)
    {
        $request->validate([
            'type' => 'required|in:pension,vie,revenus',
            'motif' => 'required|string|max:500'
        ]);

        // Simulation de création d'attestation
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
     * Obtenir l'historique (retraités)
     */
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

    /**
     * Obtenir le suivi des paiements (retraités)
     */
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

    /**
     * Obtenir les documents pour retraités
     */
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

    /**
     * Upload de document pour retraités
     */
    public function uploadDocumentRetraite(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'type' => 'required|string',
            'description' => 'required|string|max:255'
        ]);

        // Simulation d'upload
        return response()->json([
            'success' => true,
            'message' => 'Document téléchargé avec succès'
        ]);
    }

    /**
     * Obtenir les notifications pour retraités
     */
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

    /**
     * Marquer notification comme lue pour retraités
     */
    public function markNotificationReadRetraite(Request $request, $id)
    {
        // Simulation de marquage comme lu
        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue'
        ]);
    }
    // Ajouter ces méthodes à la classe DashboardController existante

/**
 * Dashboard étendu avec simulateur de pension
 */
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

/**
 * Dashboard étendu pour agents actifs
 */
private function getExtendedAgentDashboard($agent)
{
    // Données de base du dashboard existant
    $basicData = $this->agentDashboard(request())->getData();
    
    // Ajouter les données du simulateur
    $simulatorData = $this->getSimulatorPreview($agent);
    $careerData = $this->getCareerSummary($agent);
    
    // Mettre à jour les services avec le simulateur
    $services = $basicData->dashboard->services_disponibles;
    
    // Ajouter le simulateur de pension comme premier service
    array_unshift($services, [
        'id' => 'simulateur_pension',
        'name' => 'Simulateur de Pension',
        'description' => 'Estimez votre future pension de retraite',
        'icon' => 'calculator',
        'available' => true,
        'priority' => 1,
        'badge' => 'Nouveau'
    ]);

    // Enrichir les statistiques
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

/**
 * Dashboard étendu pour retraités
 */
private function getExtendedRetraiteDashboard($retraite)
{
    // Données de base du dashboard existant
    $basicData = $this->retraiteDashboard(request())->getData();
    
    // Ajouter l'analyse de pension
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

/**
 * Aperçu rapide du simulateur selon Article 94
 */
private function getSimulatorPreview($agent)
{
    try {
        // Calculer une simulation rapide selon Article 94
        $dateNaissance = $this->estimateBirthDate($agent);
        $age = Carbon::parse($dateNaissance)->age;
        $ageRetraite = 60;
        $anneesRestantes = max(0, $ageRetraite - $age);
        $moisRestants = $anneesRestantes * 12;
        
        $dureeService = Carbon::parse($agent->date_prise_service)->diffInYears(now());
        $dureeServiceRetraite = $dureeService + $anneesRestantes;
        
        $indice = $agent->indice ?? 1001;
        $salaireActuel = $indice * 500;
        
        // Calcul selon Article 94
        $tauxLiquidation = $this->calculateTauxLiquidationArticle94($dureeServiceRetraite);
        $pensionBase = ($salaireActuel * $tauxLiquidation) / 100;
        
        // Coefficient temporel pour l'année de retraite prévue
        $anneeRetraite = now()->addYears($anneesRestantes)->year;
        $coefficientTemporel = $this->getCoefficientTemporelPreview($anneeRetraite);
        
        // Pension après coefficient
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
        Log::error('Erreur simulation preview Article 94:', ['error' => $e->getMessage()]);
        
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
/**
 * Obtenir le coefficient temporel pour preview
 */
private function getCoefficientTemporelPreview($annee)
{
    $coefficients = [
        2024 => 89, 2025 => 91, 2026 => 94, 
        2027 => 96, 2028 => 98
    ];
    
    return $annee >= 2029 ? 100 : ($coefficients[$annee] ?? 100);
}

/**
 * Résumé de carrière
 */
private function getCareerSummary($agent)
{
    $dureeService = Carbon::parse($agent->date_prise_service)->diffInYears(now());
    $salaireActuel = ($agent->indice ?? 1001) * 500;
    $salaireInitial = 400 * 500; // Estimation
    
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

/**
 * Analyse de pension pour retraités
 */
private function getPensionAnalysis($retraite)
{
    $anneesRetraite = Carbon::parse($retraite->date_retraite)->diffInYears(now());
    $totalPercu = $retraite->montant_pension * $anneesRetraite * 12;
    
    return [
        'annees_retraite' => $anneesRetraite,
        'total_percu' => $totalPercu,
        'moyenne_mensuelle' => $retraite->montant_pension,
        'evolution_pension' => [
            'initial' => $retraite->montant_pension * 0.9, // Estimation
            'actuel' => $retraite->montant_pension,
            'revalorisation' => '10%'
        ]
    ];
}

/**
 * Prochaine revalorisation
 */
private function getNextRevalorisation()
{
    return now()->addMonths(6)->format('Y-m-d');
}

/**
 * Cumul perçu depuis la retraite
 */
private function getCumulPercu($retraite)
{
    $moisRetraite = Carbon::parse($retraite->date_retraite)->diffInMonths(now());
    return $retraite->montant_pension * $moisRetraite;
}

/**
 * Estimer la date de naissance
 */
private function estimateBirthDate($agent)
{
    // Estimation : 25 ans à l'embauche
    return Carbon::parse($agent->date_prise_service)->subYears(25)->format('Y-m-d');
}

/**
 * Calculer le taux de liquidation selon Article 94
 */
private function calculateTauxLiquidationArticle94($dureeService)
{
    if ($dureeService < 15) return 0;
    return $dureeService * 1.8;
}

// Mise à jour du DashboardController pour rendre les activités récentes dynamiques

// Ajouter ces méthodes dans app/Http/Controllers/DashboardController.php

/**
 * Obtenir les activités récentes dynamiques pour agents actifs
 */
private function getActivitesRecentesAgent($agent)
{
    $activites = collect();
    
    try {
        // Activités des réclamations
        $reclamationsRecentes = \App\Models\Reclamation::pourUtilisateur($agent->id, 'agent')
                                                      ->with('historique')
                                                      ->orderBy('created_at', 'desc')
                                                      ->limit(3)
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
        
        // Activités des simulations de pension (si disponibles)
        if (class_exists('\App\Models\SimulationPension')) {
            $simulationsRecentes = \App\Models\SimulationPension::where('agent_id', $agent->id)
                                                               ->orderBy('created_at', 'desc')
                                                               ->limit(2)
                                                               ->get();
            
            foreach ($simulationsRecentes as $simulation) {
                $activites->push([
                    'id' => 'simulation_' . $simulation->id,
                    'type' => 'simulation',
                    'description' => "Simulation de pension réalisée - Pension estimée: " . 
                                   number_format($simulation->pension_estimee, 0, ',', ' ') . ' FCFA',
                    'date' => $simulation->created_at,
                    'status' => 'completed',
                    'metadata' => [
                        'pension_estimee' => $simulation->pension_estimee,
                        'duree_service' => $simulation->duree_service
                    ]
                ]);
            }
        }
        
    } catch (\Exception $e) {
        \Log::error('Erreur récupération activités agent:', [
            'agent_id' => $agent->id,
            'error' => $e->getMessage()
        ]);
    }
    
    // Ajouter des activités simulées si pas assez d'activités réelles
    if ($activites->count() < 3) {
        $activites = $activites->merge($this->getActivitesSimuleesAgent($agent));
    }
    
    return $activites->sortByDesc('date')->take(5)->values()->toArray();
}

/**
 * Obtenir les activités récentes dynamiques pour retraités
 */
private function getActivitesRecentesRetraite($retraite)
{
    $activites = collect();
    
    try {
        // Activités des réclamations
        $reclamationsRecentes = \App\Models\Reclamation::pourUtilisateur($retraite->id, 'retraite')
                                                      ->with('historique')
                                                      ->orderBy('created_at', 'desc')
                                                      ->limit(3)
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
        
    } catch (\Exception $e) {
        \Log::error('Erreur récupération activités retraité:', [
            'retraite_id' => $retraite->id,
            'error' => $e->getMessage()
        ]);
    }
    
    // Ajouter des activités simulées si pas assez d'activités réelles
    if ($activites->count() < 3) {
        $activites = $activites->merge($this->getActivitesSimuleesRetraite($retraite));
    }
    
    return $activites->sortByDesc('date')->take(5)->values()->toArray();
}

/**
 * Générer la description d'une activité de réclamation
 */
private function getDescriptionActiviteReclamation($reclamation)
{
    $typeNom = \App\Models\Reclamation::$typesReclamations[$reclamation->type_reclamation]['nom'] ?? 'Réclamation';
    
    switch ($reclamation->statut) {
        case 'en_attente':
            return "Réclamation déposée: {$typeNom} (N° {$reclamation->numero_reclamation})";
        case 'en_cours':
            return "Réclamation en cours de traitement: {$typeNom}";
        case 'en_revision':
            return "Réclamation en révision: {$typeNom}";
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
 * Activités simulées pour agents (fallback)
 */
private function getActivitesSimuleesAgent($agent)
{
    return collect([
        [
            'id' => 'cotisation_auto',
            'type' => 'cotisation',
            'description' => 'Cotisation mensuelle prélevée - ' . number_format(45000, 0, ',', ' ') . ' FCFA',
            'date' => now()->subDays(rand(5, 15)),
            'status' => 'completed'
        ],
        [
            'id' => 'prestation_auto',
            'type' => 'prestation',
            'description' => 'Allocation familiale versée - ' . number_format(15000, 0, ',', ' ') . ' FCFA',
            'date' => now()->subDays(rand(20, 30)),
            'status' => 'completed'
        ]
    ]);
}

/**
 * Activités simulées pour retraités (fallback)
 */
private function getActivitesSimuleesRetraite($retraite)
{
    return collect([
        [
            'id' => 'pension_auto',
            'type' => 'pension',
            'description' => 'Pension mensuelle versée - ' . number_format($retraite->montant_pension, 0, ',', ' ') . ' FCFA',
            'date' => now()->subDays(rand(1, 5)),
            'status' => 'completed'
        ],
        [
            'id' => 'certificat_auto',
            'type' => 'certificat',
            'description' => 'Certificat de vie validé',
            'date' => now()->subDays(rand(30, 60)),
            'status' => 'completed'
        ]
    ]);
}


}