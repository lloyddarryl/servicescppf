<?php
// File: app/Models/CoefficientTemporel.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoefficientTemporel extends Model
{
    use HasFactory;

    protected $table = 'coefficients_temporels';

    protected $fillable = [
        'annee',
        'coefficient',
        'periode_debut',
        'periode_fin',
        'description',
        'actif'
    ];

    protected $casts = [
        'annee' => 'integer',
        'coefficient' => 'decimal:2',
        'actif' => 'boolean'
    ];

    /**
     * Obtenir le coefficient pour une année donnée
     */
    public static function getCoefficient($annee)
    {
        $coefficient = static::where('annee', $annee)
            ->where('actif', true)
            ->first();
        
        if ($coefficient) {
            return $coefficient->coefficient;
        }
        
        // Si pas trouvé et année >= 2029, retourner 100%
        if ($annee >= 2029) {
            return 100;
        }
        
        // Valeur par défaut selon Article 94
        return 91; // Coefficient de 2025 par défaut
    }

    /**
     * Scope pour les coefficients actifs
     */
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    /**
     * Initialiser les coefficients de base
     */
    public static function initCoefficients()
    {
        $coefficients = [
            2015 => 70, 2016 => 72, 2017 => 74, 2018 => 76,
            2019 => 79, 2020 => 81, 2021 => 83, 2022 => 85,
            2023 => 87, 2024 => 89, 2025 => 91, 2026 => 94,
            2027 => 96, 2028 => 98, 2029 => 100, 2030 => 100
        ];

        foreach ($coefficients as $annee => $coeff) {
            static::updateOrCreate(
                ['annee' => $annee],
                [
                    'coefficient' => $coeff,
                    'periode_debut' => "01/01/$annee",
                    'periode_fin' => "31/12/$annee",
                    'description' => "Coefficient temporel pour l'année $annee selon Article 94",
                    'actif' => true
                ]
            );
        }
    }
}