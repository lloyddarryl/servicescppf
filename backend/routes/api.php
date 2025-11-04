<?php
// File: backend/routes/api.php - Version corrigée pour les historique paiements

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PensionSimulatorController;
use App\Http\Controllers\FamilleController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ReclamationController;
use App\Http\Controllers\RendezVousController;
use App\Http\Controllers\HistoriquePaiementController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Admin\DocumentAdminController;
use App\Http\Controllers\Admin\RapportController;
use App\Http\Controllers\Admin\AdminMessageController;
use App\Models\DocumentRetraite; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
// ⚠️ ROUTE TEMPORAIRE pour éviter l'erreur "Route [login] not defined"
Route::get('/login', function() {
    return response()->json([
        'success' => false,
        'message' => 'Veuillez vous authentifier via /api/admin/login',
        'error' => 'Unauthenticated'
    ], 401);
})->name('login');

Route::get('/debug-test', function () {
    Log::info('Route de test atteinte');
    return response()->json([
        'status' => 'Laravel fonctionne',
        'time' => now(),
        'database' => DB::connection()->getPdo() ? 'OK' : 'KO'
    ]);
});

// Route de nettoyage (publique)
Route::post('/auth/cleanup-setup', [AuthController::class, 'cleanupSetup']);

// Routes publiques (sans authentification)
Route::prefix('auth')->group(function () {
    // Première connexion
    Route::post('/first-login/actifs', [AuthController::class, 'firstLoginActifs']);
    Route::post('/first-login/retraites', [AuthController::class, 'firstLoginRetraites']);

    // Connexion standard
    Route::post('/standard-login', [AuthController::class, 'standardLogin']);

    // Configuration du profil après première connexion
    Route::post('/setup-profile', [AuthController::class, 'setupProfile']);

    // Routes de vérification pour le setup (avec token bearer)
    Route::post('/verify-phone-setup', [AuthController::class, 'verifyPhoneSetup']);
    Route::post('/resend-verification-setup', [AuthController::class, 'resendVerificationSetup']);
});

// Routes protégées par authentification Sanctum
Route::middleware('auth:sanctum')->group(function () {


    Route::post('/logout', [App\Http\Controllers\Admin\AdminAuthController::class, 'logout']);
    Route::get('/me', [App\Http\Controllers\Admin\AdminAuthController::class, 'me']);
    Route::post('/changer-mot-de-passe', [App\Http\Controllers\Admin\AdminAuthController::class, 'changerMotDePasse']);

    Route::get('/dashboard', [App\Http\Controllers\Admin\AdminDashboardController::class, 'index']);


    // Route de test famille
    Route::get('/test-famille', function (Request $request) {
        try {
            $user = $request->user();

            return response()->json([
                'success' => true,
                'debug' => [
                    'user_class' => get_class($user),
                    'user_id' => $user->id,
                    'user_table' => $user->getTable(),
                    'is_agent' => $user instanceof \App\Models\Agent,
                    'is_retraite' => $user instanceof \App\Models\Retraite,
                    'user_attributes' => $user->getAttributes()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    });

    // Routes d'authentification (communes)
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'getCurrentUser']);
        Route::get('/verify', [AuthController::class, 'verifyToken']);
    });

    // Routes de profil utilisateur (communes)
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::put('/password', [ProfileController::class, 'changePassword']);
        Route::post('/verify-phone', [ProfileController::class, 'verifyPhone']);
        Route::post('/resend-verification', [ProfileController::class, 'resendVerification']);
    });

    // Routes communes pour documents (utilisées par les deux types d'utilisateurs)
    Route::prefix('documents')->group(function () {
        Route::get('/types', function () {
            return response()->json([
                'success' => true,
                'types' => DocumentRetraite::$typesDocuments,
                'extensions_autorisees' => DocumentRetraite::$extensionsAutorisees,
                'taille_max_mb' => DocumentRetraite::$tailleMaximale / (1024 * 1024),
                'max_fichiers_simultanes' => 3
            ]);
        });
        
        Route::get('/statistiques/{userId}', function ($userId) {
            return response()->json([
                'success' => true,
                'statistiques' => DocumentRetraite::getStatistiques($userId)
            ]);
        });
    });

    // Routes spécifiques aux agents actifs avec préfixe /actifs
    Route::prefix('actifs')->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'agentDashboard']);

        // Attestations
        Route::get('/attestations', [DashboardController::class, 'getAttestations']);
        Route::post('/attestations', [DashboardController::class, 'requestAttestation']);

        // Prestations
        Route::get('/prestations', [DashboardController::class, 'getPrestations']);

        // Cotisations et gestion de carrière
        Route::get('/cotisations', [DashboardController::class, 'getCotisations']);
        Route::get('/carriere', [DashboardController::class, 'getCarriere']);

        // Routes de profil
        Route::get('/profil', [ProfileController::class, 'show']);
        Route::put('/profil', [ProfileController::class, 'update']);
        Route::put('/profil/password', [ProfileController::class, 'changePassword']);
        Route::post('/profil/verify-phone', [ProfileController::class, 'verifyPhone']);
        Route::post('/profil/resend-verification', [ProfileController::class, 'resendVerification']);

        // Documents et certificats
        Route::get('/documents', [DashboardController::class, 'getDocuments']);
        Route::post('/documents', [DashboardController::class, 'uploadDocument']);

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::put('/notifications/{id}/lue', [NotificationController::class, 'marquerLue']);
        Route::put('/notifications/toutes-lues', [NotificationController::class, 'marquerToutesLues']);
        Route::delete('/notifications/{id}', [NotificationController::class, 'supprimer']);
       
        // FAMILLE - Routes pour les agents actifs
        Route::prefix('famille')->group(function () {
            Route::get('/', [FamilleController::class, 'getGrappeFamiliale']);
            Route::post('/conjoint', [FamilleController::class, 'saveConjoint']);
            Route::post('/enfants', [FamilleController::class, 'addEnfant']);
            Route::put('/enfants/{id}', [FamilleController::class, 'updateEnfant']);
            Route::delete('/enfants/{id}', [FamilleController::class, 'deleteEnfant']);
        });

        // SIMULATEUR DE PENSION - Routes pour agents actifs uniquement
        Route::prefix('simulateur-pension')->group(function () {
            Route::get('/profil', [PensionSimulatorController::class, 'getProfile']);
            Route::post('/simuler', [PensionSimulatorController::class, 'simulatePension']);
            Route::get('/historique', [PensionSimulatorController::class, 'getSimulationHistory']);
            Route::get('/parametres', [PensionSimulatorController::class, 'getParameters']);
        });

        // Routes cotisations
        Route::prefix('cotisations')->group(function () {
            Route::get('/', [App\Http\Controllers\CotisationController::class, 'index']);
            Route::get('/releve-pdf', [App\Http\Controllers\CotisationController::class, 'genererRelevePDF']);
            Route::get('/search', [App\Http\Controllers\CotisationController::class, 'search']);
            Route::get('/statistiques', [App\Http\Controllers\CotisationController::class, 'statistiques']);
            Route::get('/export-excel', [App\Http\Controllers\CotisationController::class, 'exportExcel']);
            Route::get('/{id}', [App\Http\Controllers\CotisationController::class, 'show']);
        });

        // RÉCLAMATIONS - Routes pour les agents actifs
        Route::prefix('reclamations')->group(function () {
            Route::get('/types', [ReclamationController::class, 'getTypesReclamations']);
            Route::get('/', [ReclamationController::class, 'index']);
            Route::post('/', [ReclamationController::class, 'store']);
            Route::get('/{id}', [ReclamationController::class, 'show']);
            Route::delete('/{id}', [ReclamationController::class, 'destroy']);
            Route::get('/{id}/documents/{index}', [ReclamationController::class, 'telechargerDocument']);
            Route::get('/{id}/accuse-reception', [ReclamationController::class, 'telechargerAccuseReception']);
            Route::get('/{id}/documents/{documentIndex}', [ReclamationController::class, 'downloadDocument']);
        });

        // RENDEZ-VOUS - Routes pour les agents actifs
        Route::prefix('rendez-vous')->group(function () {
            Route::get('/', [RendezVousController::class, 'index']);
            Route::post('/', [RendezVousController::class, 'store']);
            Route::get('/historique', [RendezVousController::class, 'historique']);
            Route::get('/creneaux-disponibles/{date}', [RendezVousController::class, 'getCreneauxDisponibles']);
            Route::get('/{id}', [RendezVousController::class, 'show']);
            Route::put('/{id}/annuler', [RendezVousController::class, 'annuler']);
        });

         // ✅ MESSAGERIE AGENTS ACTIFS
    Route::prefix('messages')->group(function () {
        Route::get('/conversations', [MessageController::class, 'index']);
        Route::post('/conversations', [MessageController::class, 'store']);
        Route::get('/conversations/{id}', [MessageController::class, 'show']);
        Route::post('/conversations/{id}/messages', [MessageController::class, 'sendMessage']);
        Route::get('/templates', [MessageController::class, 'templates']);
        Route::get('/unread-count', [MessageController::class, 'unreadCount']);
        Route::put('/messages/{id}', [MessageController::class, 'updateMessage']);
        Route::delete('/messages/{id}', [MessageController::class, 'deleteMessage']);
    });


    });

    // Routes spécifiques aux retraités avec préfixe /retraites
    Route::prefix('retraites')->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'retraiteDashboard']);

        // Pension
        Route::get('/pension', [DashboardController::class, 'getPensionInfo']);
        Route::get('/pension/historique', [DashboardController::class, 'getPensionHistorique']);

        // Certificats de vie
        Route::get('/certificats-vie', [DashboardController::class, 'getCertificatsVie']);
        Route::post('/certificats-vie', [DashboardController::class, 'submitCertificatVie']);
        Route::get('/certificats-vie/{id}/status', [DashboardController::class, 'getCertificatStatus']);

        // Attestations spécifiques retraités
        Route::get('/attestations', [DashboardController::class, 'getAttestationsRetraite']);
        Route::post('/attestations', [DashboardController::class, 'requestAttestationRetraite']);

        // Historique et suivi
        Route::get('/historique', [DashboardController::class, 'getHistorique']);
        Route::get('/suivi-paiements', [DashboardController::class, 'getSuiviPaiements']);

        // Routes de profil
        Route::get('/profil', [ProfileController::class, 'show']);
        Route::put('/profil', [ProfileController::class, 'update']);
        Route::put('/profil/password', [ProfileController::class, 'changePassword']);
        Route::post('/profil/verify-phone', [ProfileController::class, 'verifyPhone']);
        Route::post('/profil/resend-verification', [ProfileController::class, 'resendVerification']);

        // Documents
        Route::get('/documents', [DashboardController::class, 'getDocumentsRetraite']);
        Route::post('/documents', [DashboardController::class, 'uploadDocumentRetraite']);

        // Notifications
        Route::get('/notifications', [DashboardController::class, 'getNotificationsRetraite']);
        Route::put('/notifications/{id}/read', [DashboardController::class, 'markNotificationReadRetraite']);

        // FAMILLE - Routes pour les retraités
        Route::prefix('famille')->group(function () {
            Route::get('/', [FamilleController::class, 'getGrappeFamiliale']);
            Route::post('/conjoint', [FamilleController::class, 'saveConjoint']);
            Route::post('/enfants', [FamilleController::class, 'addEnfant']);
            Route::put('/enfants/{id}', [FamilleController::class, 'updateEnfant']);
            Route::delete('/enfants/{id}', [FamilleController::class, 'deleteEnfant']);
        });

        // RÉCLAMATIONS - Routes pour les retraités
        Route::prefix('reclamations')->group(function () {
            Route::get('/types', [ReclamationController::class, 'getTypesReclamations']);
            Route::get('/', [ReclamationController::class, 'index']);
            Route::post('/', [ReclamationController::class, 'store']);
            Route::get('/{id}/documents/{index}', [ReclamationController::class, 'telechargerDocument']);
            Route::get('/{id}/accuse-reception', [ReclamationController::class, 'telechargerAccuseReception']);
            Route::get('/{id}/documents/{documentIndex}', [ReclamationController::class, 'downloadDocument']);
            // Route générique EN DERNIER
            Route::get('/{id}', [ReclamationController::class, 'show']);
            Route::delete('/{id}', [ReclamationController::class, 'destroy']);
        });

        // Documents et gestion documentaire 
        Route::prefix('documents')->group(function () {
            Route::get('/notifications', [App\Http\Controllers\DocumentController::class, 'getNotifications']);
            Route::post('/notifications/dismiss', [App\Http\Controllers\DocumentController::class, 'dismissNotification']);
            Route::get('/', [App\Http\Controllers\DocumentController::class, 'index']); 
            Route::post('/', [App\Http\Controllers\DocumentController::class, 'store']);
            Route::get('/documents/{id}/view', [DocumentController::class, 'view']);
            Route::get('/{id}/download', [App\Http\Controllers\DocumentController::class, 'download']);
            Route::delete('/{id}', [App\Http\Controllers\DocumentController::class, 'destroy']);

        });

        // Routes pour l'historique des paiements - VERSION CORRIGÉE
        Route::prefix('historique-paiements')->group(function () {
            Route::get('/', [HistoriquePaiementController::class, 'index']);
            Route::get('/statistiques', [HistoriquePaiementController::class, 'statistiques']);
            Route::get('/rechercher', [HistoriquePaiementController::class, 'rechercher']);
            Route::get('/telecharger-pdf', [HistoriquePaiementController::class, 'telechargerPDF']);
            Route::get('/toutes-annees', [HistoriquePaiementController::class, 'obtenirToutesLesAnnees']);
            Route::get('/{id}', [HistoriquePaiementController::class, 'show']);
        });

        // Alternative pour compatibilité avec dashboard existant
        Route::get('/pension/historique', [HistoriquePaiementController::class, 'index']);
        Route::get('/suivi-paiements', [HistoriquePaiementController::class, 'index']);

        // RENDEZ-VOUS - Routes pour les retraités
        Route::prefix('rendez-vous')->group(function () {
            Route::get('/', [RendezVousController::class, 'index']);
            Route::post('/', [RendezVousController::class, 'store']);
            Route::get('/historique', [RendezVousController::class, 'historique']);
            Route::get('/creneaux-disponibles/{date}', [RendezVousController::class, 'getCreneauxDisponibles']);
            Route::get('/{id}', [RendezVousController::class, 'show']);
            Route::put('/{id}/annuler', [RendezVousController::class, 'annuler']);
        });

        // ✅ MESSAGERIE RETRAITÉS
    Route::prefix('messages')->group(function () {
        Route::get('/conversations', [MessageController::class, 'index']);
        Route::post('/conversations', [MessageController::class, 'store']);
        Route::get('/conversations/{id}', [MessageController::class, 'show']);
        Route::post('/conversations/{id}/messages', [MessageController::class, 'sendMessage']);
        Route::get('/templates', [MessageController::class, 'templates']);
        Route::get('/unread-count', [MessageController::class, 'unreadCount']);
        Route::put('/messages/{id}', [MessageController::class, 'updateMessage']);
        Route::delete('/messages/{id}', [MessageController::class, 'deleteMessage']);
         });

    });

    // Route générale pour le dashboard (redirige selon le type d'utilisateur)
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Routes de diagnostic (temporaires)
    Route::get('/test/pension/diagnostic', [App\Http\Controllers\PensionTestController::class, 'diagnostic']);
    Route::post('/test/pension/init', [App\Http\Controllers\PensionTestController::class, 'initTestData']);
    Route::delete('/test/pension/cleanup', [App\Http\Controllers\PensionTestController::class, 'cleanup']);

    // Route de test générale
    Route::get('/test-profile', function (Illuminate\Http\Request $request) {
        try {
            $user = $request->user();

            return response()->json([
                'success' => true,
                'user_type' => get_class($user),
                'user_id' => $user->id ?? null,
                'middleware_test' => 'OK - Route accessible'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    });

});


Route::prefix('admin')->group(function () {
    // ✅ Login admin (SANS middleware - route publique admin)
    Route::post('/login', [App\Http\Controllers\Admin\AdminAuthController::class, 'login']);
    
    // ✅ Routes protégées admin avec middleware auth:sanctum + vérification admin
    Route::middleware(['auth:admin'])->group(function () {
        // Auth admin
        Route::post('/logout', [App\Http\Controllers\Admin\AdminAuthController::class, 'logout']);
        Route::get('/me', [App\Http\Controllers\Admin\AdminAuthController::class, 'me']);
        Route::post('/changer-mot-de-passe', [App\Http\Controllers\Admin\AdminAuthController::class, 'changerMotDePasse']);
        Route::get('/verifier-token', [App\Http\Controllers\Admin\AdminAuthController::class, 'verifierToken']);

        // ✅ Dashboard admin (PRÉFIXÉ /admin/)
        Route::get('/dashboard', [App\Http\Controllers\Admin\AdminDashboardController::class, 'index']);
        Route::get('/statistiques', [App\Http\Controllers\Admin\AdminDashboardController::class, 'statistiquesGlobales']);

    

        // Gestion des rendez-vous admin
    Route::prefix('rendez-vous')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\AdminRendezVousController::class, 'index']);
        Route::get('/statistiques', [App\Http\Controllers\Admin\AdminRendezVousController::class, 'statistiques']);
        Route::get('/{id}', [App\Http\Controllers\Admin\AdminRendezVousController::class, 'show']);
        Route::put('/{id}/statut', [App\Http\Controllers\Admin\AdminRendezVousController::class, 'changerStatut']);
        Route::post('/traitement-lot', [App\Http\Controllers\Admin\AdminRendezVousController::class, 'traitementLot']);
    });

    // Documents admin
Route::prefix('documents')->group(function () {
    Route::get('/dashboard', [DocumentAdminController::class, 'dashboard']);
    Route::get('/motifs-rejet', [DocumentAdminController::class, 'getMotifsRejet']);
    Route::get('/', [DocumentAdminController::class, 'index']);
    
    // Routes spécifiques (avec sous-chemins) AVANT la route générique
    Route::get('/{id}/view', [DocumentAdminController::class, 'view']);
    Route::get('/{id}/download', [DocumentAdminController::class, 'download']);
    
    Route::post('/{id}/valider', [DocumentAdminController::class, 'valider']);
    Route::post('/{id}/rejeter', [DocumentAdminController::class, 'rejeter']);
    Route::delete('/{id}', [DocumentAdminController::class, 'destroy']);
    
    // Route générique EN DERNIER pour éviter les conflits
    Route::get('/{id}', [DocumentAdminController::class, 'show']);
});

// ✅ AJOUT: Route rappel certificat (APRÈS la fermeture du prefix documents)
Route::post('/retraites/{id}/rappel-certificat', [DocumentAdminController::class, 'envoyerRappel']);

    // Gestion des messages et conversations admin
         Route::prefix('messages')->group(function () {
        Route::get('/conversations', [AdminMessageController::class, 'index']);
        Route::post('/conversations', [AdminMessageController::class, 'store']);
        Route::get('/conversations/{id}', [AdminMessageController::class, 'show']);
        Route::put('/conversations/{id}', [AdminMessageController::class, 'update']);
        Route::post('/conversations/{id}/messages', [AdminMessageController::class, 'sendMessage']);
        Route::get('/templates', [AdminMessageController::class, 'templates']);
        Route::get('/search-users', [AdminMessageController::class, 'searchUsers']);
        Route::put('/messages/{id}', [MessageController::class, 'updateMessage']);
        Route::delete('/messages/{id}', [MessageController::class, 'deleteMessage']);
    });

        // Utilisateurs admin
        Route::prefix('utilisateurs')->group(function () {
            Route::get('/agents', [App\Http\Controllers\Admin\AdminUtilisateurController::class, 'indexAgents']);
            Route::get('/retraites', [App\Http\Controllers\Admin\AdminUtilisateurController::class, 'indexRetraites']);
            Route::get('/agent/{id}', [App\Http\Controllers\Admin\AdminUtilisateurController::class, 'showAgent']);
            Route::get('/retraite/{id}', [App\Http\Controllers\Admin\AdminUtilisateurController::class, 'showRetraite']);
            Route::put('/agent/{id}/statut', [App\Http\Controllers\Admin\AdminUtilisateurController::class, 'changerStatutAgent']);
            Route::put('/retraite/{id}/statut', [App\Http\Controllers\Admin\AdminUtilisateurController::class, 'changerStatutRetraite']);
            Route::post('/{type}/{id}/reinitialiser-mot-de-passe', [App\Http\Controllers\Admin\AdminUtilisateurController::class, 'reinitialiserMotDePasse']);
            Route::get('/statistiques', [App\Http\Controllers\Admin\AdminUtilisateurController::class, 'statistiquesUtilisateurs']);
        });


    // Rapports et exports
    Route::prefix('rapports')->group(function () {
        Route::get('/mensuel', [RapportController::class, 'rapportMensuel']);
        Route::get('/activites', [RapportController::class, 'rapportActivites']);
        Route::get('/export/excel', [RapportController::class, 'exportExcel']);
        Route::get('/export/pdf', [RapportController::class, 'exportPDF']);
    });

    // Logs et audit
    Route::prefix('logs')->group(function () {
        Route::get('/actions', [AuditController::class, 'actionsRecentes']);
        Route::get('/connexions', [AuditController::class, 'historiqueConnexions']);
    });

// Gestion des réclamations admin
    Route::prefix('reclamations')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\AdminReclamationController::class, 'index']);
        Route::get('/statistiques', [App\Http\Controllers\Admin\AdminReclamationController::class, 'statistiques']);
        Route::get('/{id}', [App\Http\Controllers\Admin\AdminReclamationController::class, 'show']);
        Route::put('/{id}/traiter', [App\Http\Controllers\Admin\AdminReclamationController::class, 'traiter']);
        Route::get('/{id}/document/{index}', [App\Http\Controllers\Admin\AdminReclamationController::class, 'telechargerDocument']);
        Route::delete('/{id}', [App\Http\Controllers\Admin\AdminReclamationController::class, 'supprimer']);
        Route::get('/reclamations/{id}/historique', [AdminReclamationController::class, 'historique']);

        });
    });
});
// Route de fallback pour API
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Route non trouvée',
        'error' => 'La route demandée n\'existe pas'
    ], 404);
});

/**
 * AJOUTER temporairement cette route dans api.php pour diagnostic
 */
Route::get('/admin/documents/{id}/diagnostic', function($id) {
    try {
        $document = \App\Models\DocumentRetraite::findOrFail($id);
        
        $chemin_complet = storage_path('app/' . $document->chemin_fichier);
        
        return response()->json([
            'success' => true,
            'document' => [
                'id' => $document->id,
                'nom_original' => $document->nom_original,
                'chemin_fichier' => $document->chemin_fichier,
                'chemin_complet' => $chemin_complet,
            ],
            'verifications' => [
                'storage_exists' => \Storage::exists($document->chemin_fichier),
                'file_exists' => file_exists($chemin_complet),
                'is_readable' => is_readable($chemin_complet),
                'file_size' => file_exists($chemin_complet) ? filesize($chemin_complet) : null,
                'permissions' => file_exists($chemin_complet) ? substr(sprintf('%o', fileperms($chemin_complet)), -4) : null,
            ],
            'storage_info' => [
                'storage_path' => storage_path('app'),
                'documents_path' => storage_path('app/documents'),
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
})->middleware('auth:admin');

// Route de test simple (sans middleware)
Route::get('/test-simple', function () {
    return response()->json(['message' => 'API fonctionne', 'time' => now()]);
});

// Ajoutez cette route dans api.php pour tester
Route::get('/test-sms/{phone}', function($phone) {
    try {
        $smsService = new \App\Services\SmsServices();
        $result = $smsService->testSmsApi($phone);
        
        return response()->json([
            'success' => true,
            'sms_result' => $result,
            'phone_used' => $phone,
            'config_check' => $smsService->checkConfiguration()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});

// Ajouter cette route temporaire dans api.php pour tester le stockage
Route::get('/test-storage', function () {
    try {
        $testDir = 'documents/retraites/test';
        $testFile = 'test.txt';
        $testContent = 'Test de stockage - ' . now();
        
        // Créer le dossier
        if (!Storage::exists($testDir)) {
            Storage::makeDirectory($testDir);
        }
        
        // Créer un fichier test
        $path = Storage::put($testDir . '/' . $testFile, $testContent);
        
        $results = [
            'storage_path' => storage_path('app'),
            'test_directory' => $testDir,
            'directory_exists' => Storage::exists($testDir),
            'file_created' => $path,
            'file_exists' => Storage::exists($testDir . '/' . $testFile),
            'absolute_path' => storage_path('app/' . $testDir . '/' . $testFile),
            'file_exists_absolute' => file_exists(storage_path('app/' . $testDir . '/' . $testFile)),
            'content_check' => Storage::get($testDir . '/' . $testFile),
            'permissions_check' => [
                'directory_writable' => is_writable(storage_path('app/' . $testDir)),
                'directory_readable' => is_readable(storage_path('app/' . $testDir))
            ]
        ];
        
        // Nettoyer
        Storage::delete($testDir . '/' . $testFile);
        Storage::deleteDirectory($testDir);
        
        return response()->json([
            'success' => true,
            'message' => 'Test de stockage réussi',
            'results' => $results
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Ajouter cette route temporaire dans api.php
Route::get('/diagnostic-permissions', function () {
    try {
        $storagePath = storage_path('app');
        $documentsPath = storage_path('app/documents');
        $retraitesPath = storage_path('app/documents/retraites');
        
        $results = [
            'storage_info' => [
                'storage_path' => $storagePath,
                'exists' => file_exists($storagePath),
                'writable' => is_writable($storagePath),
                'permissions' => file_exists($storagePath) ? substr(sprintf('%o', fileperms($storagePath)), -4) : null
            ],
            'documents_info' => [
                'documents_path' => $documentsPath,
                'exists' => file_exists($documentsPath),
                'writable' => is_writable($documentsPath),
                'permissions' => file_exists($documentsPath) ? substr(sprintf('%o', fileperms($documentsPath)), -4) : null
            ],
            'retraites_info' => [
                'retraites_path' => $retraitesPath,
                'exists' => file_exists($retraitesPath),
                'writable' => is_writable($retraitesPath),
                'permissions' => file_exists($retraitesPath) ? substr(sprintf('%o', fileperms($retraitesPath)), -4) : null
            ],
            'test_creation' => []
        ];
        
        // Test de création de dossier
        $testPath = storage_path('app/test_permissions');
        try {
            if (!file_exists($testPath)) {
                $created = mkdir($testPath, 0755, true);
                $results['test_creation']['mkdir_success'] = $created;
                
                if ($created) {
                    // Test de création de fichier
                    $testFile = $testPath . '/test.txt';
                    $fileCreated = file_put_contents($testFile, 'test content');
                    $results['test_creation']['file_creation'] = $fileCreated !== false;
                    
                    // Nettoyer
                    if (file_exists($testFile)) {
                        unlink($testFile);
                    }
                    rmdir($testPath);
                }
            }
        } catch (\Exception $e) {
            $results['test_creation']['error'] = $e->getMessage();
        }
        
        // Informations système
        $results['system_info'] = [
            'php_user' => get_current_user(),
            'disk_free_space' => disk_free_space($storagePath),
            'disk_total_space' => disk_total_space($storagePath),
            'temp_dir' => sys_get_temp_dir(),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size')
        ];
        
        return response()->json([
            'success' => true,
            'results' => $results
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});