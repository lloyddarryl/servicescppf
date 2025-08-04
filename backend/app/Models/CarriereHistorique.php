<?php
// File: app/Models/CarriereHistorique.php

class CarriereHistorique extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'date_carriere',
        'poste',
        'grade',
        'direction',
        'indice',
        'corps',
        'valide',
        'observations'
    ];

    protected $casts = [
        'date_carriere' => 'date',
        'valide' => 'boolean'
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
}