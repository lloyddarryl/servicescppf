<?php
// app/Providers/AppServiceProvider.php - Ajoutez cette méthode à votre AppServiceProvider existant

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AccuseReceptionService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ✅ Enregistrer le service AccuseReceptionService
        $this->app->singleton(AccuseReceptionService::class, function ($app) {
            try {
                return new AccuseReceptionService();
            } catch (\Exception $e) {
                \Log::error('Erreur création AccuseReceptionService:', ['error' => $e->getMessage()]);
                return null;
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}