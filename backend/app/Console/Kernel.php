<?php
// app/Console/Kernel.php - Ajouter ces lignes dans la méthode schedule()

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Envoi des rappels RDV J-1 tous les jours à 18h00
        $schedule->command('rdv:rappels')
            ->dailyAt('18:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/rdv-rappels.log'));

        // Optionnel : rappels J-7 tous les lundis à 9h00  
        $schedule->command('rdv:rappels-j7')
            ->weekly()
            ->mondays()
            ->at('09:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/rdv-rappels-j7.log'));

        // Nettoyage des anciennes données tous les dimanches à 2h00
        $schedule->command('rdv:cleanup')
            ->weekly()
            ->sundays()
            ->at('02:00')
            ->withoutOverlapping()
            ->onOneServer();
    }
}