<?php
// File: app/Models/GrilleIndiciaire.php

class GrilleIndiciaire extends Model
{
    use HasFactory;

    protected $fillable = [
        'corps',
        'grade',
        'echelon',
        'indice_brut',
        'indice_majore',
        'valeur_point',
        'date_effet',
        'date_fin',
        'is_active'
    ];

    protected $casts = [
        'valeur_point' => 'decimal:2',
        'date_effet' => 'date',
        'date_fin' => 'date',
        'is_active' => 'boolean'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

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
}