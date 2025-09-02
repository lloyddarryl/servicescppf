<?php
// app/Console/Commands/EnvoyerRappelsRdv.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RendezVousDemande;
use App\Models\Agent;
use App\Models\Retraite;
use App\Mail\RendezVousRappelMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EnvoyerRappelsRdv extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'rdv:rappels 
                           {--dry-run : Simuler l\'envoi sans envoyer les emails}
                           {--force : Forcer l\'envoi même si déjà envoyé}';

    /**
     * The console command description.
     */
    protected $description = 'Envoyer les rappels automatiques pour les RDV du lendemain';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔔 Début de l\'envoi des rappels RDV...');

        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('⚠️  Mode simulation activé - Aucun email ne sera envoyé');
        }

        try {
            // Récupérer les RDV confirmés pour demain
            $demain = Carbon::tomorrow();
            $rdvDemain = $this->getRdvPourDemain($demain, $force);

            if ($rdvDemain->isEmpty()) {
                $this->info('✅ Aucun rendez-vous trouvé pour demain (' . $demain->format('d/m/Y') . ')');
                return 0;
            }

            $this->info("📅 {$rdvDemain->count()} rendez-vous trouvé(s) pour demain ({$demain->format('d/m/Y')})");

            $rappelsEnvoyes = 0;
            $rappelsEchoues = 0;

            // Traitement de chaque RDV
            foreach ($rdvDemain as $rdv) {
                try {
                    $user = $this->getUser($rdv);
                    
                    if (!$user) {
                        $this->error("❌ Utilisateur introuvable pour RDV {$rdv->numero_demande}");
                        $rappelsEchoues++;
                        continue;
                    }

                    if (!$user->email) {
                        $this->error("❌ Email manquant pour {$user->prenoms} {$user->nom} (RDV {$rdv->numero_demande})");
                        $rappelsEchoues++;
                        continue;
                    }

                    $this->info("📧 Traitement RDV {$rdv->numero_demande} - {$user->prenoms} {$user->nom}");

                    if (!$dryRun) {
                        // Envoyer l'email de rappel
                        Mail::to($user->email)->send(new RendezVousRappelMail($rdv, $user));

                        // Marquer comme rappel envoyé
                        $rdv->update([
                            'rappel_j1_envoye' => true,
                            'date_rappel_j1' => now()
                        ]);

                        Log::info('Rappel RDV J-1 envoyé:', [
                            'rdv_id' => $rdv->id,
                            'numero' => $rdv->numero_demande,
                            'user_email' => $user->email,
                            'date_rdv' => $rdv->date_rdv_confirme->format('Y-m-d H:i')
                        ]);
                    }

                    $rappelsEnvoyes++;
                    $this->line("   ✅ Rappel envoyé à {$user->email}");

                } catch (\Exception $e) {
                    $this->error("❌ Erreur pour RDV {$rdv->numero_demande}: {$e->getMessage()}");
                    
                    Log::error('Erreur envoi rappel RDV:', [
                        'rdv_id' => $rdv->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    $rappelsEchoues++;
                }
            }

            // Résumé
            $this->info("\n📊 Résumé de l'envoi:");
            $this->info("   ✅ Rappels envoyés: {$rappelsEnvoyes}");
            
            if ($rappelsEchoues > 0) {
                $this->warn("   ❌ Rappels échoués: {$rappelsEchoues}");
            }

            if ($dryRun) {
                $this->warn("   ⚠️  Mode simulation - Aucun email réellement envoyé");
            }

            $this->info('🎉 Traitement terminé avec succès');
            return 0;

        } catch (\Exception $e) {
            $this->error('💥 Erreur générale: ' . $e->getMessage());
            
            Log::error('Erreur commande rappels RDV:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }

    /**
     * Récupérer les RDV confirmés pour demain
     */
    private function getRdvPourDemain(Carbon $demain, bool $force = false)
    {
        $query = RendezVousDemande::where('statut', 'accepte')
            ->whereNotNull('date_rdv_confirme')
            ->whereDate('date_rdv_confirme', $demain->format('Y-m-d'));

        // Si pas forcé, exclure ceux qui ont déjà reçu le rappel
        if (!$force) {
            $query->where(function($q) {
                $q->whereNull('rappel_j1_envoye')
                  ->orWhere('rappel_j1_envoye', false);
            });
        }

        return $query->orderBy('date_rdv_confirme')->get();
    }

    /**
     * Récupérer l'utilisateur selon le type
     */
    private function getUser(RendezVousDemande $rdv)
    {
        if ($rdv->user_type === 'agent') {
            return Agent::find($rdv->user_id);
        } elseif ($rdv->user_type === 'retraite') {
            return Retraite::find($rdv->user_id);
        }
        
        return null;
    }
}