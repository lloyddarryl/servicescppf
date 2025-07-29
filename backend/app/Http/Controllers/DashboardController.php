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
        
        // Données simulées pour les statistiques
        $stats = [
            'cotisations_totales' => rand(50, 200) * 10000,
            'prestations_recues' => rand(5, 25) * 10000,
            'attestations_demandees' => rand(3, 12),
            'dossiers_en_cours' => rand(0, 5),
        ];
        
        // Dernières activités simulées
        $activites = [
            [
                'id' => 1,
                'type' => 'attestation',
                'description' => 'Attestation de cotisations générée',
                'date' => now()->subDays(5),
                'status' => 'completed'
            ],
            [
                'id' => 2,
                'type' => 'prestation',
                'description' => 'Allocation rentrée scolaire versée',
                'date' => now()->subDays(12),
                'status' => 'completed'
            ],
            [
                'id' => 3,
                'type' => 'cotisation',
                'description' => 'Cotisation mensuelle prélevée',
                'date' => now()->subDays(25),
                'status' => 'completed'
            ]
        ];
        
        // Services disponibles
        $services = [
           
             [
                'id' => 'simulateur_pension',
                'name' => 'Simulateur de Pension',
                'description' => 'Simuler votre future pension de retraite',
                'icon' => 'icon-simulator',
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
                'id' => 'cotisations',
                'name' => 'Suivi des Cotisations',
                'description' => 'Consulter l\'historique de vos cotisations',
                'icon' => 'chart',
                'available' => true
            ],
             [
                'id' => 'prise_rdv',
                'name' => "Prise de Rendez-vous",
                'description' => 'Réserver un rendez-vous avec un conseiller',
                'icon' => 'document-check',
                'available' => true
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
        
        // Données simulées pour les statistiques
        $stats = [
            'pension_mensuelle' => $retraite->montant_pension,
            'pensions_recues' => $anneesRetraite * 12 + $moisRetraite,
            'total_percu' => ($anneesRetraite * 12 + $moisRetraite) * $retraite->montant_pension,
            'certificats_valides' => rand(1, 3),
        ];
        
        // Dernières activités
        $activites = [
            [
                'id' => 1,
                'type' => 'pension',
                'description' => 'Pension mensuelle versée - ' . number_format($retraite->montant_pension, 0, ',', ' ') . ' FCFA',
                'date' => now()->subDays(5),
                'status' => 'completed'
            ],
            [
                'id' => 2,
                'type' => 'certificat',
                'description' => 'Certificat de vie soumis et validé',
                'date' => now()->subDays(45),
                'status' => 'completed'
            ],
            [
                'id' => 3,
                'type' => 'document',
                'description' => 'Attestation de pension générée',
                'date' => now()->subDays(60),
                'status' => 'completed'
            ]
        ];
        
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
                'icon' => 'document-check',
                'available' => true
            ],
            [
                'id' => 'historique',
                'name' => 'Historique de paiements',
                'description' => 'Consulter votre historique de paiement',
                'icon' => 'pencil',
                'available' => true
            ],
            [
                'id' => 'Mes Documents',
                'name' => "Mes Documents",
                'description' => 'Gérer vos documents et attestations',
                'icon' => 'document',
                'available' => true
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
                'activites_recentes' => $activites,
                'services_disponibles' => $services,
                
            ]
        ]);
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
}