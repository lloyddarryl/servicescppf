<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;


class DocumentRetraite extends Model
{
    use HasFactory;
    
    protected $table = 'documents_retraites'; 

    protected $fillable = [
        'retraite_id',
        'nom_original',
        'nom_fichier',
        'chemin_fichier',
        'type_document',
        'description',
        'taille_fichier',
        'extension',
        'statut',
        'date_emission',
        'date_expiration',
        'autorite_emission',
        'date_depot',
        'notifie_par_email',
        'metadata'
    ];

    protected $casts = [
        'date_emission' => 'date',
        'date_expiration' => 'date', 
        'date_depot' => 'datetime',
        'notifie_par_email' => 'boolean',
        'metadata' => 'array'
    ];

    protected $dates = [
        'date_emission',
        'date_expiration',
        'date_depot'
    ];

    /**
     * Types de documents disponibles
     */
    public static $typesDocuments = [
        'certificat_vie' => [
            'nom' => 'Certificat de Vie',
            'description' => 'Document attestant que le retraité est en vie',
            'expire' => true,
            'duree_validite_mois' => 12,
            'obligatoire' => true,
            'icone' => '📋'
        ],
        'autre' => [
            'nom' => 'Autre Document',
            'description' => 'Tout autre document personnel',
            'expire' => false,
            'duree_validite_mois' => null,
            'obligatoire' => false,
            'icone' => '📄'
        ]
    ];

    /**
     * Extensions autorisées
     */
    public static $extensionsAutorisees = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];

    /**
     * Taille maximale en bytes (5 Mo)
     */
    public static $tailleMaximale = 5 * 1024 * 1024;

    /**
     * Relation avec le retraité
     */
    public function retraite()
    {
        return $this->belongsTo(Retraite::class);
    }

    /**
     * Scope pour les documents actifs
     */
    public function scopeActifs($query)
    {
        return $query->where('statut', 'actif');
    }

    /**
     * Scope pour les certificats de vie
     */
    public function scopeCertificatsVie($query)
    {
        return $query->where('type_document', 'certificat_vie');
    }

    /**
     * Scope pour les documents expirés
     */
    public function scopeExpires($query)
    {
        return $query->where('statut', 'expire')
                    ->orWhere(function($q) {
                        $q->where('date_expiration', '<', now())
                          ->where('statut', 'actif');
                    });
    }

    /**
     * Scope pour les documents qui expirent bientôt
     */
    public function scopeExpirentBientot($query, $jours = 30)
    {
        return $query->where('statut', 'actif')
                    ->whereNotNull('date_expiration')
                    ->whereBetween('date_expiration', [now(), now()->addDays($jours)]);
    }

    /**
     * Scope pour un retraité spécifique
     */
    public function scopePourRetraite($query, $retraiteId)
    {
        return $query->where('retraite_id', $retraiteId);
    }

    /**
     * Vérifier si le document est expiré
     */
    public function getIsExpireAttribute()
    {
        if (!$this->date_expiration) {
            return false;
        }
        
        return $this->date_expiration->isPast() && $this->statut === 'actif';
    }

    /**
     * Vérifier si le document expire bientôt
     */
    public function getExpireBientotAttribute()
    {
        if (!$this->date_expiration || $this->is_expire) {
            return false;
        }
        
         return $this->jours_avant_expiration <= 60;
    }

    /**
     * Obtenir le nombre de jours avant expiration
     */
    public function getJoursAvantExpirationAttribute()
    {
        if (!$this->date_expiration) {
            return null;
        }
        
        return (int) now()->diffInDays($this->date_expiration, false);
    }

    /**
     * Formater la taille du fichier
     */
    public function getTailleFormateeAttribute()
    {
        $bytes = $this->taille_fichier;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Obtenir le nom du type de document
     */
    public function getNomTypeAttribute()
    {
        return self::$typesDocuments[$this->type_document]['nom'] ?? 'Document';
    }

    /**
     * Obtenir l'icône du type de document
     */
    public function getIconeTypeAttribute()
    {
        return self::$typesDocuments[$this->type_document]['icone'] ?? '📄';
    }

    /**
     * Obtenir l'URL de téléchargement
     */
    public function getUrlTelechargementAttribute()
    {
            return url("/api/retraites/documents/{$this->id}/download");
    }

    /**
     * Vérifier si le fichier existe sur le disque
     */
    public function fichierExiste()
    {
        return Storage::exists($this->chemin_fichier);
    }

    /**
     * Supprimer le fichier physique
     */
    public function supprimerFichier()
    {
        if ($this->fichierExiste()) {
            Storage::delete($this->chemin_fichier);
        }
    }

    /**
     * Calculer la date d'expiration pour un certificat de vie
     */
    public static function calculerDateExpiration($dateEmission)
    {
        return Carbon::parse($dateEmission)->addMonths(12);
    }

    /**
     * Marquer comme expiré
     */
    public function marquerCommeExpire()
    {
        $this->update(['statut' => 'expire']);
    }

    /**
     * Marquer comme remplacé
     */
    public function marquerCommeRemplace()
    {
        $this->update(['statut' => 'remplace']);
    }

    /**
     * Obtenir le dernier certificat de vie actif pour un retraité
     */
    public static function dernierCertificatVie($retraiteId)
    {
        return self::pourRetraite($retraiteId)
                  ->certificatsVie()
                  ->actifs()
                  ->orderBy('date_depot', 'desc')
                  ->first();
    }

    /**
     * Vérifier si un retraité a un certificat de vie valide
     */
    public static function aCertificatVieValide($retraiteId)
    {
        $certificat = self::dernierCertificatVie($retraiteId);
        
        if (!$certificat) {
            return false;
        }
        
        return !$certificat->is_expire;
    }

    /**
     * Obtenir les notifications pour un retraité
     */
    public static function getNotificationsCertificat($retraiteId)
    {
        $certificat = self::dernierCertificatVie($retraiteId);
        $notifications = [];
        
        if (!$certificat) {
        // Aucun certificat déposé
        $notifications[] = [
            'type' => 'certificat_manquant',
            'niveau' => 'danger',
            'titre' => 'Certificat de vie requis',
            'message' => 'Vous devez déposer votre certificat de vie annuel.',
            'couleur' => '#DC2626',
            'icone' => '🚨',
            'dismissible' => false
        ];
    } elseif ($certificat->is_expire) {
        // Certificat expiré
        $notifications[] = [
            'type' => 'certificat_expire',
            'niveau' => 'danger',
            'titre' => 'Certificat de vie expiré',
            'message' => 'Votre certificat de vie a expiré le ' . $certificat->date_expiration->format('d/m/Y') . '. Veuillez le renouveler.',
            'couleur' => '#DC2626',
            'icone' => '❌',
            'dismissible' => false
        ];
    } elseif ($certificat->jours_avant_expiration <= 30) {
        // Expire dans moins de 30 jours - ROUGE/URGENT
        $jours = $certificat->jours_avant_expiration;
        $notifications[] = [
            'type' => 'certificat_expire_bientot_urgent',
            'niveau' => 'danger',
            'titre' => 'Certificat de vie à renouveler URGENT',
            'message' => "Votre certificat de vie expire dans {$jours} jour(s) (le " . $certificat->date_expiration->format('d/m/Y') . ").",
            'couleur' => '#DC2626',
            'icone' => '🚨',
            'dismissible' => false
        ];
    } elseif ($certificat->jours_avant_expiration <= 60) {
        // Expire dans 31-60 jours - ORANGE/WARNING
        $jours = $certificat->jours_avant_expiration;
        $notifications[] = [
            'type' => 'certificat_expire_bientot',
            'niveau' => 'warning',
            'titre' => 'Certificat de vie à renouveler',
            'message' => "Votre certificat de vie expire dans {$jours} jour(s) (le " . $certificat->date_expiration->format('d/m/Y') . ").",
            'couleur' => '#F59E0B',
            'icone' => '⚠️',
            'dismissible' => false
        ];
    } else {
        // Certificat valide - VERT
        $jours = $certificat->jours_avant_expiration;
        $dateExpiration = $certificat->date_expiration->format('d/m/Y');
        $notifications[] = [
            'type' => 'certificat_valide',
            'niveau' => 'success',
            'titre' => 'Certificat de vie valide',
            'message' => "Votre certificat de vie est valide encore {$jours} jour(s), jusqu'au {$dateExpiration}.",
            'couleur' => '#10B981',
            'icone' => '✅',
            'dismissible' => true
        ];
    }
        
        return $notifications;
    }

    /**
     * Obtenir les statistiques des documents pour un retraité
     */
    public static function getStatistiques($retraiteId)
    {
        return [
            'total_documents' => self::pourRetraite($retraiteId)->actifs()->count(),
            'certificats_vie' => self::pourRetraite($retraiteId)->certificatsVie()->actifs()->count(),
            'autres_documents' => self::pourRetraite($retraiteId)->where('type_document', 'autre')->actifs()->count(),
            'documents_expires' => self::pourRetraite($retraiteId)->expires()->count(),
            'derniere_activite' => self::pourRetraite($retraiteId)->orderBy('date_depot', 'desc')->first()?->date_depot
        ];
    }

    /**
     * Valider un fichier avant upload
     */
    public static function validerFichier($file)
    {
        $errors = [];
        
        // Vérifier l'extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::$extensionsAutorisees)) {
            $errors[] = 'Type de fichier non autorisé. Extensions acceptées : ' . implode(', ', self::$extensionsAutorisees);
        }
        
        // Vérifier la taille
        if ($file->getSize() > self::$tailleMaximale) {
            $errors[] = 'Fichier trop volumineux. Taille maximale : 5 MB';
        }
        
        // Vérifier le contenu (basique)
        $mimeType = $file->getMimeType();
        $mimeTypesAutorisees = [
            'application/pdf',
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        if (!in_array($mimeType, $mimeTypesAutorisees)) {
            $errors[] = 'Type de contenu non autorisé';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Boot method pour les événements du modèle
     */
    protected static function boot()
    {
        parent::boot();
        
        // Avant suppression, supprimer le fichier physique
        static::deleting(function ($document) {
            $document->supprimerFichier();
        });
        
        // Après création d'un certificat de vie, marquer les anciens comme remplacés
        static::created(function ($document) {
            if ($document->type_document === 'certificat_vie') {
                self::pourRetraite($document->retraite_id)
                    ->certificatsVie()
                    ->where('id', '!=', $document->id)
                    ->actifs()
                    ->update(['statut' => 'remplace']);
            }
        });
    }
}