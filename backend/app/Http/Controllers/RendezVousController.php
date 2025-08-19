<?php
// app/Http/Controllers/RendezVousController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RendezVousDemande;
use App\Models\Agent;
use App\Models\Retraite;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\RendezVousDemandeAdminMail;
use App\Mail\RendezVousReponseUserMail;
use Carbon\Carbon;

class RendezVousController extends Controller
{
    /**
     * Obtenir les informations pour la page de prise de RDV
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $userType = $user instanceof Agent ? 'agent' : 'retraite';

            // Informations utilisateur pour la section bienvenue
            $userInfo = [
                'nom_complet' => $user->prenoms . ' ' . $user->nom,
                'prenoms' => $user->prenoms,
                'nom' => $user->nom,
                'type_compte' => $userType === 'agent' ? 'Agent actif' : 'Retraité',
                'sexe' => $user->sexe ?? null,
                'situation_matrimoniale' => $user->situation_matrimoniale ?? null,
                'email' => $user->email,
                'telephone' => $user->telephone
            ];

            // Motifs disponibles
            $motifs = RendezVousDemande::$motifs;

            // Statistiques
            $stats = [
                'total_demandes' => RendezVousDemande::pourUtilisateur($user->id, $userType)->count(),
                'en_attente' => RendezVousDemande::pourUtilisateur($user->id, $userType)->enAttente()->count(),
                'acceptees' => RendezVousDemande::pourUtilisateur($user->id, $userType)->where('statut', 'accepte')->count(),
                'ce_mois' => RendezVousDemande::pourUtilisateur($user->id, $userType)
                                             ->whereMonth('date_soumission', now()->month)
                                             ->whereYear('date_soumission', now()->year)
                                             ->count()
            ];

            // Prochains RDV confirmés
            $prochainsRdv = RendezVousDemande::pourUtilisateur($user->id, $userType)
                                            ->confirmes()
                                            ->where('date_rdv_confirme', '>=', now())
                                            ->orderBy('date_rdv_confirme')
                                            ->limit(3)
                                            ->get();

            return response()->json([
                'success' => true,
                'user_info' => $userInfo,
                'motifs' => $motifs,
                'statuts' => RendezVousDemande::$statuts,
                'statistiques' => $stats,
                'prochains_rdv' => $prochainsRdv->map(function ($rdv) {
                    return [
                        'id' => $rdv->id,
                        'numero_demande' => $rdv->numero_demande,
                        'motif_complet' => $rdv->motif_complet,
                        'date_rdv_confirme' => $rdv->date_rdv_confirme,
                        'lieu_rdv' => $rdv->lieu_rdv,
                        'statut_info' => $rdv->statut_info
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('💥 Erreur chargement page RDV:', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement de la page'
            ], 500);
        }
    }

    /**
     * Obtenir les créneaux disponibles pour une date
     */
/**
 * Obtenir les créneaux disponibles pour une date
 */
public static function getCreneauxDisponibles($date)
{
    $creneaux = [];
    
    // Vérifier que c'est un jour ouvrable
    $dateCarbon = Carbon::parse($date);
    if ($dateCarbon->isWeekend()) {
        return [];
    }
    
    // Vérifier que c'est au moins 48h à l'avance
    if ($dateCarbon->diffInHours(now()) < 48) {
        return [];
    }
    
    // Générer les créneaux de 9h à 15h30 (par tranches de 30 minutes)
    for ($heure = 9; $heure < 16; $heure++) {
        for ($minute = 0; $minute < 60; $minute += 30) {
            $heureFormatee = sprintf('%02d:%02d', $heure, $minute);
            
            // Vérifier si ce créneau est disponible (pas déjà pris)
            // CORRECTION : Chercher avec différents formats possibles
            $dejaReserve = self::where('date_demandee', $date)
                              ->where(function($query) use ($heureFormatee) {
                                  $query->where('heure_demandee', $heureFormatee)
                                        ->orWhere('heure_demandee', $heureFormatee . ':00');
                              })
                              ->whereIn('statut', ['en_attente', 'accepte'])
                              ->exists();
            
            if (!$dejaReserve) {
                $creneaux[] = $heureFormatee;
            }
        }
    }
    
    return $creneaux;
}


    /**
     * Créer une nouvelle demande de rendez-vous
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            $userType = $user instanceof Agent ? 'agent' : 'retraite';

            Log::info('📅 Début création demande RDV:', [
                'user_id' => $user->id,
                'user_type' => $userType,
                'form_data' => $request->except(['documents'])
            ]);

            // Validation
            $validator = Validator::make($request->all(), [
                'date_demandee' => 'required|date|after:tomorrow',
                'heure_demandee' => 'required|date_format:H:i',
                'motif' => 'required|in:' . implode(',', array_keys(RendezVousDemande::$motifs)),
                'motif_autre' => 'required_if:motif,autre|string|max:255',
                'commentaires' => 'nullable|string|max:1000'
            ], [
                'date_demandee.required' => 'La date est obligatoire',
                'date_demandee.after' => 'La date doit être au moins 48h à l\'avance',
                'heure_demandee.required' => 'L\'heure est obligatoire',
                'heure_demandee.date_format' => 'Format d\'heure invalide',
                'motif.required' => 'Le motif est obligatoire',
                'motif.in' => 'Motif invalide',
                'motif_autre.required_if' => 'Veuillez préciser le motif',
                'commentaires.max' => 'Les commentaires ne peuvent pas dépasser 1000 caractères'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Vérifier la disponibilité du créneau
            if (!RendezVousDemande::estCreneauDisponible($request->date_demandee, $request->heure_demandee)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce créneau n\'est pas disponible'
                ], 422);
            }

            // Créer la demande
            $demande = new RendezVousDemande([
                'user_id' => $user->id,
                'user_type' => $userType,
                'user_email' => $user->email,
                'user_telephone' => $user->telephone,
                'user_nom' => $user->nom,
                'user_prenoms' => $user->prenoms,
                'numero_demande' => RendezVousDemande::genererNumeroDemande(),
                'date_demandee' => $request->date_demandee,
                'heure_demandee' => $request->heure_demandee,
                'motif' => $request->motif,
                'motif_autre' => $request->motif_autre,
                'commentaires' => $request->commentaires,
                'statut' => 'en_attente',
                'date_soumission' => now()
            ]);

            $demande->save();

            Log::info('✅ Demande RDV créée:', [
                'rdv_id' => $demande->id,
                'numero' => $demande->numero_demande
            ]);

            // Envoyer email à l'admin
            try {
                $destinataireAdmin = env('APP_RECLAMATION_EMAIL', 'nguidjoldarryl@gmail.com');
                Mail::to($destinataireAdmin)->send(new RendezVousDemandeAdminMail($demande, $user));
                
                $demande->update(['email_admin_envoye' => true]);
                Log::info('📧 Email admin RDV envoyé');
            } catch (\Exception $e) {
                Log::error('❌ Erreur envoi email admin RDV:', [
                    'rdv_id' => $demande->id,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Votre demande de rendez-vous a été soumise avec succès. Vous recevrez une réponse par email.',
                'demande' => [
                    'id' => $demande->id,
                    'numero_demande' => $demande->numero_demande,
                    'date_heure_formatee' => $demande->date_heure_formatee,
                    'motif_complet' => $demande->motif_complet,
                    'statut_info' => $demande->statut_info,
                    'date_soumission' => $demande->date_soumission->format('d/m/Y à H:i')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('💥 Erreur création demande RDV:', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la soumission de votre demande'
            ], 500);
        }
    }

    /**
     * Obtenir l'historique des demandes
     */
    public function historique(Request $request)
    {
        try {
            $user = $request->user();
            $userType = $user instanceof Agent ? 'agent' : 'retraite';

            Log::info('📋 Récupération historique RDV:', [
                'user_id' => $user->id,
                'user_type' => $userType
            ]);

            // Filtres
            $query = RendezVousDemande::pourUtilisateur($user->id, $userType)
                                    ->orderBy('date_soumission', 'desc');

            if ($request->has('statut') && $request->statut !== '' && $request->statut !== 'tous') {
                $query->where('statut', $request->statut);
            }

            if ($request->has('motif') && $request->motif !== '' && $request->motif !== 'tous') {
                $query->where('motif', $request->motif);
            }

            $demandes = $query->paginate(10);

            // Formater les demandes
            $demandesFormatees = [];
            foreach ($demandes->items() as $demande) {
                $demandesFormatees[] = [
                    'id' => $demande->id,
                    'numero_demande' => $demande->numero_demande,
                    'date_heure_formatee' => $demande->date_heure_formatee,
                    'motif_complet' => $demande->motif_complet,
                    'motif_info' => $demande->motif_info,
                    'statut' => $demande->statut,
                    'statut_info' => $demande->statut_info,
                    'commentaires' => $demande->commentaires,
                    'reponse_admin' => $demande->reponse_admin,
                    'date_soumission' => $demande->date_soumission->format('d/m/Y à H:i'),
                    'temps_ecoule' => $demande->temps_ecoule,
                    'peut_modifier' => $demande->peut_modifier,
                    'est_future' => $demande->est_future,
                    'date_rdv_confirme' => $demande->date_rdv_confirme ? 
                                         $demande->date_rdv_confirme->format('d/m/Y à H:i') : null,
                    'lieu_rdv' => $demande->lieu_rdv,
                    'date_reponse' => $demande->date_reponse ? 
                                    $demande->date_reponse->format('d/m/Y à H:i') : null
                ];
            }

            return response()->json([
                'success' => true,
                'demandes' => $demandesFormatees,
                'pagination' => [
                    'current_page' => $demandes->currentPage(),
                    'last_page' => $demandes->lastPage(),
                    'per_page' => $demandes->perPage(),
                    'total' => $demandes->total()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('💥 Erreur récupération historique RDV:', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement de l\'historique'
            ], 500);
        }
    }

    /**
     * Obtenir une demande spécifique
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $userType = $user instanceof Agent ? 'agent' : 'retraite';

            $demande = RendezVousDemande::pourUtilisateur($user->id, $userType)->findOrFail($id);

            return response()->json([
                'success' => true,
                'demande' => [
                    'id' => $demande->id,
                    'numero_demande' => $demande->numero_demande,
                    'date_heure_formatee' => $demande->date_heure_formatee,
                    'motif_complet' => $demande->motif_complet,
                    'motif_info' => $demande->motif_info,
                    'statut' => $demande->statut,
                    'statut_info' => $demande->statut_info,
                    'commentaires' => $demande->commentaires,
                    'reponse_admin' => $demande->reponse_admin,
                    'date_soumission' => $demande->date_soumission->format('d/m/Y à H:i'),
                    'temps_ecoule' => $demande->temps_ecoule,
                    'peut_modifier' => $demande->peut_modifier,
                    'est_future' => $demande->est_future,
                    'date_rdv_confirme' => $demande->date_rdv_confirme ? 
                                         $demande->date_rdv_confirme->format('d/m/Y à H:i') : null,
                    'lieu_rdv' => $demande->lieu_rdv,
                    'date_reponse' => $demande->date_reponse ? 
                                    $demande->date_reponse->format('d/m/Y à H:i') : null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Demande non trouvée'
            ], 404);
        }
    }

    /**
     * Annuler une demande (utilisateur)
     */
    public function annuler(Request $request, $id)
    {
        try {
            $user = $request->user();
            $userType = $user instanceof Agent ? 'agent' : 'retraite';

            $demande = RendezVousDemande::pourUtilisateur($user->id, $userType)->findOrFail($id);

            // Vérifier si la demande peut être annulée
            if (!$demande->peut_modifier) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette demande ne peut plus être annulée'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'motif_annulation' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Annuler la demande
            $demande->changerStatut(
                'annule',
                'Annulé par l\'utilisateur: ' . ($request->motif_annulation ?? 'Aucun motif précisé')
            );

            Log::info('🚫 Demande RDV annulée par utilisateur:', [
                'rdv_id' => $demande->id,
                'numero' => $demande->numero_demande,
                'motif' => $request->motif_annulation
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Votre demande de rendez-vous a été annulée avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('💥 Erreur annulation RDV:', [
                'rdv_id' => $id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation'
            ], 500);
        }
    }

    /**
     * Changer le statut d'une demande (admin uniquement)
     */
    public function changerStatut(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nouveau_statut' => 'required|in:en_attente,accepte,refuse,reporte,annule',
                'reponse_admin' => 'nullable|string|max:1000',
                'date_rdv_confirme' => 'nullable|date|after:now',
                'lieu_rdv' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $demande = RendezVousDemande::findOrFail($id);

            // Changer le statut
            $demande->changerStatut(
                $request->nouveau_statut,
                $request->reponse_admin,
                $request->date_rdv_confirme,
                $request->lieu_rdv
            );

            // Envoyer email à l'utilisateur
            try {
                $user = $demande->user_type === 'agent' 
                       ? Agent::find($demande->user_id)
                       : Retraite::find($demande->user_id);

                if ($user) {
                    Mail::to($user->email)->send(new RendezVousReponseUserMail($demande, $user));
                    $demande->update(['email_user_reponse_envoye' => true]);
                    Log::info('📧 Email réponse RDV envoyé à l\'utilisateur');
                }
            } catch (\Exception $e) {
                Log::error('❌ Erreur envoi email réponse RDV:', [
                    'rdv_id' => $demande->id,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('💥 Erreur changement statut RDV:', [
                'rdv_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut'
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques pour l'admin
     */
    public function statistiquesAdmin(Request $request)
    {
        try {
            $stats = [
                'total' => RendezVousDemande::count(),
                'en_attente' => RendezVousDemande::where('statut', 'en_attente')->count(),
                'acceptees' => RendezVousDemande::where('statut', 'accepte')->count(),
                'refusees' => RendezVousDemande::where('statut', 'refuse')->count(),
                'ce_mois' => RendezVousDemande::whereMonth('date_soumission', now()->month)
                                            ->whereYear('date_soumission', now()->year)
                                            ->count(),
                'cette_semaine' => RendezVousDemande::whereBetween('date_soumission', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
                'aujourd_hui' => RendezVousDemande::whereDate('date_soumission', now())->count()
            ];

            // Répartition par motif
            $repartitionMotifs = [];
            foreach (RendezVousDemande::$motifs as $key => $motif) {
                $repartitionMotifs[$key] = [
                    'nom' => $motif['nom'],
                    'count' => RendezVousDemande::where('motif', $key)->count()
                ];
            }

            // Prochains RDV confirmés
            $prochainsConfirmes = RendezVousDemande::where('statut', 'accepte')
                                                  ->where('date_rdv_confirme', '>=', now())
                                                  ->orderBy('date_rdv_confirme')
                                                  ->limit(10)
                                                  ->get();

            return response()->json([
                'success' => true,
                'statistiques' => $stats,
                'repartition_motifs' => $repartitionMotifs,
                'prochains_confirmes' => $prochainsConfirmes->map(function ($rdv) {
                    return [
                        'id' => $rdv->id,
                        'numero_demande' => $rdv->numero_demande,
                        'user_nom_complet' => $rdv->user_prenoms . ' ' . $rdv->user_nom,
                        'user_type' => $rdv->user_type,
                        'motif_complet' => $rdv->motif_complet,
                        'date_rdv_confirme' => $rdv->date_rdv_confirme->format('d/m/Y à H:i'),
                        'lieu_rdv' => $rdv->lieu_rdv
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('💥 Erreur statistiques admin RDV:', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des statistiques'
            ], 500);
        }
    }

    /**
     * Obtenir toutes les demandes pour l'admin
     */
    public function indexAdmin(Request $request)
    {
        try {
            Log::info('📋 [ADMIN] Récupération toutes les demandes RDV');

            // Filtres
            $query = RendezVousDemande::orderBy('date_soumission', 'desc');

            if ($request->has('statut') && $request->statut !== '' && $request->statut !== 'tous') {
                $query->where('statut', $request->statut);
            }

            if ($request->has('motif') && $request->motif !== '' && $request->motif !== 'tous') {
                $query->where('motif', $request->motif);
            }

            if ($request->has('user_type') && $request->user_type !== '' && $request->user_type !== 'tous') {
                $query->where('user_type', $request->user_type);
            }

            if ($request->has('date_debut') && $request->date_debut) {
                $query->whereDate('date_soumission', '>=', $request->date_debut);
            }

            if ($request->has('date_fin') && $request->date_fin) {
                $query->whereDate('date_soumission', '<=', $request->date_fin);
            }

            $demandes = $query->paginate($request->per_page ?? 15);

            // Formater les demandes
            $demandesFormatees = [];
            foreach ($demandes->items() as $demande) {
                $demandesFormatees[] = [
                    'id' => $demande->id,
                    'numero_demande' => $demande->numero_demande,
                    'user_nom_complet' => $demande->user_prenoms . ' ' . $demande->user_nom,
                    'user_type' => $demande->user_type,
                    'user_type_libelle' => $demande->user_type === 'agent' ? 'Agent actif' : 'Retraité',
                    'user_email' => $demande->user_email,
                    'user_telephone' => $demande->user_telephone,
                    'date_heure_formatee' => $demande->date_heure_formatee,
                    'motif_complet' => $demande->motif_complet,
                    'motif_info' => $demande->motif_info,
                    'statut' => $demande->statut,
                    'statut_info' => $demande->statut_info,
                    'commentaires' => $demande->commentaires,
                    'reponse_admin' => $demande->reponse_admin,
                    'date_soumission' => $demande->date_soumission->format('d/m/Y à H:i'),
                    'temps_ecoule' => $demande->temps_ecoule,
                    'est_future' => $demande->est_future,
                    'date_rdv_confirme' => $demande->date_rdv_confirme ? 
                                         $demande->date_rdv_confirme->format('d/m/Y à H:i') : null,
                    'lieu_rdv' => $demande->lieu_rdv,
                    'date_reponse' => $demande->date_reponse ? 
                                    $demande->date_reponse->format('d/m/Y à H:i') : null,
                    'email_admin_envoye' => $demande->email_admin_envoye,
                    'email_user_reponse_envoye' => $demande->email_user_reponse_envoye
                ];
            }

            return response()->json([
                'success' => true,
                'demandes' => $demandesFormatees,
                'pagination' => [
                    'current_page' => $demandes->currentPage(),
                    'last_page' => $demandes->lastPage(),
                    'per_page' => $demandes->perPage(),
                    'total' => $demandes->total(),
                    'from' => $demandes->firstItem(),
                    'to' => $demandes->lastItem()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('💥 [ADMIN] Erreur récupération demandes RDV:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des demandes'
            ], 500);
        }
    }

    /**
     * Exporter les demandes RDV (admin)
     */
    public function export(Request $request)
    {
        try {
            $query = RendezVousDemande::orderBy('date_soumission', 'desc');

            // Appliquer les mêmes filtres que pour l'index
            if ($request->has('statut') && $request->statut !== '' && $request->statut !== 'tous') {
                $query->where('statut', $request->statut);
            }

            if ($request->has('motif') && $request->motif !== '' && $request->motif !== 'tous') {
                $query->where('motif', $request->motif);
            }

            if ($request->has('user_type') && $request->user_type !== '' && $request->user_type !== 'tous') {
                $query->where('user_type', $request->user_type);
            }

            if ($request->has('date_debut') && $request->date_debut) {
                $query->whereDate('date_soumission', '>=', $request->date_debut);
            }

            if ($request->has('date_fin') && $request->date_fin) {
                $query->whereDate('date_soumission', '<=', $request->date_fin);
            }

            $demandes = $query->get();

            // Préparer les données pour l'export CSV
            $csvData = [];
            $csvData[] = [
                'Numéro',
                'Nom complet',
                'Type utilisateur',
                'Email',
                'Téléphone',
                'Date demandée',
                'Heure demandée',
                'Motif',
                'Statut',
                'Date soumission',
                'Date RDV confirmé',
                'Lieu RDV',
                'Commentaires',
                'Réponse admin'
            ];

            foreach ($demandes as $demande) {
                $csvData[] = [
                    $demande->numero_demande,
                    $demande->user_prenoms . ' ' . $demande->user_nom,
                    $demande->user_type === 'agent' ? 'Agent actif' : 'Retraité',
                    $demande->user_email,
                    $demande->user_telephone ?? '',
                    $demande->date_demandee->format('d/m/Y'),
                    $demande->heure_demandee,
                    $demande->motif_complet,
                    $demande->statut_info['nom'],
                    $demande->date_soumission->format('d/m/Y H:i'),
                    $demande->date_rdv_confirme ? $demande->date_rdv_confirme->format('d/m/Y H:i') : '',
                    $demande->lieu_rdv ?? '',
                    $demande->commentaires ?? '',
                    $demande->reponse_admin ?? ''
                ];
            }

            // Générer le fichier CSV
            $filename = 'rendez_vous_' . now()->format('Y-m-d_H-i-s') . '.csv';
            $handle = fopen('php://temp', 'w+');
            
            foreach ($csvData as $row) {
                fputcsv($handle, $row, ';'); // Utiliser ';' pour Excel français
            }
            
            rewind($handle);
            $csvContent = stream_get_contents($handle);
            fclose($handle);

            return response($csvContent)
                ->header('Content-Type', 'text/csv; charset=UTF-8')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($csvContent));

        } catch (\Exception $e) {
            Log::error('💥 Erreur export RDV:', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export'
            ], 500);
        }
    }

    /**
     * Obtenir les créneaux occupés pour une date (admin)
     */
    public function getCreneauxOccupes(Request $request, $date)
    {
        try {
            $validator = Validator::make(['date' => $date], [
                'date' => 'required|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Date invalide',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Récupérer les RDV confirmés pour cette date
            $rdvConfirmes = RendezVousDemande::where('statut', 'accepte')
                                           ->whereDate('date_rdv_confirme', $date)
                                           ->orderBy('date_rdv_confirme')
                                           ->get();

            return response()->json([
                'success' => true,
                'date' => $date,
                'rdv_confirmes' => $rdvConfirmes->map(function ($rdv) {
                    return [
                        'id' => $rdv->id,
                        'numero_demande' => $rdv->numero_demande,
                        'user_nom_complet' => $rdv->user_prenoms . ' ' . $rdv->user_nom,
                        'heure' => $rdv->date_rdv_confirme->format('H:i'),
                        'motif_complet' => $rdv->motif_complet,
                        'lieu_rdv' => $rdv->lieu_rdv
                    ];
                }),
                'total_rdv' => $rdvConfirmes->count()
            ]);

        } catch (\Exception $e) {
            Log::error('💥 Erreur récupération créneaux occupés:', [
                'date' => $date,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des créneaux occupés'
            ], 500);
        }
    }

    /**
     * Rechercher des demandes (admin)
     */
    public function rechercher(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'terme' => 'required|string|min:3',
                'champ' => 'nullable|in:numero,nom,email,motif,all'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $terme = $request->terme;
            $champ = $request->champ ?? 'all';

            $query = RendezVousDemande::query();

            switch ($champ) {
                case 'numero':
                    $query->where('numero_demande', 'LIKE', "%{$terme}%");
                    break;
                case 'nom':
                    $query->where(function ($q) use ($terme) {
                        $q->where('user_nom', 'LIKE', "%{$terme}%")
                          ->orWhere('user_prenoms', 'LIKE', "%{$terme}%");
                    });
                    break;
                case 'email':
                    $query->where('user_email', 'LIKE', "%{$terme}%");
                    break;
                case 'motif':
                    $query->where('motif_autre', 'LIKE', "%{$terme}%")
                          ->orWhere('commentaires', 'LIKE', "%{$terme}%");
                    break;
                default: // 'all'
                    $query->where(function ($q) use ($terme) {
                        $q->where('numero_demande', 'LIKE', "%{$terme}%")
                          ->orWhere('user_nom', 'LIKE', "%{$terme}%")
                          ->orWhere('user_prenoms', 'LIKE', "%{$terme}%")
                          ->orWhere('user_email', 'LIKE', "%{$terme}%")
                          ->orWhere('motif_autre', 'LIKE', "%{$terme}%")
                          ->orWhere('commentaires', 'LIKE', "%{$terme}%");
                    });
                    break;
            }

            $resultats = $query->orderBy('date_soumission', 'desc')
                              ->limit(20)
                              ->get();

            return response()->json([
                'success' => true,
                'terme_recherche' => $terme,
                'champ_recherche' => $champ,
                'resultats' => $resultats->map(function ($demande) {
                    return [
                        'id' => $demande->id,
                        'numero_demande' => $demande->numero_demande,
                        'user_nom_complet' => $demande->user_prenoms . ' ' . $demande->user_nom,
                        'user_email' => $demande->user_email,
                        'motif_complet' => $demande->motif_complet,
                        'statut_info' => $demande->statut_info,
                        'date_soumission' => $demande->date_soumission->format('d/m/Y à H:i'),
                        'date_heure_formatee' => $demande->date_heure_formatee
                    ];
                }),
                'total_resultats' => $resultats->count()
            ]);

        } catch (\Exception $e) {
            Log::error('💥 Erreur recherche RDV:', [
                'terme' => $request->terme,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche'
            ], 500);
        }
    }

    /**
     * Supprimer définitivement une demande (admin uniquement)
     */
    public function destroy(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'motif_suppression' => 'required|string|max:500',
                'confirmation' => 'required|boolean|accepted'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $demande = RendezVousDemande::findOrFail($id);

            // Sauvegarder les informations pour les logs
            $infosSupprimes = [
                'numero_demande' => $demande->numero_demande,
                'user_nom_complet' => $demande->user_prenoms . ' ' . $demande->user_nom,
                'user_email' => $demande->user_email,
                'motif_complet' => $demande->motif_complet,
                'statut' => $demande->statut,
                'date_soumission' => $demande->date_soumission->format('d/m/Y H:i'),
                'motif_suppression' => $request->motif_suppression
            ];

            // Supprimer la demande
            $demande->delete();

            Log::warning('🗑️ [ADMIN] Demande RDV supprimée définitivement:', $infosSupprimes);

            return response()->json([
                'success' => true,
                'message' => 'Demande de rendez-vous supprimée définitivement'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Demande non trouvée'
            ], 404);
        } catch (\Exception $e) {
            Log::error('💥 Erreur suppression RDV:', [
                'rdv_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }

    /**
     * Obtenir un rapport détaillé (admin)
     */
    public function rapport(Request $request)
    {
        try {
            $dateDebut = $request->date_debut ?? now()->startOfMonth()->format('Y-m-d');
            $dateFin = $request->date_fin ?? now()->endOfMonth()->format('Y-m-d');

            // Statistiques générales
            $statsGenerales = [
                'total_demandes' => RendezVousDemande::whereBetween('date_soumission', [$dateDebut, $dateFin])->count(),
                'total_acceptees' => RendezVousDemande::whereBetween('date_soumission', [$dateDebut, $dateFin])->where('statut', 'accepte')->count(),
                'total_refusees' => RendezVousDemande::whereBetween('date_soumission', [$dateDebut, $dateFin])->where('statut', 'refuse')->count(),
                'total_en_attente' => RendezVousDemande::whereBetween('date_soumission', [$dateDebut, $dateFin])->where('statut', 'en_attente')->count(),
                'total_annulees' => RendezVousDemande::whereBetween('date_soumission', [$dateDebut, $dateFin])->where('statut', 'annule')->count()
            ];

            // Calcul du taux d'acceptation
            $statsGenerales['taux_acceptation'] = $statsGenerales['total_demandes'] > 0 
                ? round(($statsGenerales['total_acceptees'] / $statsGenerales['total_demandes']) * 100, 2) 
                : 0;

            // Répartition par type d'utilisateur
            $repartitionUserType = [
                'agents' => RendezVousDemande::whereBetween('date_soumission', [$dateDebut, $dateFin])->where('user_type', 'agent')->count(),
                'retraites' => RendezVousDemande::whereBetween('date_soumission', [$dateDebut, $dateFin])->where('user_type', 'retraite')->count()
            ];

            // Répartition par motif
            $repartitionMotifs = [];
            foreach (RendezVousDemande::$motifs as $key => $motif) {
                $count = RendezVousDemande::whereBetween('date_soumission', [$dateDebut, $dateFin])
                                        ->where('motif', $key)
                                        ->count();
                if ($count > 0) {
                    $repartitionMotifs[] = [
                        'motif' => $motif['nom'],
                        'count' => $count,
                        'pourcentage' => $statsGenerales['total_demandes'] > 0 
                            ? round(($count / $statsGenerales['total_demandes']) * 100, 2) 
                            : 0
                    ];
                }
            }

            // Évolution par jour
            $evolutionJours = [];
            $dateTemp = Carbon::parse($dateDebut);
            while ($dateTemp->lte(Carbon::parse($dateFin))) {
                $count = RendezVousDemande::whereDate('date_soumission', $dateTemp->format('Y-m-d'))->count();
                $evolutionJours[] = [
                    'date' => $dateTemp->format('d/m/Y'),
                    'count' => $count
                ];
                $dateTemp->addDay();
            }

            // Temps de traitement moyen
            $demandesTraitees = RendezVousDemande::whereBetween('date_soumission', [$dateDebut, $dateFin])
                                               ->whereNotNull('date_reponse')
                                               ->get();

            $tempsTraitementMoyen = 0;
            if ($demandesTraitees->count() > 0) {
                $totalHeures = 0;
                foreach ($demandesTraitees as $demande) {
                    $totalHeures += $demande->date_soumission->diffInHours($demande->date_reponse);
                }
                $tempsTraitementMoyen = round($totalHeures / $demandesTraitees->count(), 2);
            }

            return response()->json([
                'success' => true,
                'periode' => [
                    'date_debut' => $dateDebut,
                    'date_fin' => $dateFin
                ],
                'statistiques_generales' => $statsGenerales,
                'repartition_user_type' => $repartitionUserType,
                'repartition_motifs' => $repartitionMotifs,
                'evolution_jours' => $evolutionJours,
                'temps_traitement_moyen_heures' => $tempsTraitementMoyen,
                'date_generation' => now()->format('d/m/Y à H:i')
            ]);

        } catch (\Exception $e) {
            Log::error('💥 Erreur génération rapport RDV:', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport'
            ], 500);
        }
    }
}
                