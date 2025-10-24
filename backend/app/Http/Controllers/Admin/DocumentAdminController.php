<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentRetraite;
use App\Models\Retraite;
use App\Models\MessageDashboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\DocumentValideNotification;
use App\Mail\DocumentRejeteNotification;
use Carbon\Carbon;

class DocumentAdminController extends Controller
{
    /**
     * Liste des documents avec pagination et filtres
     */
    public function index(Request $request)
    {
        try {
            $query = DocumentRetraite::with(['retraite']);

            // Filtres
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('retraite', function($q) use ($search) {
                    $q->where('nom', 'like', "%{$search}%")
                      ->orWhere('prenom', 'like', "%{$search}%")
                      ->orWhere('matricule', 'like', "%{$search}%");
                });
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }

            if ($request->filled('type_document')) {
                $query->where('type_document', $request->type_document);
            }

            if ($request->filled('date_depot_debut') && $request->filled('date_depot_fin')) {
                $query->whereBetween('date_depot', [$request->date_depot_debut, $request->date_depot_fin]);
            }

            if ($request->filled('validite')) {
                if ($request->validite === 'expire') {
                    $query->where('date_expiration', '<', now());
                } elseif ($request->validite === 'expire_bientot') {
                    $query->whereBetween('date_expiration', [now(), now()->addDays(30)]);
                }
            }

            if ($request->filled('priorite')) {
                if ($request->priorite === 'urgente') {
                    $query->where('statut', 'en_attente')
                          ->where('date_depot', '<', now()->subDays(7));
                }
            }

            // Tri
            $sortField = $request->get('sort_field', 'date_depot');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            $documents = $query->paginate($request->get('per_page', 15));

            // Ajouter des informations supplémentaires
            $documents->getCollection()->transform(function ($document) {
                $document->jours_depuis_depot = now()->diffInDays($document->date_depot);
                $document->expire_bientot = $document->date_expiration && 
                    $document->date_expiration->isBetween(now(), now()->addDays(30));
                $document->urgent = $document->statut === 'en_attente' && 
                    $document->date_depot < now()->subDays(7);
                return $document;
            });

            return response()->json([
                'success' => true,
                'data' => $documents,
                'filtres_disponibles' => [
                    'statuts' => DocumentRetraite::distinct('statut')->pluck('statut'),
                    'types_documents' => DocumentRetraite::distinct('type_document')->pluck('type_document')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des documents: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des documents'
            ], 500);
        }
    }

    /**
     * Détails d'un document spécifique
     */
    public function show($id)
    {
        try {
            $document = DocumentRetraite::with(['retraite', 'admin'])->findOrFail($id);

            // Informations supplémentaires
            $document->jours_depuis_depot = now()->diffInDays($document->date_depot);
            $document->expire_bientot = $document->date_expiration && 
                $document->date_expiration->isBetween(now(), now()->addDays(30));
            $document->urgent = $document->statut === 'en_attente' && 
                $document->date_depot < now()->subDays(7);

            // Vérifier si le fichier existe
            $document->fichier_existe = Storage::exists($document->chemin_fichier);
            $document->taille_fichier = $document->fichier_existe ? 
                Storage::size($document->chemin_fichier) : 0;

            return response()->json([
                'success' => true,
                'data' => $document
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du document: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Document non trouvé'
            ], 404);
        }
    }

    /**
     * Télécharger un document
     */
    public function download($id)
    {
        try {
            $document = DocumentRetraite::findOrFail($id);
            
            if (!Storage::exists($document->chemin_fichier)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fichier non trouvé'
                ], 404);
            }

            // Log de l'activité
            auth('admin')->user()->enregistrerActivite(
                'telechargement_document',
                "Téléchargement du document {$document->type_document} de {$document->retraite->nom_complet}"
            );

            return Storage::download($document->chemin_fichier, $document->nom_fichier);

        } catch (\Exception $e) {
            Log::error('Erreur lors du téléchargement: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement'
            ], 500);
        }
    }

    /**
     * Valider un document
     */
    public function validerDocument(Request $request, $id)
    {
        $request->validate([
            'commentaires' => 'nullable|string|max:1000',
            'date_expiration' => 'nullable|date|after:today'
        ]);

        try {
            $document = DocumentRetraite::with('retraite')->findOrFail($id);
            
            if ($document->statut !== 'en_attente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce document a déjà été traité'
                ], 400);
            }

            $document->update([
                'statut' => 'valide',
                'date_traitement' => now(),
                'admin_id' => auth('admin')->id(),
                'commentaires_admin' => $request->commentaires,
                'date_expiration' => $request->date_expiration
            ]);

            // Envoyer un email de notification
            try {
                Mail::to($document->retraite->email)
                    ->send(new DocumentValideNotification($document));
            } catch (\Exception $mailError) {
                Log::warning('Erreur envoi email validation document: ' . $mailError->getMessage());
            }

            // Envoyer un message dans le dashboard
            MessageDashboard::create([
                'admin_id' => auth('admin')->id(),
                'user_id' => $document->retraite->id,
                'user_type' => 'retraite',
                'titre' => 'Document validé',
                'message' => "Votre document {$document->type_document} a été validé." . 
                    ($request->commentaires ? " Commentaires: {$request->commentaires}" : ''),
                'type_message' => 'notification',
                'priorite' => 'normale'
            ]);

            // Log de l'activité
            auth('admin')->user()->enregistrerActivite(
                'validation_document',
                "Document {$document->type_document} de {$document->retraite->nom_complet} validé"
            );

            return response()->json([
                'success' => true,
                'message' => 'Document validé avec succès',
                'data' => $document->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la validation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation'
            ], 500);
        }
    }

    /**
     * Rejeter un document
     */
    public function rejeterDocument(Request $request, $id)
    {
        $request->validate([
            'motif_rejet' => 'required|string|max:1000'
        ]);

        try {
            $document = DocumentRetraite::with('retraite')->findOrFail($id);
            
            if ($document->statut !== 'en_attente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce document a déjà été traité'
                ], 400);
            }

            $document->update([
                'statut' => 'rejete',
                'date_traitement' => now(),
                'admin_id' => auth('admin')->id(),
                'motif_rejet' => $request->motif_rejet
            ]);

            // Envoyer un email de notification
            try {
                Mail::to($document->retraite->email)
                    ->send(new DocumentRejeteNotification($document));
            } catch (\Exception $mailError) {
                Log::warning('Erreur envoi email rejet document: ' . $mailError->getMessage());
            }

            // Envoyer un message dans le dashboard
            MessageDashboard::create([
                'admin_id' => auth('admin')->id(),
                'user_id' => $document->retraite->id,
                'user_type' => 'retraite',
                'titre' => 'Document rejeté',
                'message' => "Votre document {$document->type_document} a été rejeté. Motif: {$request->motif_rejet}",
                'type_message' => 'alerte',
                'priorite' => 'haute'
            ]);

            // Log de l'activité
            auth('admin')->user()->enregistrerActivite(
                'rejet_document',
                "Document {$document->type_document} de {$document->retraite->nom_complet} rejeté"
            );

            return response()->json([
                'success' => true,
                'message' => 'Document rejeté avec succès',
                'data' => $document->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du rejet: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet'
            ], 500);
        }
    }

    /**
     * Supprimer un document
     */
    public function supprimerDocument(Request $request, $id)
    {
        $request->validate([
            'motif_suppression' => 'required|string|max:500'
        ]);

        try {
            $document = DocumentRetraite::with('retraite')->findOrFail($id);

            // Supprimer le fichier physique
            if (Storage::exists($document->chemin_fichier)) {
                Storage::delete($document->chemin_fichier);
            }

            // Envoyer un message au retraité
            MessageDashboard::create([
                'admin_id' => auth('admin')->id(),
                'user_id' => $document->retraite->id,
                'user_type' => 'retraite',
                'titre' => 'Document supprimé',
                'message' => "Votre document {$document->type_document} a été supprimé. Motif: {$request->motif_suppression}",
                'type_message' => 'alerte',
                'priorite' => 'haute'
            ]);

            // Log de l'activité avant suppression
            auth('admin')->user()->enregistrerActivite(
                'suppression_document',
                "Document {$document->type_document} de {$document->retraite->nom_complet} supprimé. Motif: {$request->motif_suppression}"
            );

            // Supprimer le document de la base de données
            $document->delete();

            return response()->json([
                'success' => true,
                'message' => 'Document supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }

    /**
     * Statistiques des documents
     */
    public function statistiques()
    {
        try {
            $today = now();
            $startOfMonth = $today->copy()->startOfMonth();
            $startOfWeek = $today->copy()->startOfWeek();

            $statistiques = [
                'globales' => [
                    'total_documents' => DocumentRetraite::count(),
                    'en_attente' => DocumentRetraite::where('statut', 'en_attente')->count(),
                    'valides' => DocumentRetraite::where('statut', 'valide')->count(),
                    'rejetes' => DocumentRetraite::where('statut', 'rejete')->count(),
                    'expires' => DocumentRetraite::where('date_expiration', '<', $today)->count(),
                    'expire_bientot' => DocumentRetraite::whereBetween('date_expiration', [$today, $today->copy()->addDays(30)])->count()
                ],
                'periode' => [
                    'nouveaux_cette_semaine' => DocumentRetraite::where('date_depot', '>=', $startOfWeek)->count(),
                    'nouveaux_ce_mois' => DocumentRetraite::where('date_depot', '>=', $startOfMonth)->count(),
                    'traites_cette_semaine' => DocumentRetraite::whereNotNull('date_traitement')
                        ->where('date_traitement', '>=', $startOfWeek)->count(),
                    'traites_ce_mois' => DocumentRetraite::whereNotNull('date_traitement')
                        ->where('date_traitement', '>=', $startOfMonth)->count()
                ],
                'par_type' => DocumentRetraite::selectRaw('type_document, statut, count(*) as total')
                    ->groupBy('type_document', 'statut')
                    ->get()
                    ->groupBy('type_document')
                    ->map(function ($documents, $type) {
                        return [
                            'type' => $type,
                            'total' => $documents->sum('total'),
                            'en_attente' => $documents->where('statut', 'en_attente')->sum('total'),
                            'valides' => $documents->where('statut', 'valide')->sum('total'),
                            'rejetes' => $documents->where('statut', 'rejete')->sum('total')
                        ];
                    })->values(),
                'urgents' => [
                    'documents_urgents' => DocumentRetraite::where('statut', 'en_attente')
                        ->where('date_depot', '<', $today->copy()->subDays(7))
                        ->count(),
                    'certificats_expires' => DocumentRetraite::where('type_document', 'certificat_vie')
                        ->where('date_expiration', '<', $today)
                        ->where('statut', 'valide')
                        ->count()
                ],
                'performance' => [
                    'temps_moyen_traitement' => DocumentRetraite::whereNotNull('date_traitement')
                        ->selectRaw('AVG(DATEDIFF(date_traitement, date_depot)) as moyenne')
                        ->value('moyenne'),
                    'taux_validation' => DocumentRetraite::whereNotNull('date_traitement')->count() > 0 ? 
                        round(DocumentRetraite::where('statut', 'valide')->count() * 100 / 
                              DocumentRetraite::whereNotNull('date_traitement')->count(), 2) : 0
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $statistiques
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des statistiques: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques'
            ], 500);
        }
    }

    /**
     * Traitement en lot des documents
     */
    public function traitementLot(Request $request)
    {
        $request->validate([
            'document_ids' => 'required|array|min:1',
            'document_ids.*' => 'integer|exists:document_retraites,id',
            'action' => 'required|in:valider,rejeter,supprimer',
            'commentaires' => 'nullable|string|max:1000',
            'motif_rejet' => 'required_if:action,rejeter|string|max:1000',
            'motif_suppression' => 'required_if:action,supprimer|string|max:500'
        ]);

        try {
            $documents = DocumentRetraite::with('retraite')
                ->whereIn('id', $request->document_ids)
                ->where('statut', 'en_attente')
                ->get();

            if ($documents->count() !== count($request->document_ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certains documents ne peuvent pas être traités'
                ], 400);
            }

            $resultats = [
                'traites' => 0,
                'erreurs' => 0,
                'details' => []
            ];

            foreach ($documents as $document) {
                try {
                    switch ($request->action) {
                        case 'valider':
                            $document->update([
                                'statut' => 'valide',
                                'date_traitement' => now(),
                                'admin_id' => auth('admin')->id(),
                                'commentaires_admin' => $request->commentaires
                            ]);
                            
                            // Message au retraité
                            MessageDashboard::create([
                                'admin_id' => auth('admin')->id(),
                                'user_id' => $document->retraite->id,
                                'user_type' => 'retraite',
                                'titre' => 'Document validé',
                                'message' => "Votre document {$document->type_document} a été validé (traitement en lot).",
                                'type_message' => 'notification',
                                'priorite' => 'normale'
                            ]);
                            break;

                        case 'rejeter':
                            $document->update([
                                'statut' => 'rejete',
                                'date_traitement' => now(),
                                'admin_id' => auth('admin')->id(),
                                'motif_rejet' => $request->motif_rejet
                            ]);
                            
                            // Message au retraité
                            MessageDashboard::create([
                                'admin_id' => auth('admin')->id(),
                                'user_id' => $document->retraite->id,
                                'user_type' => 'retraite',
                                'titre' => 'Document rejeté',
                                'message' => "Votre document {$document->type_document} a été rejeté. Motif: {$request->motif_rejet}",
                                'type_message' => 'alerte',
                                'priorite' => 'haute'
                            ]);
                            break;

                        case 'supprimer':
                            // Supprimer le fichier physique
                            if (Storage::exists($document->chemin_fichier)) {
                                Storage::delete($document->chemin_fichier);
                            }
                            
                            // Message au retraité
                            MessageDashboard::create([
                                'admin_id' => auth('admin')->id(),
                                'user_id' => $document->retraite->id,
                                'user_type' => 'retraite',
                                'titre' => 'Document supprimé',
                                'message' => "Votre document {$document->type_document} a été supprimé. Motif: {$request->motif_suppression}",
                                'type_message' => 'alerte',
                                'priorite' => 'haute'
                            ]);
                            
                            $document->delete();
                            break;
                    }

                    $resultats['traites']++;
                    $resultats['details'][] = [
                        'document_id' => $document->id,
                        'success' => true,
                        'message' => 'Traité avec succès'
                    ];

                } catch (\Exception $e) {
                    $resultats['erreurs']++;
                    $resultats['details'][] = [
                        'document_id' => $document->id,
                        'success' => false,
                        'message' => 'Erreur: ' . $e->getMessage()
                    ];
                }
            }

            // Log de l'activité
            auth('admin')->user()->enregistrerActivite(
                'traitement_lot_documents',
                "Traitement en lot: {$request->action} sur {$resultats['traites']} documents"
            );

            return response()->json([
                'success' => true,
                'message' => "Traitement terminé: {$resultats['traites']} document(s) traité(s), {$resultats['erreurs']} erreur(s)",
                'data' => $resultats
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement en lot: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement en lot'
            ], 500);
        }
    }

    /**
     * Rapport d'activité des documents
     */
    public function rapportActivite(Request $request)
    {
        $request->validate([
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'format' => 'in:json,excel'
        ]);

        try {
            $query = DocumentRetraite::with(['retraite', 'admin'])
                ->whereBetween('date_depot', [$request->date_debut, $request->date_fin]);

            $documents = $query->get();

            $rapport = [
                'periode' => [
                    'debut' => $request->date_debut,
                    'fin' => $request->date_fin
                ],
                'resume' => [
                    'total_documents' => $documents->count(),
                    'valides' => $documents->where('statut', 'valide')->count(),
                    'rejetes' => $documents->where('statut', 'rejete')->count(),
                    'en_attente' => $documents->where('statut', 'en_attente')->count()
                ],
                'par_type' => $documents->groupBy('type_document')->map(function ($docs, $type) {
                    return [
                        'type' => $type,
                        'total' => $docs->count(),
                        'valides' => $docs->where('statut', 'valide')->count(),
                        'rejetes' => $docs->where('statut', 'rejete')->count(),
                        'en_attente' => $docs->where('statut', 'en_attente')->count()
                    ];
                })->values(),
                'details' => $documents->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'type_document' => $doc->type_document,
                        'retraite' => $doc->retraite->nom_complet,
                        'matricule' => $doc->retraite->matricule,
                        'date_depot' => $doc->date_depot->format('d/m/Y'),
                        'statut' => $doc->statut,
                        'date_traitement' => $doc->date_traitement ? $doc->date_traitement->format('d/m/Y') : null,
                        'admin_traitement' => $doc->admin ? $doc->admin->nom_complet : null,
                        'jours_traitement' => $doc->date_traitement ? 
                            $doc->date_depot->diffInDays($doc->date_traitement) : null
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $rapport
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération du rapport: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport'
            ], 500);
        }
    }
}