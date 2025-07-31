<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
// Et ajouter cette route dans api.php :
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
    
    // Routes d'authentification (communes)
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'getCurrentUser']);
        Route::get('/verify', [AuthController::class, 'verifyToken']);
    });

    // Routes de profil utilisateur (communes) - gardées pour compatibilité
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::put('/password', [ProfileController::class, 'changePassword']);
        Route::post('/verify-phone', [ProfileController::class, 'verifyPhone']);
        Route::post('/resend-verification', [ProfileController::class, 'resendVerification']);
    });

    // Routes spécifiques aux agents actifs avec préfixe /actifs
    Route::middleware('check.user.type:actif')->prefix('actifs')->group(function () {
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
        
        // ✅ Routes de profil - méthodes uniques avec middleware
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
    });

    // Routes spécifiques aux retraités avec préfixe /retraites
    Route::middleware('check.user.type:retraite')->prefix('retraites')->group(function () {
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
        
        // ✅ Routes de profil - méthodes uniques avec middleware
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
    });

    // Route générale pour le dashboard (redirige selon le type d'utilisateur)
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // À ajouter TEMPORAIREMENT dans api.php pour tester

// Route de test sans middleware
Route::middleware('auth:sanctum')->get('/test-profile', function (Request $request) {
    try {
        $user = $request->user();
        
        return response()->json([
            'success' => true,
            'user_type' => get_class($user),
            'user_id' => $user->id,
            'middleware_test' => 'OK - Sans middleware'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Ajout dans routes/api.php

// Routes pour le simulateur de pension (agents actifs uniquement)
Route::middleware(['auth:sanctum', 'check.user.type:actif'])->prefix('actifs')->group(function () {
    
    // Simulateur de pension
    Route::prefix('simulateur-pension')->group(function () {
        // Obtenir le profil pour simulation
        Route::get('/profil', [App\Http\Controllers\PensionSimulatorController::class, 'getProfile']);
        
        // Lancer une simulation
        Route::post('/simuler', [App\Http\Controllers\PensionSimulatorController::class, 'simulatePension']);
        
        // Obtenir l'historique des simulations
        Route::get('/historique', [App\Http\Controllers\PensionSimulatorController::class, 'getSimulationHistory']);
        
        // Obtenir les paramètres de calcul
        Route::get('/parametres', [App\Http\Controllers\PensionSimulatorController::class, 'getParameters']);
        
        // Obtenir les grilles indiciaires
        Route::get('/grilles', [App\Http\Controllers\PensionSimulatorController::class, 'getGrilles']);
        
        // Sauvegarder une simulation favorite
        Route::post('/sauvegarder/{id}', [App\Http\Controllers\PensionSimulatorController::class, 'saveFavorite']);
        
        // Supprimer une simulation
        Route::delete('/supprimer/{id}', [App\Http\Controllers\PensionSimulatorController::class, 'deleteSimulation']);
        
        // Exporter une simulation en PDF
        Route::get('/exporter/{id}', [App\Http\Controllers\PensionSimulatorController::class, 'exportPDF']);
        
        // Comparer plusieurs simulations
        Route::post('/comparer', [App\Http\Controllers\PensionSimulatorController::class, 'compareSimulations']);
    });
    
    // Gestion de carrière (pour alimenter le simulateur)
    Route::prefix('carriere')->group(function () {
        // Obtenir l'historique de carrière complet
        Route::get('/historique', [App\Http\Controllers\CarriereController::class, 'getHistorique']);
        
        // Obtenir la position actuelle
        Route::get('/position-actuelle', [App\Http\Controllers\CarriereController::class, 'getPositionActuelle']);
        
        // Mettre à jour les informations de carrière
        Route::put('/mettre-a-jour', [App\Http\Controllers\CarriereController::class, 'updateCarriere']);
        
        // Projeter une évolution de carrière
        Route::post('/projeter-evolution', [App\Http\Controllers\CarriereController::class, 'projeterEvolution']);
    });
});

// Routes pour les retraités (consultation uniquement)
Route::middleware(['auth:sanctum', 'check.user.type:retraite'])->prefix('retraites')->group(function () {
    
    // Consultation de pension existante
    Route::prefix('pension-actuelle')->group(function () {
        // Détails de la pension actuelle
        Route::get('/details', [App\Http\Controllers\PensionSimulatorController::class, 'getPensionDetails']);
        
        // Historique des versements
        Route::get('/historique-versements', [App\Http\Controllers\PensionSimulatorController::class, 'getHistoriqueVersements']);
        
        // Simuler une révision de pension
        Route::post('/simuler-revision', [App\Http\Controllers\PensionSimulatorController::class, 'simulerRevision']);
    });
});

// Routes communes (dashboard mis à jour)
Route::middleware('auth:sanctum')->group(function () {
    // Mise à jour du dashboard pour inclure le simulateur
    Route::get('/dashboard-extended', [App\Http\Controllers\DashboardController::class, 'getExtendedDashboard']);
});

});