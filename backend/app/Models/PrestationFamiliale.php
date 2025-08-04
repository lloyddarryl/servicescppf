<?php
// File: app/Models/PrestationFamiliale.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrestationFamiliale extends Model
{
    use HasFactory;

    protected $table = 'prestations_familiales';

    protected $fillable = [
        'enfant_id',
        'type_prestation',
        'montant',
        'date_debut',
        'date_fin',
        'statut',
        'motif_arret'
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'montant' => 'decimal:2'
    ];

    /**
     * Relation avec l'enfant
     */
    public function enfant()
    {
        return $this->belongsTo(Enfant::class);
    }

    /**
     * Scope pour les prestations actives
     */
    public function scopeActif($query)
    {
        return $query->where('statut', 'ACTIF');
    }

    /**
     * Scope pour les prestations en cours
     */
    public function scopeEnCours($query)
    {
        return $query->where('statut', 'ACTIF')
                    ->where('date_debut', '<=', now())
                    ->where(function($q) {
                        $q->whereNull('date_fin')
                          ->orWhere('date_fin', '>=', now());
                    });
    }
}