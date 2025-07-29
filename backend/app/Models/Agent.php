<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Agent extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'matricule_solde',
        'nom',
        'prenoms',
        'poste',
        'direction',
        'grade',
        'date_prise_service',
        'email',
        'telephone',
        'password',
        'first_login',
        'password_changed',
        'status',
        'is_active'
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'date_prise_service' => 'date',
        'first_login' => 'boolean',
        'password_changed' => 'boolean',
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
    ];

    /**
     * Vérifier si le matricule solde est valide (7 ou 13 caractères)
     */
    public static function isValidMatricule($matricule)
    {
        return preg_match('/^[0-9]{12}[A-Z]$/', $matricule);       
    }

    /**
     * Obtenir les chiffres du matricule (mot de passe temporaire)
     */
    public function getTemporaryPassword()
    {
        $length = strlen($this->matricule_solde);
        return $length === 7 ? substr($this->matricule_solde, 0, 6) : substr($this->matricule_solde, 0, 12);
    }

    /**
     * Vérifier si l'agent peut se connecter
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
     * Scope pour les agents actifs
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
}