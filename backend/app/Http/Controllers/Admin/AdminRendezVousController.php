<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RendezVousDemande;
use App\Models\Agent;
use App\Models\MessageDashboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminRendezVousController extends Controller
{
    /**
     * Liste des rendez-vous avec filtres et pagination
     */
    public function index(Request $request)
    {
        try {
            // ✅ CORRECTION : Ne pas utiliser with(['agent']) car la relation n'existe pas
            $query = RendezVousDemande::query();

            // Filtres
            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }

            if ($request->filled('motif')) {
                $query->where('motif', 'like', "%{$request->motif}%");
            }

            if ($request->filled('date_debut') && $request->filled('date_fin')) {
                $query->whereBetween('date_demandee', [$request->date_debut, $request->date_fin]);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                // ✅ CORRECTION : Chercher dans user_nom et user_prenoms (pas agent.nom)
                $query->where(function($q) use ($search) {
                    $q->where('user_nom', 'like', "%{$search}%")
                      ->orWhere('user_prenoms', 'like', "%{$search}%")
                      ->orWhere('numero_demande', 'like', "%{$search}%");
                });
            }

            if ($request->filled('urgence')) {
                if ($request->urgence === 'urgent') {
                    $query->where('statut', 'en_attente')
                          ->where('date_soumission', '<', now()->subDays(2));
                }
            }

            // Tri
            $sortField = $request->get('sort_field', 'date_soumission');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            $rdvs = $query->paginate($request->get('per_page', 15));

            // ✅ CORRECTION : Ajouter des informations calculées
            $rdvs->getCollection()->transform(function ($rdv) {
                $rdv->jours_attente = $rdv->date_soumission->diffInDays(now());
                $rdv->urgent = $rdv->statut === 'en_attente' && $rdv->jours_attente > 2;
                $rdv->peut_modifier = in_array($rdv->statut, ['en_attente', 'reporte']);
                
                // ✅ Créer un objet agent factice pour compatibilité frontend
                $rdv->agent = (object) [
                    'nom' => $rdv->user_nom,
                    'prenom' => $rdv->user_prenoms,
                    'prenoms' => $rdv->user_prenoms,
                    'email' => $rdv->user_email,
                    'telephone' => $rdv->user_telephone,
                    'nom_complet' => $rdv->user_prenoms . ' ' . $rdv->user_nom,
                    'matricule' => 'N/A' // Si vous n'avez pas cette info
                ];
                
                return $rdv;
            });

            return response()->json([
                'success' => true,
                'data' => $rdvs,
                'filtres_disponibles' => [
                    'statuts' => ['en_attente', 'accepte', 'refuse', 'reporte', 'annule'],
                    'motifs' => RendezVousDemande::distinct('motif')->pluck('motif')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération RDV admin: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des rendez-vous',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Détails d'un RDV spécifique
     */
    public function show($id)
    {
        try {
            // ✅ CORRECTION : Pas de with(['agent', 'admin'])
            $rdv = RendezVousDemande::findOrFail($id);
            
            $rdv->jours_attente = $rdv->date_soumission->diffInDays(now());
            $rdv->urgent = $rdv->statut === 'en_attente' && $rdv->jours_attente > 2;
            $rdv->peut_modifier = in_array($rdv->statut, ['en_attente', 'reporte']);

            // ✅ Créer l'objet agent factice
            $rdv->agent = (object) [
                'nom' => $rdv->user_nom,
                'prenom' => $rdv->user_prenoms,
                'prenoms' => $rdv->user_prenoms,
                'email' => $rdv->user_email,
                'telephone' => $rdv->user_telephone,
                'nom_complet' => $rdv->user_prenoms . ' ' . $rdv->user_nom,
                'matricule' => 'N/A'
            ];

            return response()->json([
                'success' => true,
                'data' => $rdv
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération RDV: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Rendez-vous non trouvé'
            ], 404);
        }
    }

    /**
     * Changer le statut d'un RDV
     */
    public function changerStatut(Request $request, $id)
    {
        $request->validate([
            'statut' => 'required|in:accepte,refuse,reporte,annule',
            'commentaire_admin' => 'nullable|string|max:1000',
            'nouvelle_date' => 'required_if:statut,reporte|date|after:today',
            'nouvelle_heure' => 'required_if:statut,reporte|string'
        ]);

        try {
            DB::beginTransaction();

            $rdv = RendezVousDemande::findOrFail($id);
            $admin = auth('admin')->user();
            $ancienStatut = $rdv->statut;

            // Vérifier que le RDV peut être modifié
            if (!in_array($rdv->statut, ['en_attente', 'reporte'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce rendez-vous ne peut plus être modifié'
                ], 400);
            }

            // ✅ CORRECTION : Utiliser les bonnes colonnes selon votre migration
            $updateData = [
                'statut' => $request->statut,
                'admin_id' => $admin->id,
                'commentaire_admin' => $request->commentaire_admin,
                'date_traitement' => now(),
                'reponse_admin' => $request->commentaire_admin, // Votre colonne existante
                'date_reponse' => now() // Votre colonne existante
            ];

            if ($request->statut === 'reporte') {
                $updateData['date_demandee'] = $request->nouvelle_date;
                $updateData['heure_demandee'] = $request->nouvelle_heure;
            }

            $rdv->update($updateData);

            // Enregistrer l'activité
            $admin->enregistrerActivite(
                'changement_statut_rdv',
                "RDV #{$rdv->id} de {$rdv->user_prenoms} {$rdv->user_nom} : {$ancienStatut} → {$request->statut}",
                $rdv
            );

            // Envoyer un message automatique à l'agent
            $this->envoyerMessageAutomatique($rdv, $admin, $request->statut);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Statut du rendez-vous modifié avec succès',
                'data' => $rdv->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur changement statut RDV: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de statut',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ CORRECTION : Envoyer un message automatique selon le statut
     */
    private function envoyerMessageAutomatique($rdv, $admin, $statut)
    {

         // Vérifier que le RDV a un user_id valide
    if (!$rdv->user_id) {
        Log::warning('Impossible d\'envoyer un message automatique : user_id manquant pour RDV #' . $rdv->id);
        return;
    }
        $messages = [
            'accepte' => [
                'titre' => 'Rendez-vous confirmé',
                'template' => "Bonjour {nom},\n\nVotre demande de rendez-vous du {date} à {heure} pour {motif} a été acceptée.\n\nMerci de vous présenter à l'heure avec les documents nécessaires.\n\nCordialement,\nAdministration CPPF",
                'type' => 'success'
            ],
            'refuse' => [
                'titre' => 'Rendez-vous refusé',
                'template' => "Bonjour {nom},\n\nNous regrettons de vous informer que votre demande de rendez-vous du {date} à {heure} ne peut être accordée.\n\nRaison: {commentaire}\n\nVous pouvez soumettre une nouvelle demande si nécessaire.\n\nCordialement,\nAdministration CPPF",
                'type' => 'error'
            ],
            'reporte' => [
                'titre' => 'Rendez-vous reporté',
                'template' => "Bonjour {nom},\n\nVotre rendez-vous initialement prévu le {ancienne_date} a été reporté au {nouvelle_date} à {nouvelle_heure}.\n\nRaison du report: {commentaire}\n\nMerci de noter cette nouvelle date.\n\nCordialement,\nAdministration CPPF",
                'type' => 'warning'
            ],
            'annule' => [
                'titre' => 'Rendez-vous annulé',
                'template' => "Bonjour {nom},\n\nVotre rendez-vous du {date} à {heure} a été annulé.\n\nRaison: {commentaire}\n\nVous pouvez prendre un nouveau rendez-vous si nécessaire.\n\nCordialement,\nAdministration CPPF",
                'type' => 'error'
            ]
        ];

        if (!isset($messages[$statut])) {
            return;
        }

        $messageConfig = $messages[$statut];
        
        // ✅ CORRECTION : Utiliser user_prenoms et user_nom
        $nomComplet = $rdv->user_prenoms . ' ' . $rdv->user_nom;
        
        // Remplacer les variables dans le template
        $contenu = str_replace([
            '{nom}',
            '{date}',
            '{heure}',
            '{motif}',
            '{commentaire}',
            '{ancienne_date}',
            '{nouvelle_date}',
            '{nouvelle_heure}'
        ], [
            $nomComplet,
            $rdv->getOriginal('date_demandee') ?? $rdv->date_demandee,
            $rdv->getOriginal('heure_demandee') ?? $rdv->heure_demandee,
            $rdv->motif,
            $rdv->commentaire_admin ?? 'Non spécifiée',
            $rdv->getOriginal('date_demandee'),
            $rdv->date_demandee,
            $rdv->heure_demandee
        ], $messageConfig['template']);

        try {
        MessageDashboard::create([
            'admin_id' => $admin->id,
            'user_type' => $rdv->user_type,
            'user_id' => $rdv->user_id,
            'titre' => $messageConfig['titre'],
            'message' => $contenu,
            'type' => $messageConfig['type'],
            'statut' => 'envoye',
            'priority' => $statut === 'refuse' ? 'high' : 'normal'
        ]);
        
        Log::info('Message automatique envoyé pour RDV #' . $rdv->id);
        
    } catch (\Exception $e) {
        Log::error('Erreur envoi message automatique : ' . $e->getMessage(), [
            'rdv_id' => $rdv->id,
            'user_id' => $rdv->user_id,
            'user_type' => $rdv->user_type
        ]);
    }
    }

    /**
     * Statistiques des RDV
     */
    public function statistiques()
    {
        try {
            $today = now();
            $startOfMonth = $today->copy()->startOfMonth();

            $stats = [
                'globales' => [
                    'total_rdv' => RendezVousDemande::count(),
                    'en_attente' => RendezVousDemande::where('statut', 'en_attente')->count(),
                    'acceptes' => RendezVousDemande::where('statut', 'accepte')->count(),
                    'refuses' => RendezVousDemande::where('statut', 'refuse')->count(),
                    'reportes' => RendezVousDemande::where('statut', 'reporte')->count(),
                    'urgents' => RendezVousDemande::where('statut', 'en_attente')
                        ->where('date_soumission', '<', $today->copy()->subDays(2))->count()
                ],
                'periode' => [
                    'nouveaux_ce_mois' => RendezVousDemande::where('date_soumission', '>=', $startOfMonth)->count(),
                    'traites_ce_mois' => RendezVousDemande::whereNotNull('date_traitement')
                        ->where('date_traitement', '>=', $startOfMonth)->count()
                ],
                'par_motif' => RendezVousDemande::select('motif', DB::raw('count(*) as total'))
                    ->groupBy('motif')->get(),
                'par_statut' => RendezVousDemande::select('statut', DB::raw('count(*) as total'))
                    ->groupBy('statut')->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur statistiques RDV: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques'
            ], 500);
        }
    }
}