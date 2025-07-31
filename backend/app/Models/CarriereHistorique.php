<?php
// File: app/Models/CarriereHistorique.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarriereHistorique extends Model
{
    use HasFactory;

    protected $table = 'carrieres';

    protected $fillable = [
        'agent_id',
        'matricule_assure',
        'grade_code',
        'grade_libelle',
        'indice',
        'statut',
        'position_administrative',
        'presence',
        'fonction',
        'corps',
        'etablissement',
        'departement_ministere',
        'salaire_brut',
        'salaire_net',
        'salaire_base',
        'montant_bonifications',
        'cotisations',
        'detachement',
        'date_debut_detachement',
        'date_fin_detachement',
        'date_suspension_solde',
        'date_carriere',
        'etat_general',
        'taux_cotisation',
        'valide'
    ];

    protected $casts = [
        'date_debut_detachement' => 'date',
        'date_fin_detachement' => 'date',
        'date_suspension_solde' => 'date',
        'date_carriere' => 'date',
        'salaire_brut' => 'decimal:2',
        'salaire_net' => 'decimal:2',
        'salaire_base' => 'decimal:2',
        'montant_bonifications' => 'decimal:2',
        'cotisations' => 'decimal:2',
        'taux_cotisation' => 'decimal:2',
        'detachement' => 'boolean',
        'valide' => 'boolean'
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    // Scope pour obtenir la carrière active
    public function scopeActive($query)
    {
        return $query->where('valide', true)
                    ->where('position_administrative', 'ACTIVITE');
    }

    // Scope pour une période donnée
    public function scopePeriode($query, $dateDebut, $dateFin)
    {
        return $query->whereBetween('date_carriere', [$dateDebut, $dateFin]);
    }
}

// File: app/Models/GrilleIndiciaire.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrilleIndiciaire extends Model
{
    use HasFactory;

    protected $table = 'grilles_indiciaires';

    protected $fillable = [
        'type_grille',
        'categorie',
        'classe',
        'duree_classe',
        'indice_ancien',
        'indice_nouveau',
        'valeur_point'
    ];

    protected $casts = [
        'classe' => 'integer',
        'duree_classe' => 'integer',
        'indice_ancien' => 'integer',
        'indice_nouveau' => 'integer',
        'valeur_point' => 'decimal:2'
    ];

    // Obtenir le salaire pour un indice donné
    public static function getSalaireByIndice($indice, $typeGrille = 'CIVIL')
    {
        $grille = self::where('type_grille', $typeGrille)
                     ->where('indice_nouveau', $indice)
                     ->first();

        if ($grille) {
            return $indice * $grille->valeur_point;
        }

        // Valeur par défaut
        return $indice * 500;
    }

    // Obtenir la progression dans une catégorie
    public static function getProgression($typeGrille, $categorie)
    {
        return self::where('type_grille', $typeGrille)
                  ->where('categorie', $categorie)
                  ->orderBy('classe')
                  ->get();
    }
}

// File: app/Models/ParametrePension.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ParametrePension extends Model
{
    use HasFactory;

    protected $table = 'parametres_pension';

    protected $fillable = [
        'code_parametre',
        'libelle',
        'valeur',
        'unite',
        'description',
        'date_effet',
        'date_fin',
        'actif'
    ];

    protected $casts = [
        'valeur' => 'decimal:4',
        'date_effet' => 'date',
        'date_fin' => 'date',
        'actif' => 'boolean'
    ];

    // Obtenir la valeur d'un paramètre à une date donnée
    public static function getValeur($code, $date = null)
    {
        $date = $date ?? now();
        
        $parametre = self::where('code_parametre', $code)
                        ->where('actif', true)
                        ->where('date_effet', '<=', $date)
                        ->where(function($query) use ($date) {
                            $query->whereNull('date_fin')
                                  ->orWhere('date_fin', '>=', $date);
                        })
                        ->orderBy('date_effet', 'desc')
                        ->first();

        return $parametre ? $parametre->valeur : null;
    }

    // Créer les paramètres par défaut
    public static function createDefaults()
    {
        $defaults = [
            ['AGE_RETRAITE', 'Âge légal de retraite', 60, 'années'],
            ['DUREE_MIN_SERVICE', 'Durée minimale de service', 15, 'années'],
            ['TAUX_LIQUIDATION_BASE', 'Taux de liquidation de base', 37.5, '%'],
            ['TAUX_LIQUIDATION_MAX', 'Taux de liquidation maximum', 75, '%'],
            ['AUGMENTATION_ANNUELLE', 'Augmentation par année de service', 1.25, '%'],
            ['VALEUR_POINT_INDICE', 'Valeur du point d\'indice', 500, 'FCFA'],
            ['BONIF_CONJOINT', 'Bonification pour conjoint', 3, '%'],
            ['BONIF_ENFANT', 'Bonification par enfant', 2, '%']
        ];

        foreach ($defaults as $default) {
            self::updateOrCreate(
                ['code_parametre' => $default[0]],
                [
                    'libelle' => $default[1],
                    'valeur' => $default[2],
                    'unite' => $default[3],
                    'date_effet' => now()->startOfYear(),
                    'actif' => true
                ]
            );
        }
    }
}

// File: app/Models/SimulationPension.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimulationPension extends Model
{
    use HasFactory;

    protected $table = 'simulations_pension';

    protected $fillable = [
        'agent_id',
        'date_simulation',
        'date_retraite_prevue',
        'duree_service_simulee',
        'indice_simule',
        'salaire_reference',
        'taux_liquidation',
        'pension_base',
        'bonifications',
        'pension_totale',
        'parametres_utilises'
    ];

    protected $casts = [
        'date_simulation' => 'datetime',
        'date_retraite_prevue' => 'date',
        'duree_service_simulee' => 'decimal:2',
        'salaire_reference' => 'decimal:2',
        'taux_liquidation' => 'decimal:2',
        'pension_base' => 'decimal:2',
        'bonifications' => 'decimal:2',
        'pension_totale' => 'decimal:2',
        'parametres_utilises' => 'array'
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    // Formater la pension en FCFA
    public function getPensionFormatteeAttribute()
    {
        return number_format($this->pension_totale, 0, ',', ' ') . ' FCFA';
    }

    // Calculer le pourcentage du salaire
    public function getPourcentageSalaireAttribute()
    {
        if ($this->salaire_reference > 0) {
            return round(($this->pension_totale / $this->salaire_reference) * 100, 2);
        }
        return 0;
    }

    // Scope pour les simulations récentes
    public function scopeRecentes($query, $jours = 30)
    {
        return $query->where('date_simulation', '>=', now()->subDays($jours));
    }
}

// Mise à jour du modèle Agent existant
