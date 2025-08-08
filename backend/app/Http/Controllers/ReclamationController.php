<?php
// Mise à jour du ReclamationController avec email et suppression

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reclamation;
use App\Models\Agent;
use App\Models\Retraite;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReclamationCreatedMail;
use App\Mail\ReclamationStatusChangedMail;
use App\Mail\ReclamationDeletedMail;
use Carbon\Carbon;

class ReclamationController extends Controller
{
    /**
     * Obtenir les types de réclamations
     */
    public function getTypesReclamations()
    {
        return response()->json([
            'success' => true,
            'types_reclamations' => Reclamation::$typesReclamations
        ]);
    }

    /**
     * Obtenir les réclamations de l'utilisateur
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $userType = $user instanceof Agent ? 'agent' : 'retraite';

            $query = Reclamation::pourUtilisateur($user->id, $userType)
                               ->with('historique')
                               ->orderBy('date_soumission', 'desc');

            // Filtres optionnels
            if ($request->has('statut') && $request->statut !== '') {
                $query->where('statut', $request->statut);
            }

            if ($request->has('type') && $request->type !== '') {
                $query->where('type_reclamation', $request->type);
            }

            $reclamations = $query->paginate(10);

            // Transformer les données pour le frontend
            $reclamationsFormatted = $reclamations->map(function ($reclamation) {
                return [
                    'id' => $reclamation->id,
                    'numero_reclamation' => $reclamation->numero_reclamation,
                    'type_reclamation' => $reclamation->type_reclamation,
                    'type_reclamation_info' => $reclamation->type_reclamation_info,
                    'sujet_personnalise' => $reclamation->sujet_personnalise,
                    'description' => $reclamation->description,
                    'statut' => $reclamation->statut,
                    'statut_libelle' => $reclamation->statut_libelle,
                    'couleur_statut' => $reclamation->couleur_statut,
                    'priorite' => $reclamation->priorite,
                    'priorite_info' => $reclamation->priorite_info,
                    'documents' => $reclamation->documents,
                    'date_soumission' => $reclamation->date_soumission->format('Y-m-d H:i:s'),
                    'date_soumission_formatee' => $reclamation->date_soumission->format('d/m/Y à H:i'),
                    'temps_ecoule' => $reclamation->temps_ecoule,
                    'en_cours' => $reclamation->en_cours,
                    'peut_supprimer' => $this->peutSupprimer($reclamation),
                    'historique' => $reclamation->historique->map(function ($hist) {
                        return [
                            'id' => $hist->id,
                            'ancien_statut' => $hist->ancien_statut_libelle,
                            'nouveau_statut' => $hist->nouveau_statut_libelle,
                            'commentaire' => $hist->commentaire,
                            'date' => $hist->created_at->format('d/m/Y à H:i'),
                            'modifie_par' => $hist->modifie_par
                        ];
                    })
                ];
            });

            return response()->json([
                'success' => true,
                'reclamations' => $reclamationsFormatted,
                'pagination' => [
                    'current_page' => $reclamations->currentPage(),
                    'last_page' => $reclamations->lastPage(),
                    'per_page' => $reclamations->perPage(),
                    'total' => $reclamations->total()
                ],
                'statistiques' => $this->getStatistiques($user->id, $userType)
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des réclamations:', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des réclamations'
            ], 500);
        }
    }

    /**
     * Créer une nouvelle réclamation
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            $userType = $user instanceof Agent ? 'agent' : 'retraite';

            // Validation
            $validator = Validator::make($request->all(), [
                'type_reclamation' => 'required|string|in:' . implode(',', array_keys(Reclamation::$typesReclamations)),
                'sujet_personnalise' => 'nullable|string|max:255',
                'description' => 'required|string|min:10|max:2000',
                'priorite' => 'nullable|in:basse,normale,haute,urgente',
                'documents.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120' // 5MB max
            ], [
                'type_reclamation.required' => 'Le type de réclamation est obligatoire',
                'type_reclamation.in' => 'Type de réclamation invalide',
                'description.required' => 'La description est obligatoire',
                'description.min' => 'La description doit contenir au moins 10 caractères',
                'description.max' => 'La description ne peut pas dépasser 2000 caractères',
                'documents.*.mimes' => 'Seuls les fichiers PDF, DOC, DOCX, JPG, JPEG et PNG sont autorisés',
                'documents.*.max' => 'Chaque fichier ne peut pas dépasser 5MB'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Vérifier si le type nécessite un document
            $typeInfo = Reclamation::$typesReclamations[$request->type_reclamation];
            $necessiteDocument = $typeInfo['necessite_document'];

            if ($necessiteDocument && !$request->hasFile('documents')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce type de réclamation nécessite au moins un document justificatif'
                ], 422);
            }

            // Créer la réclamation
            $reclamation = new Reclamation([
                'user_id' => $user->id,
                'user_type' => $userType,
                'user_email' => $user->email,
                'user_telephone' => $user->telephone,
                'numero_reclamation' => Reclamation::genererNumeroReclamation(),
                'type_reclamation' => $request->type_reclamation,
                'sujet_personnalise' => $request->sujet_personnalise,
                'description' => $request->description,
                'priorite' => $request->priorite ?? 'normale',
                'statut' => 'en_attente',
                'necessite_document' => $necessiteDocument,
                'date_soumission' => now()
            ]);

            // Traitement des documents
            $documentsUrls = [];
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('reclamations/' . $user->id, $filename, 'public');
                    
                    $documentsUrls[] = [
                        'nom_original' => $file->getClientOriginalName(),
                        'nom_stocke' => $filename,
                        'chemin' => $path,
                        'url' => Storage::url($path),
                        'taille' => $file->getSize(),
                        'type' => $file->getClientOriginalExtension(),
                        'date_upload' => now()->format('Y-m-d H:i:s')
                    ];
                }
            }

            $reclamation->documents = $documentsUrls;
            $reclamation->save();

            // Créer l'entrée d'historique initiale
            $reclamation->historique()->create([
                'ancien_statut' => null,
                'nouveau_statut' => 'en_attente',
                'commentaire' => 'Réclamation créée',
                'modifie_par' => 'Système'
            ]);

            // ✅ ENVOYER EMAIL DE NOTIFICATION
            try {
                Mail::send(new ReclamationCreatedMail($reclamation, $user));
                
                Log::info('Email de réclamation envoyé:', [
                    'reclamation_id' => $reclamation->id,
                    'numero' => $reclamation->numero_reclamation,
                    'destinataire' => config('app.reclamation_email', 'nguidjoldarryl@gmail.com')
                ]);
            } catch (\Exception $e) {
                Log::error('Erreur envoi email réclamation:', [
                    'reclamation_id' => $reclamation->id,
                    'error' => $e->getMessage()
                ]);
                // Ne pas faire échouer la création de réclamation si l'email échoue
            }

            // Mettre à jour les activités récentes
            $this->ajouterActiviteRecente($user, $userType, 'reclamation_creee', $reclamation);

            return response()->json([
                'success' => true,
                'message' => 'Votre réclamation a été soumise avec succès. Un email de confirmation a été envoyé.',
                'reclamation' => [
                    'id' => $reclamation->id,
                    'numero_reclamation' => $reclamation->numero_reclamation,
                    'statut' => $reclamation->statut_libelle,
                    'date_soumission' => $reclamation->date_soumission->format('d/m/Y à H:i'),
                    'peut_supprimer' => true // Nouvellement créée, peut être supprimée
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la réclamation:', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la soumission de votre réclamation'
            ], 500);
        }
    }

    /**
     * ✅ NOUVELLE MÉTHODE : Supprimer une réclamation
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            $userType = $user instanceof Agent ? 'agent' : 'retraite';

            // Validation du motif (optionnel)
            $validator = Validator::make($request->all(), [
                'motif' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Motif invalide',
                    'errors' => $validator->errors()
                ], 422);
            }

            $reclamation = Reclamation::pourUtilisateur($user->id, $userType)->findOrFail($id);

            // Vérifier si la réclamation peut être supprimée
            if (!$this->peutSupprimer($reclamation)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette réclamation ne peut plus être supprimée car elle est déjà en cours de traitement.'
                ], 403);
            }

            // Sauvegarder les informations pour l'email
            $numeroReclamation = $reclamation->numero_reclamation;
            $typeReclamation = $reclamation->type_reclamation_info['nom'] ?? $reclamation->type_reclamation;
            $motifSuppression = $request->motif;

            // Supprimer les documents physiques
            if ($reclamation->documents) {
                foreach ($reclamation->documents as $document) {
                    $filePath = 'public/' . $document['chemin'];
                    if (Storage::exists($filePath)) {
                        Storage::delete($filePath);
                    }
                }
            }

            // Supprimer la réclamation (l'historique sera supprimé en cascade)
            $reclamation->delete();

            // ✅ ENVOYER EMAIL DE NOTIFICATION DE SUPPRESSION
            try {
                Mail::send(new ReclamationDeletedMail($numeroReclamation, $typeReclamation, $user, $motifSuppression));
                
                Log::info('Email de suppression de réclamation envoyé:', [
                    'numero' => $numeroReclamation,
                    'user_id' => $user->id,
                    'destinataire' => config('app.reclamation_email', 'nguidjoldarryl@gmail.com')
                ]);
            } catch (\Exception $e) {
                Log::error('Erreur envoi email suppression réclamation:', [
                    'numero' => $numeroReclamation,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Réclamation supprimée avec succès. Une notification a été envoyée.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Réclamation non trouvée'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de la réclamation:', [
                'user_id' => $request->user()->id,
                'reclamation_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la réclamation'
            ], 500);
        }
    }

    /**
     * Obtenir une réclamation spécifique
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $userType = $user instanceof Agent ? 'agent' : 'retraite';

            $reclamation = Reclamation::pourUtilisateur($user->id, $userType)
                                    ->with('historique')
                                    ->findOrFail($id);

            return response()->json([
                'success' => true,
                'reclamation' => [
                    'id' => $reclamation->id,
                    'numero_reclamation' => $reclamation->numero_reclamation,
                    'type_reclamation' => $reclamation->type_reclamation,
                    'type_reclamation_info' => $reclamation->type_reclamation_info,
                    'sujet_personnalise' => $reclamation->sujet_personnalise,
                    'description' => $reclamation->description,
                    'statut' => $reclamation->statut,
                    'statut_libelle' => $reclamation->statut_libelle,
                    'couleur_statut' => $reclamation->couleur_statut,
                    'priorite' => $reclamation->priorite,
                    'priorite_info' => $reclamation->priorite_info,
                    'documents' => $reclamation->documents,
                    'date_soumission' => $reclamation->date_soumission->format('Y-m-d H:i:s'),
                    'date_soumission_formatee' => $reclamation->date_soumission->format('d/m/Y à H:i'),
                    'temps_ecoule' => $reclamation->temps_ecoule,
                    'en_cours' => $reclamation->en_cours,
                    'peut_supprimer' => $this->peutSupprimer($reclamation),
                    'commentaires_admin' => $reclamation->commentaires_admin,
                    'historique' => $reclamation->historique->map(function ($hist) {
                        return [
                            'id' => $hist->id,
                            'ancien_statut' => $hist->ancien_statut_libelle,
                            'nouveau_statut' => $hist->nouveau_statut_libelle,
                            'commentaire' => $hist->commentaire,
                            'date' => $hist->created_at->format('d/m/Y à H:i'),
                            'modifie_par' => $hist->modifie_par
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Réclamation non trouvée'
            ], 404);
        }
    }

    /**
     * Télécharger un document de réclamation
     */
    public function downloadDocument(Request $request, $id, $documentIndex)
    {
        try {
            $user = $request->user();
            $userType = $user instanceof Agent ? 'agent' : 'retraite';

            $reclamation = Reclamation::pourUtilisateur($user->id, $userType)->findOrFail($id);

            if (!isset($reclamation->documents[$documentIndex])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document non trouvé'
                ], 404);
            }

            $document = $reclamation->documents[$documentIndex];
            $path = storage_path('app/public/' . $document['chemin']);

            if (!file_exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fichier non trouvé sur le serveur'
                ], 404);
            }

            return response()->download($path, $document['nom_original']);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement'
            ], 500);
        }
    }

    /**
     * ✅ Vérifier si une réclamation peut être supprimée
     */
    private function peutSupprimer($reclamation)
    {
        // Une réclamation peut être supprimée si :
        // 1. Elle est en attente (pas encore traitée)
        // 2. Elle a été créée il y a moins de 24 heures
        // 3. Elle n'a pas de commentaires admin
        
        $maintenant = now();
        $limite24h = $reclamation->date_soumission->addHours(24);
        
        return $reclamation->statut === 'en_attente' 
               && $maintenant->lessThan($limite24h)
               && empty($reclamation->commentaires_admin);
    }

    /**
     * ✅ NOUVELLE MÉTHODE : Changer le statut d'une réclamation (pour admin)
     */
    public function changerStatut(Request $request, $id)
    {
        // Cette méthode sera utilisée par l'interface admin
        $validator = Validator::make($request->all(), [
            'nouveau_statut' => 'required|in:en_attente,en_cours,en_revision,resolu,ferme,rejete',
            'commentaire' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $reclamation = Reclamation::findOrFail($id);
            $ancienStatut = $reclamation->statut;
            
            // Changer le statut avec historique
            $reclamation->changerStatut(
                $request->nouveau_statut, 
                $request->commentaire, 
                'Administrateur'
            );

            // Envoyer email à l'utilisateur
            $user = $reclamation->user_type === 'agent' 
                   ? Agent::find($reclamation->user_id)
                   : Retraite::find($reclamation->user_id);

            if ($user) {
                $dernierHistorique = $reclamation->historique()->latest()->first();
                try {
                    Mail::send(new ReclamationStatusChangedMail($reclamation, $user, $dernierHistorique));
                } catch (\Exception $e) {
                    Log::error('Erreur envoi email changement statut:', [
                        'reclamation_id' => $reclamation->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur changement statut:', [
                'reclamation_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut'
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques
     */
    private function getStatistiques($userId, $userType)
    {
        $query = Reclamation::pourUtilisateur($userId, $userType);

        return [
            'total' => $query->count(),
            'en_attente' => (clone $query)->where('statut', 'en_attente')->count(),
            'en_cours' => (clone $query)->where('statut', 'en_cours')->count(),
            'resolues' => (clone $query)->whereIn('statut', ['resolu', 'ferme'])->count(),
            'ce_mois' => (clone $query)->whereMonth('date_soumission', now()->month)
                                     ->whereYear('date_soumission', now()->year)
                                     ->count()
        ];
    }

    /**
     * Ajouter une activité récente
     */
    private function ajouterActiviteRecente($user, $userType, $type, $reclamation)
    {
        // Cette fonction sera intégrée au système d'activités existant
        // Pour l'instant, on peut logger l'activité
        Log::info('Nouvelle activité réclamation:', [
            'user_id' => $user->id,
            'user_type' => $userType,
            'type' => $type,
            'reclamation_id' => $reclamation->id,
            'numero_reclamation' => $reclamation->numero_reclamation
        ]);
    }
}