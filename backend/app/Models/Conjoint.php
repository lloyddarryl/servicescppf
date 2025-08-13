<?php
// File: app/Models/Conjoint.php - Version mise à jour

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Conjoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'retraite_id', // ✅ Nouveau champ
        'matricule_conjoint',
        'nag_conjoint',
        'nom',
        'prenoms',
        'sexe',
        'date_naissance',
        'date_mariage',
        'statut',
        'travaille',
        'profession'
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'date_mariage' => 'date',
        'travaille' => 'boolean'
    ];

    /**
     * Relation avec l'agent (si c'est un agent actif)
     */
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * ✅ Relation avec le retraité (si c'est un retraité)
     */
    public function retraite()
    {
        return $this->belongsTo(Retraite::class);
    }

    /**
     * ✅ Obtenir le parent (agent ou retraité)
     */
    public function getParentAttribute()
    {
        return $this->agent ?: $this->retraite;
    }

    /**
     * ✅ Obtenir le type de parent
     */
    public function getTypeParentAttribute()
    {
        return $this->agent_id ? 'agent' : 'retraite';
    }

    /**
     * Obtenir l'âge du conjoint
     */
    public function getAgeAttribute()
    {
        return $this->date_naissance ? $this->date_naissance->age : null;
    }

    /**
     * Obtenir le nom complet
     */
    public function getNomCompletAttribute()
    {
        return trim($this->prenoms . ' ' . $this->nom);
    }

    /**
     * Vérifier si le conjoint est actif
     */
    public function scopeActif($query)
    {
        return $query->where('statut', 'ACTIF');
    }

    /**
     * Obtenir l'identifiant du conjoint (matricule ou NAG)
     */
    public function getIdentifiantAttribute()
    {
        return $this->matricule_conjoint ?: $this->nag_conjoint;
    }
}