<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParametrePension extends Model
{
    use HasFactory;

    protected $table = 'parametres_pension';

    protected $fillable = [
        'code_parametre',
        'libelle',
        'valeur',
        'type_valeur',
        'description',
        'is_active',
        'date_effet',
        'date_fin'
    ];

    protected $casts = [
        'valeur' => 'decimal:4',
        'is_active' => 'boolean',
        'date_effet' => 'date',
        'date_fin' => 'date',
    ];

    /**
     * Scope pour les paramètres actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function($q) {
                        $q->whereNull('date_fin')
                          ->orWhere('date_fin', '>=', now());
                    });
    }

    /**
     * Scope pour les paramètres en vigueur à une date donnée
     */
    public function scopeEnVigueur($query, $date = null)
    {
        $date = $date ?? now();
        
        return $query->where('is_active', true)
                    ->where('date_effet', '<=', $date)
                    ->where(function($q) use ($date) {
                        $q->whereNull('date_fin')
                          ->orWhere('date_fin', '>=', $date);
                    });
    }

    /**
     * Obtenir un paramètre par son code
     */
    public static function getByCode($code, $date = null)
    {
        $query = static::where('code_parametre', $code);
        
        if ($date) {
            $query->enVigueur($date);
        } else {
            $query->active();
        }
        
        return $query->first();
    }

    /**
     * Obtenir la valeur d'un paramètre par son code
     */
    public static function getValeur($code, $date = null)
    {
        $parametre = static::getByCode($code, $date);
        return $parametre ? $parametre->valeur : null;
    }

    /**
     * Mettre à jour ou créer un paramètre
     */
    public static function updateOrCreateParametre($code, $libelle, $valeur, $typeValeur = 'decimal', $description = null)
    {
        return static::updateOrCreate(
            ['code_parametre' => $code],
            [
                'libelle' => $libelle,
                'valeur' => $valeur,
                'type_valeur' => $typeValeur,
                'description' => $description,
                'is_active' => true,
                'date_effet' => now(),
            ]
        );
    }
}