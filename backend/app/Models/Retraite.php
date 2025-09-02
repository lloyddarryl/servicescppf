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

// Ajoutez ces relations à la fin de la classe Retraite, avant la fermeture }

/**
 * Relation avec tous les documents
 */
public function documents()
{
    return $this->hasMany(DocumentRetraite::class);
}

/**
 * Relation avec les documents actifs
 */
public function documentsActifs()
{
    return $this->hasMany(DocumentRetraite::class)->where('statut', 'actif');
}

/**
 * Relation avec les certificats de vie
 */
public function certificatsVie()
{
    return $this->hasMany(DocumentRetraite::class)->where('type_document', 'certificat_vie');
}

/**
 * Obtenir le titre de civilité selon le sexe et la situation matrimoniale
 */
public function getTitreCiviliteAttribute()
{
    if (!$this->sexe) return '';
    
    $sexeNormalized = strtoupper($this->sexe);
    
    if ($sexeNormalized === 'M') {
        return 'M.';
    } elseif ($sexeNormalized === 'F') {
        $situation = strtolower($this->situation_matrimoniale ?? '');
        
        switch ($situation) {
            case 'mariée':
            case 'marie':
            case 'marié':
            case 'mariee':
                return 'Mme';
            case 'veuve':
            case 'veuf':
                return 'Mme';
            case 'divorcée':
            case 'divorce':
            case 'divorcee':
                return 'Mme';
            case 'célibataire':
            case 'celibataire':
                return 'Mlle';
            default:
                return 'Mme';
        }
    }
    
    return '';
}

/**
 * Obtenir le nom complet avec titre de civilité
 */
public function getNomCompletAvecTitreAttribute()
{
    $titre = $this->titre_civilite;
    $nomComplet = $this->prenoms . ' ' . $this->nom;
    
    return $titre ? "{$titre} {$nomComplet}" : $nomComplet;
}

/**
 * Obtenir les notifications de certificat de vie
 */
public function getNotificationsCertificatAttribute()
{
    return DocumentRetraite::getNotificationsCertificat($this->id);
}

/**
 * Obtenir les statistiques des documents
 */
public function getStatistiquesDocumentsAttribute()
{
    return DocumentRetraite::getStatistiques($this->id);
}

}