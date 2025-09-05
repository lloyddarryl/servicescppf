<?php
// File: backend/routes/api.php - Version corrigée pour les rendez-vous

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PensionSimulatorController;
use App\Http\Controllers\FamilleController;
use App\Http\Controllers\ReclamationController;
use App\Http\Controllers\RendezVousController;
use App\Models\DocumentRetraite; 

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

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

    // ✅ ROUTE DE TEST FAMILLE
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
        Route::get('/notifications', [DashboardController::class, 'getNotifications']);
        Route::put('/notifications/{id}/read', [DashboardController::class, 'markNotificationRead']);

        // ✅ FAMILLE - Routes pour les agents actifs
        Route::prefix('famille')->group(function () {
            Route::get('/', [FamilleController::class, 'getGrappeFamiliale']);
            Route::post('/conjoint', [FamilleController::class, 'saveConjoint']);
            Route::post('/enfants', [FamilleController::class, 'addEnfant']);
            Route::put('/enfants/{id}', [FamilleController::class, 'updateEnfant']);
            Route::delete('/enfants/{id}', [FamilleController::class, 'deleteEnfant']);
        });

        // ✅ SIMULATEUR DE PENSION - Routes pour agents actifs uniquement
        Route::prefix('simulateur-pension')->group(function () {
            Route::get('/profil', [PensionSimulatorController::class, 'getProfile']);
            Route::post('/simuler', [PensionSimulatorController::class, 'simulatePension']);
            Route::get('/historique', [PensionSimulatorController::class, 'getSimulationHistory']);
            Route::get('/parametres', [PensionSimulatorController::class, 'getParameters']);
        });

        // ✅ RÉCLAMATIONS - Routes pour les agents actifs
        Route::prefix('reclamations')->group(function () {
            Route::get('/types', [ReclamationController::class, 'getTypesReclamations']);
            Route::get('/', [ReclamationController::class, 'index']);
            Route::post('/', [ReclamationController::class, 'store']);
            Route::get('/{id}', [ReclamationController::class, 'show']);
            Route::delete('/{id}', [ReclamationController::class, 'destroy']);
            Route::get('/{id}/accuse-reception', [ReclamationController::class, 'telechargerAccuseReception']);
            Route::get('/{id}/documents/{documentIndex}', [ReclamationController::class, 'downloadDocument']);
        });

        // ✅ RENDEZ-VOUS - Routes corrigées pour les agents actifs
        Route::prefix('rendez-vous')->group(function () {
            Route::get('/', [RendezVousController::class, 'index']);
            Route::post('/', [RendezVousController::class, 'store']);
            Route::get('/historique', [RendezVousController::class, 'historique']);
            Route::get('/creneaux-disponibles/{date}', [RendezVousController::class, 'getCreneauxDisponibles']);
            Route::get('/{id}', [RendezVousController::class, 'show']);
            Route::put('/{id}/annuler', [RendezVousController::class, 'annuler']);
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

        // ✅ FAMILLE - Routes pour les retraités
        Route::prefix('famille')->group(function () {
            Route::get('/', [FamilleController::class, 'getGrappeFamiliale']);
            Route::post('/conjoint', [FamilleController::class, 'saveConjoint']);
            Route::post('/enfants', [FamilleController::class, 'addEnfant']);
            Route::put('/enfants/{id}', [FamilleController::class, 'updateEnfant']);
            Route::delete('/enfants/{id}', [FamilleController::class, 'deleteEnfant']);
        });

        // ✅ RÉCLAMATIONS - Routes pour les retraités
        Route::prefix('reclamations')->group(function () {
            Route::get('/types', [ReclamationController::class, 'getTypesReclamations']);
            Route::get('/', [ReclamationController::class, 'index']);
            Route::post('/', [ReclamationController::class, 'store']);
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
            Route::delete('/{id}', [App\Http\Controllers\DocumentController::class, 'destroy']);
            Route::get('/download/{id}', [App\Http\Controllers\DocumentController::class, 'download'])->name('retraites.documents.download');
            });


        // ✅ RENDEZ-VOUS - Routes corrigées pour les retraités
        Route::prefix('rendez-vous')->group(function () {
            Route::get('/', [RendezVousController::class, 'index']);
            Route::post('/', [RendezVousController::class, 'store']);
            Route::get('/historique', [RendezVousController::class, 'historique']);
            Route::get('/creneaux-disponibles/{date}', [RendezVousController::class, 'getCreneauxDisponibles']);
            Route::get('/{id}', [RendezVousController::class, 'show']);
            Route::put('/{id}/annuler', [RendezVousController::class, 'annuler']);
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

    // ✅ NOUVEAU : Routes d'administration pour les rendez-vous
    Route::prefix('admin/rendez-vous')->group(function () {
        Route::get('/', [RendezVousController::class, 'indexAdmin']);
        Route::get('/statistiques', [RendezVousController::class, 'statistiquesAdmin']);
        Route::put('/{id}/statut', [RendezVousController::class, 'changerStatut']);
        Route::get('/export', [RendezVousController::class, 'export']);
        Route::get('/creneaux-occupes/{date}', [RendezVousController::class, 'getCreneauxOccupes']);
        Route::get('/rechercher', [RendezVousController::class, 'rechercher']);
        Route::delete('/{id}', [RendezVousController::class, 'destroy']);
        Route::get('/rapport', [RendezVousController::class, 'rapport']);
    });
});

// ✅ Route de fallback pour API
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Route non trouvée',
        'error' => 'La route demandée n\'existe pas'
    ], 404);
});