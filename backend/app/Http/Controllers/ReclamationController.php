<?php
// Mise à jour du ReclamationController avec accusés de réception

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
use App\Mail\AccuseReceptionMail; 
use App\Services\AccuseReceptionService; 
use Carbon\Carbon;
use App\Models\ReclamationHistorique; 


class ReclamationController extends Controller
{
    protected $accuseService;

    public function __construct()
    {
        
        try {
            $this->accuseService = app(AccuseReceptionService::class);
        } catch (\Exception $e) {
            Log::error('❌ Erreur initialisation AccuseReceptionService:', ['error' => $e->getMessage()]);
            $this->accuseService = null;
        }
    }

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

        Log::info('🔍 [BACKEND] Récupération réclamations:', [
            'user_id' => $user->id,
            'user_type' => $userType,
            'user_class' => get_class($user),
            'filtres' => $request->all()
        ]);

        // ✅ FILTRES CORRIGÉS - Ne pas appliquer si vide
        $query = Reclamation::where('user_id', $user->id)
                           ->where('user_type', $userType)
                           ->orderBy('date_soumission', 'desc');

        // ✅ Filtre statut : seulement si non vide ET différent de "Tous"
        if ($request->has('statut') && $request->statut !== '' && $request->statut !== 'Tous') {
            $query->where('statut', $request->statut);
            Log::info('📋 Filtre statut appliqué:', ['statut' => $request->statut]);
        }

        // ✅ Filtre type : seulement si non vide ET différent de "Tous" 
        if ($request->has('type') && $request->type !== '' && $request->type !== 'Tous') {
            $query->where('type_reclamation', $request->type);
            Log::info('📋 Filtre type appliqué:', ['type' => $request->type]);
        }

        // Debug AVANT exécution
        Log::info('🔍 [SQL] Requête avant exécution:', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'request_statut' => $request->statut,
            'request_type' => $request->type,
            'has_statut' => $request->has('statut'),
            'has_type' => $request->has('type')
        ]);

        $reclamations = $query->paginate(10);

        Log::info('📊 [BACKEND] Réclamations trouvées:', [
            'count_collection' => $reclamations->count(),
            'total' => $reclamations->total(),
            'current_page' => $reclamations->currentPage(),
            'per_page' => $reclamations->perPage(),
            'last_page' => $reclamations->lastPage()
        ]);

        // ✅ TRANSFORMATION SIMPLIFIÉE avec accusé de réception
        $reclamationsFormatted = [];
        
        foreach ($reclamations->items() as $reclamation) {
            try {
                $formatted = [
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
                    'documents' => $reclamation->documents ?? [],
                    'date_soumission' => $reclamation->date_soumission->format('Y-m-d H:i:s'),
                    'date_soumission_formatee' => $reclamation->date_soumission->format('d/m/Y à H:i'),
                    'date_traitement' => $reclamation->date_traitement,
                    'date_traitement_formatee' => $reclamation->date_traitement ? $reclamation->date_traitement->format('d/m/Y à H:i') : null,
                    'reponse_admin' => $reclamation->reponse_admin, // ← IMPORTANT
                    'temps_ecoule' => $reclamation->temps_ecoule,
                    'en_cours' => $reclamation->en_cours,
                    'peut_supprimer' => $this->peutSupprimer($reclamation),
                    'peut_telecharger_accuse' => true, // ✅ Toujours possible de télécharger l'accusé
                    'historique' => [] // Temporairement vide pour éviter les erreurs
                ];
                
                $reclamationsFormatted[] = $formatted;
                
                Log::info("✅ [BACKEND] Réclamation formatée: {$reclamation->numero_reclamation}");
                
            } catch (\Exception $e) {
                Log::error("❌ [BACKEND] Erreur formatage réclamation {$reclamation->id}:", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        $statistiques = $this->getStatistiques($user->id, $userType);

        Log::info('📊 [BACKEND] Statistiques calculées:', $statistiques);
        Log::info('📋 [BACKEND] Nombre final réclamations formatées:', ['count' => count($reclamationsFormatted)]);

        // ✅ MISE À JOUR : Infos utilisateur avec sexe et situation matrimoniale
        $userInfo = [
            'nom_complet' => $user->prenoms . ' ' . $user->nom,
            'type_compte' => $userType === 'agent' ? 'Agent actif' : 'Retraité',
            // ✅ NOUVEAU : Ajouter le sexe et la situation matrimoniale
            'sexe' => $user->sexe ?? null,
            'situation_matrimoniale' => $user->situation_matrimoniale ?? null,
            // ✅ NOUVEAU : Infos supplémentaires pour debug
            'prenoms' => $user->prenoms ?? '',
            'nom' => $user->nom ?? '',
            'email' => $user->email ?? ''
        ];

        // ✅ DEBUG : Log des infos utilisateur
        Log::info('👤 [BACKEND] Infos utilisateur générées:', [
            'user_info' => $userInfo,
            'user_attributes' => $user->getAttributes() // Pour voir tous les champs disponibles
        ]);

        $response = [
            'success' => true,
            'reclamations' => $reclamationsFormatted,
            'pagination' => [
                'current_page' => $reclamations->currentPage(),
                'last_page' => $reclamations->lastPage(),
                'per_page' => $reclamations->perPage(),
                'total' => $reclamations->total()
            ],
            'statistiques' => $statistiques,
            'user_info' => $userInfo // ✅ Infos utilisateur mises à jour
        ];

        Log::info('🎯 [BACKEND] Réponse finale:', [
            'success' => $response['success'],
            'reclamations_count' => count($response['reclamations']),
            'pagination' => $response['pagination'],
            'statistiques' => $response['statistiques'],
            'user_info' => $response['user_info'] // ✅ Log des infos utilisateur dans la réponse
        ]);

        return response()->json($response);

    } catch (\Exception $e) {
        Log::error('💥 [BACKEND] Erreur lors de la récupération des réclamations:', [
            'user_id' => $request->user()?->id,
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du chargement des réclamations',
            'error' => $e->getMessage()
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

            Log::info('🔄 [STORE] Début création réclamation:', [
                'user_id' => $user->id,
                'user_type' => $userType,
                'form_data' => $request->except(['documents'])
            ]);

            // Validation
            $validator = Validator::make($request->all(), [
                'type_reclamation' => 'required|string|in:' . implode(',', array_keys(Reclamation::$typesReclamations)),
                'sujet_personnalise' => 'nullable|string|max:255',
                'description' => 'required|string|min:10|max:2000',
                'priorite' => 'nullable|in:basse,normale,haute,urgente',
                'documents.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120'
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

            Log::info('✅ [STORE] Réclamation créée:', [
                'reclamation_id' => $reclamation->id,
                'numero' => $reclamation->numero_reclamation
            ]);

            // Créer l'entrée d'historique initiale
            $reclamation->historique()->create([
                'ancien_statut' => null,
                'nouveau_statut' => 'en_attente',
                'commentaire' => 'Réclamation créée',
                'modifie_par' => 'Système'
            ]);

            // ✅ NOUVEAU : Génération et envoi de l'accusé de réception (avec protection)
            try {
                if ($this->accuseService) {
                    Log::info('📧 [EMAIL] Début envoi accusé de réception:', [
                        'reclamation_id' => $reclamation->id,
                        'user_email' => $user->email
                    ]);

                    // Envoyer l'accusé de réception par email à l'utilisateur
                    Mail::to($user->email)->send(new AccuseReceptionMail($reclamation, $user));
                    
                    Log::info('✅ [EMAIL] Accusé de réception envoyé à l\'utilisateur');
                } else {
                    Log::warning('⚠️ [EMAIL] AccuseReceptionService non disponible - envoi simple');
                }

                // Envoyer aussi le mail traditionnel à l'admin
                $destinataireAdmin = env('APP_RECLAMATION_EMAIL', 'nguidjoldarryl@gmail.com');
                Mail::to($destinataireAdmin)->send(new ReclamationCreatedMail($reclamation, $user));
                
                Log::info('✅ [EMAIL] Mail admin envoyé');

            } catch (\Exception $e) {
                Log::error('❌ [EMAIL] Erreur envoi emails:', [
                    'reclamation_id' => $reclamation->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
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
                    'peut_supprimer' => true,
                    'peut_telecharger_accuse' => ($this->accuseService !== null) // ✅ Conditionnel
                ],
                'accuse_reception_disponible' => ($this->accuseService !== null) // ✅ Conditionnel
            ]);

        } catch (\Exception $e) {
            Log::error('💥 [STORE] Erreur lors de la création de la réclamation:', [
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
 *  Télécharger un document joint à une réclamation
 * Cette méthode gère TOUS les formats de stockage de documents
 */
public function telechargerDocument(Request $request, $id, $index)
{
    try {
        $user = $request->user();
        $userType = $user instanceof \App\Models\Agent ? 'agent' : 'retraite';

        Log::info('📥 Téléchargement document réclamation:', [
            'reclamation_id' => $id,
            'document_index' => $index,
            'user_id' => $user->id,
            'user_type' => $userType
        ]);

        // Récupérer la réclamation
        $reclamation = Reclamation::where('id', $id)
                                 ->where('user_id', $user->id)
                                 ->where('user_type', $userType)
                                 ->firstOrFail();

        // Vérifier que des documents existent
        if (empty($reclamation->documents)) {
            Log::warning('⚠️ Aucun document trouvé pour cette réclamation');
            return response()->json([
                'success' => false,
                'message' => 'Aucun document disponible'
            ], 404);
        }

        // Décoder les documents (peuvent être en JSON ou array)
        $documents = is_string($reclamation->documents) 
            ? json_decode($reclamation->documents, true) 
            : $reclamation->documents;

        Log::info('📄 Documents récupérés:', [
            'count' => count($documents),
            'index_demande' => $index,
            'format' => is_array($documents[0] ?? null) ? 'nouveau (objet)' : 'ancien (string)'
        ]);

        // Vérifier que l'index existe
        if (!isset($documents[$index])) {
            Log::warning('⚠️ Index de document invalide:', [
                'index_demande' => $index,
                'documents_count' => count($documents)
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Document non trouvé'
            ], 404);
        }

        //  Gérer les 2 formats de stockage
        $documentInfo = $documents[$index];
        $documentPath = null;
        $documentNom = null;

        // FORMAT 1 : Nouveau format (objet avec chemin, nom_original, etc.)
        if (is_array($documentInfo)) {
            // Chercher le chemin dans différentes clés possibles
            $documentPath = $documentInfo['chemin'] 
                         ?? $documentInfo['path'] 
                         ?? $documentInfo['url'] 
                         ?? null;
            
            $documentNom = $documentInfo['nom_original'] 
                        ?? $documentInfo['nom'] 
                        ?? $documentInfo['nom_stocke'] 
                        ?? basename($documentPath ?? '');

            Log::info('📎 Format nouveau (objet):', [
                'chemin' => $documentPath,
                'nom' => $documentNom
            ]);
        }
        // FORMAT 2 : Ancien format (string simple)
        else if (is_string($documentInfo)) {
            $documentPath = $documentInfo;
            $documentNom = basename($documentPath);

            Log::info('📎 Format ancien (string):', [
                'chemin' => $documentPath,
                'nom' => $documentNom
            ]);
        }

        // Vérifier qu'on a un chemin valide
        if (!$documentPath) {
            Log::error('❌ Chemin du document invalide:', [
                'document_info' => $documentInfo
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Chemin du document invalide'
            ], 500);
        }

        //  Nettoyer le chemin (enlever "public/" si présent)
        // Le fichier peut être stocké avec "public/reclamations/..." ou "reclamations/..."
        $cleanPath = str_replace('public/', '', $documentPath);
        
        // Essayer d'abord avec le chemin nettoyé
        if (Storage::disk('public')->exists($cleanPath)) {
            $fullPath = storage_path('app/public/' . $cleanPath);
            Log::info('✅ Fichier trouvé (disk public):', [
                'clean_path' => $cleanPath,
                'full_path' => $fullPath,
                'exists' => file_exists($fullPath)
            ]);

            if (file_exists($fullPath)) {
                return response()->download($fullPath, $documentNom);
            }
        }

        // Sinon essayer avec le chemin original sur le disk par défaut
        if (Storage::exists($documentPath)) {
            $fullPath = storage_path('app/' . $documentPath);
            Log::info('✅ Fichier trouvé (disk default):', [
                'path' => $documentPath,
                'full_path' => $fullPath,
                'exists' => file_exists($fullPath)
            ]);

            if (file_exists($fullPath)) {
                return response()->download($fullPath, $documentNom);
            }
        }

        // Si rien n'a fonctionné, le fichier n'existe pas
        Log::error('❌ Fichier introuvable sur le disque:', [
            'original_path' => $documentPath,
            'clean_path' => $cleanPath,
            'checked_paths' => [
                'public' => storage_path('app/public/' . $cleanPath),
                'default' => storage_path('app/' . $documentPath)
            ]
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Fichier introuvable sur le serveur'
        ], 404);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        Log::error('❌ Réclamation non trouvée ou accès non autorisé:', [
            'reclamation_id' => $id,
            'user_id' => $request->user()?->id
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Réclamation non trouvée ou accès non autorisé'
        ], 404);

    } catch (\Exception $e) {
        Log::error('❌ Erreur lors du téléchargement du document:', [
            'reclamation_id' => $id,
            'document_index' => $index,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du téléchargement: ' . $e->getMessage()
        ], 500);
    }
}



    /**
     *  Télécharger l'accusé de réception
     */
    public function telechargerAccuseReception(Request $request, $id)
    {
        try {
            // ✅ Vérification du service
            if (!$this->accuseService) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service d\'accusé de réception non disponible'
                ], 503);
            }

            $user = $request->user();
            $userType = $user instanceof Agent ? 'agent' : 'retraite';

            Log::info('📥 [ACCUSE] Demande téléchargement accusé:', [
                'reclamation_id' => $id,
                'user_id' => $user->id
            ]);

            $reclamation = Reclamation::pourUtilisateur($user->id, $userType)->findOrFail($id);

            // Générer l'accusé de réception
            $accuseData = $this->accuseService->telechargerAccuse($reclamation, $user);

            Log::info('✅ [ACCUSE] Accusé généré pour téléchargement:', [
                'reclamation' => $reclamation->numero_reclamation,
                'filename' => $accuseData['filename']
            ]);

            return response($accuseData['content'])
                ->header('Content-Type', $accuseData['mime_type'])
                ->header('Content-Disposition', 'attachment; filename="' . $accuseData['filename'] . '"');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('❌ [ACCUSE] Réclamation non trouvée:', ['id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Réclamation non trouvée'
            ], 404);
        } catch (\Exception $e) {
            Log::error('💥 [ACCUSE] Erreur téléchargement accusé:', [
                'reclamation_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération de l\'accusé de réception'
            ], 500);
        }
    }

    /**
     * ✅ MÉTHODE : Supprimer une réclamation
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
                $destinataireAdmin = env('APP_RECLAMATION_EMAIL', 'nguidjoldarryl@gmail.com');
                Mail::to($destinataireAdmin)->send(new ReclamationDeletedMail($numeroReclamation, $typeReclamation, $user, $motifSuppression));
                
                Log::info('📧 Email de suppression de réclamation envoyé:', [
                    'numero' => $numeroReclamation,
                    'user_id' => $user->id,
                    'destinataire' => $destinataireAdmin
                ]);
            } catch (\Exception $e) {
                Log::error('❌ Erreur envoi email suppression réclamation:', [
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
            Log::error('💥 Erreur lors de la suppression de la réclamation:', [
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
                    'peut_telecharger_accuse' => true, // ✅ Toujours disponible
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
        $maintenant = now();
        $limite24h = $reclamation->date_soumission->addHours(24);
        
        return $reclamation->statut === 'en_attente' 
               && $maintenant->lessThan($limite24h)
               && empty($reclamation->commentaires_admin);
    }

    /**
     * ✅ MÉTHODE : Changer le statut d'une réclamation (pour admin)
     */
    public function changerStatut(Request $request, $id)
    {
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
                    Mail::to($user->email)->send(new ReclamationStatusChangedMail($reclamation, $user, $dernierHistorique));
                } catch (\Exception $e) {
                    Log::error('❌ Erreur envoi email changement statut:', [
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
            Log::error('💥 Erreur changement statut:', [
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
        try {
            $total = Reclamation::where('user_id', $userId)
                              ->where('user_type', $userType)
                              ->count();
                              
            $en_attente = Reclamation::where('user_id', $userId)
                                   ->where('user_type', $userType)
                                   ->where('statut', 'en_attente')
                                   ->count();
                                   
            $en_cours = Reclamation::where('user_id', $userId)
                                 ->where('user_type', $userType)
                                 ->where('statut', 'en_cours')
                                 ->count();
                                 
            $resolues = Reclamation::where('user_id', $userId)
                                 ->where('user_type', $userType)
                                 ->whereIn('statut', ['resolu', 'ferme'])
                                 ->count();
                                 
            $ce_mois = Reclamation::where('user_id', $userId)
                                ->where('user_type', $userType)
                                ->whereMonth('date_soumission', now()->month)
                                ->whereYear('date_soumission', now()->year)
                                ->count();

            return [
                'total' => $total,
                'en_attente' => $en_attente,
                'en_cours' => $en_cours,
                'resolues' => $resolues,
                'ce_mois' => $ce_mois
            ];
        } catch (\Exception $e) {
            Log::error('❌ Erreur calcul statistiques:', ['error' => $e->getMessage()]);
            return [
                'total' => 0,
                'en_attente' => 0,
                'en_cours' => 0,
                'resolues' => 0,
                'ce_mois' => 0
            ];
        }
    }

    /**
     * Ajouter une activité récente
     */
    private function ajouterActiviteRecente($user, $userType, $type, $reclamation)
    {
        Log::info('📝 Nouvelle activité réclamation:', [
            'user_id' => $user->id,
            'user_type' => $userType,
            'type' => $type,
            'reclamation_id' => $reclamation->id,
            'numero_reclamation' => $reclamation->numero_reclamation
        ]);
    }

    // app/Http/Controllers/ReclamationController.php

public function indexAdmin(Request $request)
{
    $query = Reclamation::with('historique')
                       ->orderBy('date_soumission', 'desc');

    // Filtres
    if ($request->has('statut') && $request->statut !== '') {
        $query->where('statut', $request->statut);
    }

    if ($request->has('priorite') && $request->priorite !== '') {
        $query->where('priorite', $request->priorite);
    }

    if ($request->has('user_type') && $request->user_type !== '') {
        $query->where('user_type', $request->user_type);
    }

    $reclamations = $query->paginate(20);

    return response()->json([
        'success' => true,
        'reclamations' => $reclamations->map(fn($r) => [
            'id' => $r->id,
            'numero_reclamation' => $r->numero_reclamation,
            'user_nom_complet' => $r->user_prenoms . ' ' . $r->user_nom,
            'user_type' => $r->user_type,
            'user_email' => $r->user_email,
            'type_reclamation' => $r->type_reclamation_info['nom'],
            'description' => $r->description,
            'statut' => $r->statut_libelle,
            'priorite' => $r->priorite_info['nom'],
            'date_soumission' => $r->date_soumission->format('d/m/Y H:i'),
            'documents_count' => count($r->documents ?? []),
            'en_cours' => $r->en_cours
        ]),
        'pagination' => [
            'current_page' => $reclamations->currentPage(),
            'last_page' => $reclamations->lastPage(),
            'total' => $reclamations->total()
        ]
    ]);
}

public function traiterReclamationAdmin(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'nouveau_statut' => 'required|in:en_attente,en_cours,en_revision,resolu,ferme,rejete',
        'commentaire_admin' => 'required|string|max:1000'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    $reclamation = Reclamation::findOrFail($id);
    $admin = $request->user(); // Admin authentifié

    // Changer le statut avec historique
    $reclamation->changerStatut(
        $request->nouveau_statut,
        $request->commentaire_admin,
        "{$admin->prenoms} {$admin->nom} (Admin)"
    );

    // Mettre à jour les commentaires admin
    $reclamation->commentaires_admin = $request->commentaire_admin;
    $reclamation->save();

    // Envoyer email à l'utilisateur
    try {
        $user = $reclamation->user_type === 'agent'
            ? Agent::find($reclamation->user_id)
            : Retraite::find($reclamation->user_id);

        if ($user) {
            $dernierHistorique = $reclamation->historique()->latest()->first();
            Mail::to($user->email)->send(new ReclamationStatusChangedMail($reclamation, $user, $dernierHistorique));
        }
    } catch (\Exception $e) {
        Log::error('Erreur envoi email traitement réclamation:', [
            'reclamation_id' => $reclamation->id,
            'error' => $e->getMessage()
        ]);
    }

    return response()->json([
        'success' => true,
        'message' => 'Réclamation traitée avec succès'
    ]);
}
}

