<?php
// Fichier: debug_famille.php - Version améliorée

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Agent;
use App\Models\Retraite;
use App\Models\Conjoint;
use App\Models\Enfant;

echo "🔍 DIAGNOSTIC COMPLET GRAPPE FAMILIALE\n";
echo "======================================\n\n";

try {
    // 1. Statistiques générales
    echo "1. STATISTIQUES GÉNÉRALES\n";
    echo "-------------------------\n";
    
    $agentsCount = Agent::count();
    $retraitesCount = Retraite::count();
    $conjointsCount = Conjoint::count();
    $enfantsCount = Enfant::count();
    
    echo "📊 Agents actifs: $agentsCount\n";
    echo "📊 Retraités: $retraitesCount\n";
    echo "📊 Conjoints: $conjointsCount\n";
    echo "📊 Enfants: $enfantsCount\n";
    
    // 2. Répartition des conjoints
    echo "\n2. RÉPARTITION DES CONJOINTS\n";
    echo "----------------------------\n";
    
    $conjointsAgents = Conjoint::whereNotNull('agent_id')->count();
    $conjointsRetraites = Conjoint::whereNotNull('retraite_id')->count();
    $conjointsTravaillent = Conjoint::whereNotNull('matricule_conjoint')->count();
    $conjointsNAG = Conjoint::whereNotNull('nag_conjoint')->count();
    
    echo "👫 Conjoints d'agents: $conjointsAgents\n";
    echo "👫 Conjoints de retraités: $conjointsRetraites\n";
    echo "💼 Conjoints qui travaillent: $conjointsTravaillent\n";
    echo "🏠 Conjoints avec NAG: $conjointsNAG\n";
    
    // 3. Répartition des enfants
    echo "\n3. RÉPARTITION DES ENFANTS\n";
    echo "--------------------------\n";
    
    $enfantsAgents = Enfant::whereNotNull('agent_id')->count();
    $enfantsRetraites = Enfant::whereNotNull('retraite_id')->count();
    $enfantsActifs = Enfant::where('actif', true)->count();
    $enfantsPrestations = Enfant::where('prestation_familiale', true)->count();
    $enfantsScolarises = Enfant::where('scolarise', true)->count();
    
    echo "👶 Enfants d'agents: $enfantsAgents\n";
    echo "👶 Enfants de retraités: $enfantsRetraites\n";
    echo "✅ Enfants actifs: $enfantsActifs\n";
    echo "💰 Enfants avec prestations: $enfantsPrestations\n";
    echo "🎓 Enfants scolarisés: $enfantsScolarises\n";
    
    // 4. Test des relations Eloquent
    echo "\n4. TEST DES RELATIONS ELOQUENT\n";
    echo "------------------------------\n";
    
    // Test Agent
    $agent = Agent::with(['conjoint', 'enfants'])->first();
    if ($agent) {
        echo "👨‍💼 Agent test: {$agent->prenoms} {$agent->nom}\n";
        echo "   Conjoint: " . ($agent->conjoint ? $agent->conjoint->prenoms . ' ' . $agent->conjoint->nom : 'Aucun') . "\n";
        echo "   Enfants: " . $agent->enfants->count() . "\n";
        
        if ($agent->enfants->count() > 0) {
            foreach ($agent->enfants as $enfant) {
                $age = \Carbon\Carbon::parse($enfant->date_naissance)->age;
                echo "     - {$enfant->prenoms} {$enfant->nom} ({$age} ans)\n";
            }
        }
    }
    
    // Test Retraité
    $retraite = Retraite::with(['conjoint', 'enfants'])->first();
    if ($retraite) {
        echo "\n👴 Retraité test: {$retraite->prenoms} {$retraite->nom}\n";
        echo "   Conjoint: " . ($retraite->conjoint ? $retraite->conjoint->prenoms . ' ' . $retraite->conjoint->nom : 'Aucun') . "\n";
        echo "   Enfants: " . $retraite->enfants->count() . "\n";
        
        if ($retraite->enfants->count() > 0) {
            foreach ($retraite->enfants as $enfant) {
                $age = \Carbon\Carbon::parse($enfant->date_naissance)->age;
                echo "     - {$enfant->prenoms} {$enfant->nom} ({$age} ans)\n";
            }
        }
    }
    
    // 5. Test de la logique métier
    echo "\n5. TEST LOGIQUE MÉTIER\n";
    echo "---------------------\n";
    
    // Calculer quelques statistiques intéressantes
    $enfantsMineurs = Enfant::whereRaw('DATEDIFF(CURDATE(), date_naissance) < 18*365')->count();
    $famillesCompletes = 0;
    $famillesMonoparentales = 0;
    
    // Compter les familles complètes vs monoparentales
    $tousLesParents = collect();
    $tousLesParents = $tousLesParents->merge(Agent::with('conjoint')->get());
    $tousLesParents = $tousLesParents->merge(Retraite::with('conjoint')->get());
    
    foreach ($tousLesParents as $parent) {
        if ($parent->conjoint) {
            $famillesCompletes++;
        } else {
            $famillesMonoparentales++;
        }
    }
    
    echo "👶 Enfants mineurs: $enfantsMineurs\n";
    echo "👨‍👩‍👧‍👦 Familles complètes: $famillesCompletes\n";
    echo "👨‍👧‍👦 Familles monoparentales: $famillesMonoparentales\n";
    
    // 6. Vérifications de cohérence
    echo "\n6. VÉRIFICATIONS DE COHÉRENCE\n";
    echo "-----------------------------\n";
    
    $conjointsOrphelins = Conjoint::whereNull('agent_id')->whereNull('retraite_id')->count();
    $enfantsOrphelins = Enfant::whereNull('agent_id')->whereNull('retraite_id')->count();
    $conjointsSansIdentifiant = Conjoint::whereNull('matricule_conjoint')->whereNull('nag_conjoint')->count();
    
    echo ($conjointsOrphelins > 0 ? "❌" : "✅") . " Conjoints sans parent: $conjointsOrphelins\n";
    echo ($enfantsOrphelins > 0 ? "❌" : "✅") . " Enfants sans parent: $enfantsOrphelins\n";
    echo ($conjointsSansIdentifiant > 0 ? "⚠️" : "✅") . " Conjoints sans identifiant: $conjointsSansIdentifiant\n";
    
    // 7. Données pour les tests
    echo "\n7. COMPTES DISPONIBLES POUR LES TESTS\n";
    echo "=====================================\n";
    
    echo "🔑 AGENTS ACTIFS :\n";
    $agents = Agent::where('password_changed', true)->get();
    foreach ($agents as $agent) {
        echo "  - Email: {$agent->email}\n";
        echo "    Nom: {$agent->prenoms} {$agent->nom}\n";
        echo "    Matricule: {$agent->matricule_solde}\n";
        echo "    Famille: " . ($agent->conjoint ? "Conjoint" : "Célibataire") . 
             " / " . $agent->enfants->count() . " enfant(s)\n\n";
    }
    
    echo "🔑 RETRAITÉS :\n";
    $retraites = Retraite::where('password_changed', true)->get();
    foreach ($retraites as $retraite) {
        echo "  - Email: {$retraite->email}\n";
        echo "    Nom: {$retraite->prenoms} {$retraite->nom}\n";
        echo "    N° Pension: {$retraite->numero_pension}\n";
        echo "    Famille: " . ($retraite->conjoint ? "Conjoint" : "Seul") . 
             " / " . $retraite->enfants->count() . " enfant(s)\n\n";
    }
    
    echo "🔑 PREMIÈRE CONNEXION :\n";
    $nouveaux = Agent::where('first_login', true)->get();
    foreach ($nouveaux as $nouveau) {
        echo "  - Matricule: {$nouveau->matricule_solde}\n";
        echo "    Nom: {$nouveau->prenoms} {$nouveau->nom}\n";
        echo "    Mot de passe temporaire: " . substr($nouveau->matricule_solde, 0, -1) . "\n\n";
    }
    
    echo "✅ DIAGNOSTIC TERMINÉ AVEC SUCCÈS !\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur durant le diagnostic: " . $e->getMessage() . "\n";
    echo "Ligne: " . $e->getLine() . "\n";
    echo "Fichier: " . $e->getFile() . "\n";
}