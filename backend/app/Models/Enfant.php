<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Enfant extends Model
{
    use HasFactory;

    protected $table = 'enfants';

    protected $fillable = [
        'enfant_id',
        'matricule_parent',
        'agent_id',
        'retraite_id',
        'nom',
        'prenoms',
        'sexe',
        'date_naissance',
        'prestation_familiale',
        'scolarise',
        'niveau_scolaire',
        'actif',
        'observations'
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'prestation_familiale' => 'boolean',
        'scolarise' => 'boolean',
        'actif' => 'boolean'
    ];

    /**
     * Relation avec l'agent parent (si c'est un agent actif)
     */
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * ✅ Relation avec le retraité parent (si c'est un retraité)
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
     * Relation avec les prestations familiales
     */
    public function prestations()
    {
        return $this->hasMany(PrestationFamiliale::class);
    }

    /**
     * Obtenir l'âge de l'enfant
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
     * Scope pour les enfants actifs
     */
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    /**
     * Scope pour les enfants qui touchent des prestations
     */
    public function scopeAvecPrestations($query)
    {
        return $query->where('prestation_familiale', true);
    }

    /**
     * Vérifier si l'enfant est mineur
     */
    public function getEstMineurAttribute()
    {
        return $this->age < 18;
    }

    /**
     * Vérifier si l'enfant est en âge scolaire
     */
    public function getEnAgeScolariteAttribute()
    {
        return $this->age >= 3 && $this->age <= 25;
    }

    /**
     * Générer le matricule parent sans la lettre
     */
    public static function generateMatriculeParent($matriculeComplet)
    {
        // Enlever la lettre finale du matricule (ex: 567890D -> 567890)
        return preg_replace('/[A-Z]$/', '', $matriculeComplet);
    }
}
