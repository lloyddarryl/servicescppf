<?php
// File: app/Models/SimulationPension.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SimulationPension extends Model
{
    use HasFactory;

    // ✅ CORRECTION : Spécifier le nom de la table
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
        'coefficient_temporel',
        'pension_apres_coefficient',
        'annee_pension',
        'methode_calcul',
        'parametres_utilises'
    ];

    protected $casts = [
        'date_simulation' => 'date',
        'date_retraite_prevue' => 'date',
        'duree_service_simulee' => 'decimal:2',
        'salaire_reference' => 'decimal:2',
        'taux_liquidation' => 'decimal:2',
        'pension_base' => 'decimal:2',
        'bonifications' => 'decimal:2',
        'pension_totale' => 'decimal:2',
        'coefficient_temporel' => 'decimal:2',
        'pension_apres_coefficient' => 'decimal:2',
        'parametres_utilises' => 'array'
    ];

    /**
     * Relation avec l'agent
     */
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Scope pour obtenir les simulations récentes
     */
    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('date_simulation', 'desc')->limit($limit);
    }

    /**
     * Scope pour un agent spécifique
     */
    public function scopeForAgent($query, $agentId)
    {
        return $query->where('agent_id', $agentId);
    }
}