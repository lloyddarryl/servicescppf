<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Retraite extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'numero_pension',
        'nom',
        'prenoms',
        'date_naissance',
        'date_retraite',
        'ancien_poste',
        'ancienne_direction',
        'parcours_professionnel',
        'montant_pension',
        'email',
        'telephone',
        'password',
        'first_login',
        'password_changed',
        'status',
        'is_active',
        'verification_code',
        'verification_code_expires_at'
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'date_retraite' => 'date',
        'montant_pension' => 'decimal:2',
        'first_login' => 'boolean',
        'password_changed' => 'boolean',
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
    ];

    /**
     * Vérifier si le numéro de pension est valide (uniquement des chiffres)
     */
    public static function isValidNumeroPension($numero)
    {
        return preg_match('/^[0-9]+$/', $numero);
    }

    /**
     * Vérifier si le retraité peut se connecter
     */
    public function canLogin()
    {
        return $this->is_active && $this->status === 'actif';
    }

    /**
     * Nom complet
     */
    public function getFullNameAttribute()
    {
        return $this->prenoms . ' ' . $this->nom;
    }

    /**
     * Âge du retraité
     */
    public function getAgeAttribute()
    {
        return $this->date_naissance->age;
    }

    /**
     * Années de retraite
     */
    public function getAnneesRetraiteAttribute()
    {
        return $this->date_retraite->diffInYears(now());
    }

    /**
     * Scope pour les retraités actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'actif');
    }

    /**
     * Scope pour première connexion
     */
    public function scopeFirstLogin($query)
    {
        return $query->where('first_login', true);
    }

    /**
     * Formater le montant de la pension
     */
    public function getFormattedPensionAttribute()
    {
        return number_format($this->montant_pension, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Relation avec les enfants
     *//**
 * Relation avec le conjoint actif
 */
public function conjoint()
{
    return $this->hasOne(Conjoint::class)->where('statut', 'ACTIF');
}

/**
 * Relation avec tous les conjoints (historique)
 */
public function conjoints()
{
    return $this->hasMany(Conjoint::class);
}

/**
 * Relation avec les enfants actifs
 */
public function enfants()
{
    return $this->hasMany(Enfant::class)->where('actif', true);
}

/**
 * Relation avec tous les enfants (y compris inactifs)
 */
public function tousLesEnfants()
{
    return $this->hasMany(Enfant::class);
}

/**
 * Obtenir le nombre d'enfants actifs
 */
public function getNombreEnfantsAttribute()
{
    return $this->enfants()->count();
}

/**
 * Vérifier si le retraité a un conjoint
 */
public function getAConjointAttribute()
{
    return $this->conjoint()->exists();
}
}