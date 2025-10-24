<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\RendezVousDemande;
use App\Models\Reclamation;
use App\Models\DocumentRetraite;
use App\Models\Agent;
use App\Models\Retraite;
use App\Models\MessageDashboard;
use App\Models\LogActivite;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    /**
     * Dashboard principal admin
     */
    public function index(Request $request)
    {
        try {
            $admin = $request->user();
            
            Log::info('📊 [ADMIN DASHBOARD] Chargement dashboard', [
                'admin_id' => $admin->id,
                'role' => $admin->role
            ]);

            // Statistiques générales avec vérification des tables
            $stats = [
                // Rendez-vous
                'rdv_en_attente' => $this->safeCount('rendez_vous_demandes', 'statut', 'en_attente'),
                'rdv_acceptes' => $this->safeCount('rendez_vous_demandes', 'statut', 'accepte'),
                'rdv_total' => $this->safeCount('rendez_vous_demandes'),
                'rdv_trend' => $this->calculerTendance('rendez_vous_demandes', 'date_soumission'),
                
                // Réclamations  
                'reclamations_actives' => $this->safeCountIn('reclamations', 'statut', ['en_attente', 'en_cours', 'en_revision']),
                'reclamations_resolues' => $this->safeCountIn('reclamations', 'statut', ['resolu', 'ferme']),
                'reclamations_total' => $this->safeCount('reclamations'),
                'reclamations_trend' => $this->calculerTendance('reclamations', 'date_soumission'),
                
                // Documents
                'documents_en_attente' => $this->safeCount('document_retraites', 'statut', 'en_attente'),
                'documents_valides' => $this->safeCount('document_retraites', 'statut', 'valide'),
                'documents_total' => $this->safeCount('document_retraites'),
                'certificats_expires' => $this->getCertificatsExpires(),
                'documents_trend' => $this->calculerTendance('document_retraites', 'date_depot'),
                
                // Utilisateurs
                'total_agents_actifs' => $this->safeCount('agents', 'is_active', 1),
                'total_retraites' => $this->safeCount('retraites', 'is_active', 1),
                'connexions_today' => $this->getConnexionsAujourdhui(),
                
                // Activité du mois  
                'rdv_traites_mois' => $this->getRendezVousTraitesCeMois(),
                'reclamations_resolues_mois' => $this->getReclamationsResoluesCeMois(),
                'documents_valides_mois' => $this->getDocumentsValidesCeMois(),
                
                // Performance
                'temps_moyen_traitement' => $this->calculerTempsMoyenTraitement(),
                'taux_satisfaction' => 0,
                'delai_moyen_reponse' => $this->calculerDelaiMoyenReponse(),
            ];

            // Alertes urgentes
            $alertes = $this->getAlertesUrgentes();

            // Évolution des demandes (30 derniers jours)
            $evolutionDemandes = $this->getEvolutionDemandes();

            // Répartition par type
            $repartitionTypes = $this->getRepartitionTypes();

            // Activités récentes
            $activitesRecentes = $this->getActivitesRecentes();

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'alertes_urgentes' => $alertes,
                'evolution_demandes' => $evolutionDemandes,
                'repartition_types' => $repartitionTypes,
                'activites_recentes' => $activitesRecentes,
                'notifications_non_lues' => 0,
                'admin_info' => [
                    'nom_complet' => $admin->nom_complet,
                    'role' => $admin->role,
                    'derniere_connexion' => $admin->derniere_connexion
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('💥 [ADMIN DASHBOARD] Erreur chargement dashboard', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du dashboard'
            ], 500);
        }
    }

    /**
     * Comptage sécurisé avec vérification de table
     */
    private function safeCount($table, $column = null, $value = null)
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                return 0;
            }

            $query = DB::table($table);
            if ($column && $value) {
                $query->where($column, $value);
            }
            return $query->count();
        } catch (\Exception $e) {
            Log::warning("Erreur comptage table {$table}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Comptage sécurisé avec whereIn
     */
    private function safeCountIn($table, $column, $values)
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                return 0;
            }
            return DB::table($table)->whereIn($column, $values)->count();
        } catch (\Exception $e) {
            Log::warning("Erreur comptage table {$table}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calculer la tendance sur 7 jours
     */
    private function calculerTendance($table, $dateColumn)
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                return 0;
            }

            $today = DB::table($table)
                ->whereDate($dateColumn, today())
                ->count();

            $lastWeek = DB::table($table)
                ->whereDate($dateColumn, '>=', now()->subDays(7))
                ->whereDate($dateColumn, '<', today())
                ->count();

            if ($lastWeek == 0) {
                return $today > 0 ? 100 : 0;
            }

            $weeklyAverage = $lastWeek / 7;
            return round((($today - $weeklyAverage) / $weeklyAverage) * 100, 1);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtenir les certificats expirés
     */
    private function getCertificatsExpires()
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('document_retraites')) {
                return 0;
            }

            return DB::table('document_retraites')
                ->where('type_document', 'certificat_vie')
                ->where('date_expiration', '<', now())
                ->where('statut', '!=', 'rejete')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtenir les connexions aujourd'hui
     */
    private function getConnexionsAujourdhui()
    {
        $agentsConnexions = 0;
        $retraitesConnexions = 0;

        try {
            if (DB::getSchemaBuilder()->hasTable('agents')) {
                $agentsConnexions = DB::table('agents')
                    ->whereDate('derniere_connexion', today())
                    ->count();
            }
        } catch (\Exception $e) {}

        try {
            if (DB::getSchemaBuilder()->hasTable('retraites')) {
                $retraitesConnexions = DB::table('retraites')
                    ->whereDate('derniere_connexion', today())
                    ->count();
            }
        } catch (\Exception $e) {}
        
        return $agentsConnexions + $retraitesConnexions;
    }

    /**
     * RDV traités ce mois
     */
    private function getRendezVousTraitesCeMois()
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('rendez_vous_demandes')) {
                return 0;
            }

            return DB::table('rendez_vous_demandes')
                ->whereNotNull('date_traitement')
                ->whereMonth('date_traitement', now()->month)
                ->whereYear('date_traitement', now()->year)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Réclamations résolues ce mois
     */
    private function getReclamationsResoluesCeMois()
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('reclamations')) {
                return 0;
            }

            return DB::table('reclamations')
                ->whereIn('statut', ['resolu', 'ferme'])
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Documents validés ce mois
     */
    private function getDocumentsValidesCeMois()
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('document_retraites')) {
                return 0;
            }

            return DB::table('document_retraites')
                ->where('statut', 'valide')
                ->whereMonth('date_traitement', now()->month)
                ->whereYear('date_traitement', now()->year)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculer le temps moyen de traitement
     */
    private function calculerTempsMoyenTraitement()
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('rendez_vous_demandes')) {
                return 0;
            }

            $rdvTraites = DB::table('rendez_vous_demandes')
                ->whereNotNull('date_traitement')
                ->whereMonth('date_traitement', now()->month)
                ->select('date_soumission', 'date_traitement')
                ->get();

            if ($rdvTraites->isEmpty()) {
                return 0;
            }

            $totalHeures = 0;
            foreach ($rdvTraites as $rdv) {
                $debut = Carbon::parse($rdv->date_soumission);
                $fin = Carbon::parse($rdv->date_traitement);
                $totalHeures += $debut->diffInHours($fin);
            }

            return round($totalHeures / $rdvTraites->count(), 1);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculer le délai moyen de réponse
     */
    private function calculerDelaiMoyenReponse()
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('reclamations')) {
                return 0;
            }

            $reclamationsTraitees = DB::table('reclamations')
                ->whereNotNull('date_traitement')
                ->whereMonth('date_traitement', now()->month)
                ->select('date_soumission', 'date_traitement')
                ->get();

            if ($reclamationsTraitees->isEmpty()) {
                return 0;
            }

            $totalHeures = 0;
            foreach ($reclamationsTraitees as $reclamation) {
                $debut = Carbon::parse($reclamation->date_soumission);
                $fin = Carbon::parse($reclamation->date_traitement);
                $totalHeures += $debut->diffInHours($fin);
            }

            return round($totalHeures / $reclamationsTraitees->count(), 1);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtenir les alertes urgentes
     */
    private function getAlertesUrgentes()
    {
        $alertes = [];

        try {
            // RDV en attente depuis plus de 48h
            $rdvAnciensAttente = DB::table('rendez_vous_demandes')
                ->where('statut', 'en_attente')
                ->where('date_soumission', '<', now()->subHours(48))
                ->count();

            if ($rdvAnciensAttente > 0) {
                $alertes[] = [
                    'type' => 'rdv_anciens',
                    'niveau' => 'urgent',
                    'titre' => 'Rendez-vous en attente',
                    'message' => "{$rdvAnciensAttente} rendez-vous en attente depuis plus de 48h",
                    'icone' => '⚠️',
                    'couleur' => '#EF4444',
                    'count' => $rdvAnciensAttente
                ];
            }

            // Réclamations urgentes
            $reclamationsUrgentes = DB::table('reclamations')
                ->where('priorite', 'urgente')
                ->whereIn('statut', ['en_attente', 'en_cours'])
                ->count();

            if ($reclamationsUrgentes > 0) {
                $alertes[] = [
                    'type' => 'reclamations_urgentes',
                    'niveau' => 'critique',
                    'titre' => 'Réclamations urgentes',
                    'message' => "{$reclamationsUrgentes} réclamations urgentes à traiter",
                    'icone' => '🚨',
                    'couleur' => '#DC2626',
                    'count' => $reclamationsUrgentes
                ];
            }

            // Certificats expirés
            $certificatsExpires = $this->getCertificatsExpires();
            if ($certificatsExpires > 0) {
                $alertes[] = [
                    'type' => 'certificats_expires',
                    'niveau' => 'warning',
                    'titre' => 'Certificats de vie expirés',
                    'message' => "{$certificatsExpires} certificats expirés à vérifier",
                    'icone' => '📋',
                    'couleur' => '#F59E0B',
                    'count' => $certificatsExpires
                ];
            }

        } catch (\Exception $e) {
            Log::warning('Erreur récupération alertes: ' . $e->getMessage());
        }

        return $alertes;
    }

    /**
     * Obtenir l'évolution des demandes sur 30 jours
     */
    private function getEvolutionDemandes()
    {
        $data = [];
        
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            
            $data[] = [
                'date' => $date->format('d/m'),
                'rdv' => $this->safeCountByDate('rendez_vous_demandes', 'date_soumission', $date),
                'reclamations' => $this->safeCountByDate('reclamations', 'date_soumission', $date),
                'documents' => $this->safeCountByDate('document_retraites', 'date_depot', $date)
            ];
        }

        return $data;
    }

    private function safeCountByDate($table, $column, $date)
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                return 0;
            }
            return DB::table($table)->whereDate($column, $date)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtenir la répartition par type
     */
    private function getRepartitionTypes()
    {
        $repartition = [
            'rdv_par_motif' => [],
            'reclamations_par_type' => []
        ];

        try {
            if (DB::getSchemaBuilder()->hasTable('rendez_vous_demandes')) {
                $repartition['rdv_par_motif'] = DB::table('rendez_vous_demandes')
                    ->select('motif', DB::raw('count(*) as total'))
                    ->groupBy('motif')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'motif' => $item->motif ?? 'Non spécifié',
                            'total' => $item->total
                        ];
                    });
            }

            if (DB::getSchemaBuilder()->hasTable('reclamations')) {
                $repartition['reclamations_par_type'] = DB::table('reclamations')
                    ->select('type_reclamation', DB::raw('count(*) as total'))
                    ->groupBy('type_reclamation')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'type' => $item->type_reclamation ?? 'Non spécifié',
                            'total' => $item->total
                        ];
                    });
            }
        } catch (\Exception $e) {
            Log::warning('Erreur répartition types: ' . $e->getMessage());
        }

        return $repartition;
    }

    /**
     * Obtenir les activités récentes
     */
    private function getActivitesRecentes()
{
    $activites = [];

    try {
        // ✅ CORRECTION : Pas de JOIN avec agents, utiliser les données directes
        $derniersRdv = DB::table('rendez_vous_demandes')
            ->whereNotNull('date_traitement')
            ->orderBy('date_traitement', 'desc')
            ->limit(5)
            ->get();

        foreach ($derniersRdv as $rdv) {
            $activites[] = [
                'type' => 'rdv_traite',
                'titre' => 'RDV traité',
                'description' => "RDV de " . ($rdv->user_prenoms ?? 'Agent') . " " . ($rdv->user_nom ?? 'supprimé'),
                'admin' => 'Système',
                'date' => $rdv->date_traitement,
                'icone' => '📅',
                'couleur' => '#10B981'
            ];
        }
    } catch (\Exception $e) {
        Log::warning('Erreur activités récentes: ' . $e->getMessage());
    }

    return $activites;
}
}