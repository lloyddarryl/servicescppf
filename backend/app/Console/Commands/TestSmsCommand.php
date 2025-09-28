<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SmsServices;

class TestSmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sms:test {phone : Le numéro de téléphone} {--message= : Message personnalisé}';

    /**
     * The console command description.
     */
    protected $description = 'Teste l\'envoi de SMS vers un numéro donné';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phone = $this->argument('phone');
        $customMessage = $this->option('message');
        
        $this->info("🚀 Test d'envoi SMS en cours...");
        $this->info("📱 Numéro cible: {$phone}");
        
        // Générer un code ou utiliser le message personnalisé
        if ($customMessage) {
            $message = $customMessage;
            $this->info("💬 Message: {$message}");
        } else {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $message = "Code de verification e-CPPF: {$code}. Valide pendant 15 minutes.";
            $this->info("🔢 Code généré: {$code}");
        }
        
        try {
            // Vérifier la configuration d'abord
            $smsService = new SmsServices();
            
            $this->info("🔧 Vérification de la configuration...");
            $configCheck = $smsService->checkConfiguration();
            
            if (!$configCheck['valid']) {
                $this->error("❌ Configuration SMS invalide:");
                foreach ($configCheck['issues'] as $issue) {
                    $this->error("   - {$issue}");
                }
                return Command::FAILURE;
            }
            
            $this->info("✅ Configuration SMS valide");
            
            // Afficher la configuration (sans le mot de passe)
            $this->table(['Paramètre', 'Valeur'], [
                ['URL', $configCheck['config']['url']],
                ['Client', $configCheck['config']['client']],
                ['From', $configCheck['config']['from']],
                ['Affiliate', $configCheck['config']['affiliate'] ?? 'Non défini'],
                ['Password', $configCheck['config']['password']]
            ]);
            
            // Envoyer le SMS
            $this->info("📤 Envoi du SMS...");
            
            if ($customMessage) {
                // Envoyer message personnalisé
                $result = $smsService->sendCustomMessage($phone, $message);
            } else {
                // Envoyer code de vérification
                $result = $smsService->sendVerificationCode($phone, $code);
            }
            
            // Afficher le résultat
            if ($result['success']) {
                $this->info("✅ SMS envoyé avec succès!");
                $this->info("📋 Réponse API: " . json_encode($result['response'], JSON_PRETTY_PRINT));
                
                // Demander confirmation de réception
                if ($this->confirm('🤔 Avez-vous reçu le SMS sur votre téléphone?')) {
                    $this->info("🎉 Test réussi! Le système SMS fonctionne correctement.");
                } else {
                    $this->warn("⚠️  SMS envoyé côté serveur mais non reçu. Vérifiez:");
                    $this->warn("   - Le numéro de téléphone");
                    $this->warn("   - La couverture réseau");
                    $this->warn("   - Les filtres anti-spam");
                    $this->warn("   - L'opérateur mobile");
                }
            } else {
                $this->error("❌ Échec de l'envoi SMS");
                $this->error("💀 Erreur: {$result['message']}");
                if (isset($result['response'])) {
                    $this->error("📋 Réponse API: " . json_encode($result['response'], JSON_PRETTY_PRINT));
                }
                return Command::FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error("💥 Erreur critique: {$e->getMessage()}");
            $this->error("🔍 Fichier: {$e->getFile()}:{$e->getLine()}");
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}