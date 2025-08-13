<?php

namespace App\Models;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\ReclamationHistorique; 

class Reclamation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_type',
        'user_email',
        'user_telephone',
        'numero_reclamation',
        'type_reclamation',
        'sujet_personnalise',
        'description',
        'priorite',
        'statut',
        'necessite_document',
        'documents',
        'date_soumission',
    ];

    protected $casts = [
        'documents' => 'array',
        'necessite_document' => 'boolean',
        'date_soumission' => 'datetime',
    ];

    // Types de réclamations disponibles
    public static $typesReclamations = [
        'cotisation' => [
            'nom' => 'Problème de cotisation',
            'necessite_document' => true,
            'description' => 'Erreur de prélèvement, cotisation manquante, etc.'
        ],
        'prestation' => [
            'nom' => 'Problème de prestation',
            'necessite_document' => true,
            'description' => 'Retard de versement, montant incorrect, etc.'
        ],
        'pension' => [
            'nom' => 'Problème de pension',
            'necessite_document' => true,
            'description' => 'Pension non versée, montant incorrect, etc.'
        ],
        'attestation' => [
            'nom' => 'Problème d\'attestation',
            'necessite_document' => false,
            'description' => 'Demande non traitée, erreur dans le document, etc.'
        ],
        'compte' => [
            'nom' => 'Problème de compte',
            'necessite_document' => false,
            'description' => 'Accès bloqué, informations incorrectes, etc.'
        ],
        'service_client' => [
            'nom' => 'Service client',
            'necessite_document' => false,
            'description' => 'Insatisfaction, délai de réponse, etc.'
        ],
        'technique' => [
            'nom' => 'Problème technique',
            'necessite_document' => false,
            'description' => 'Bug de l\'application, erreur système, etc.'
        ],
        'autre' => [
            'nom' => 'Autre',
            'necessite_document' => false,
            'description' => 'Autre type de réclamation'
        ]
    ];

    // Statuts avec leurs libellés
    public static $statutsLibelles = [
        'en_attente' => 'En attente',
        'en_cours' => 'En cours de traitement',
        'en_revision' => 'En révision',
        'resolu' => 'Résolu',
        'ferme' => 'Fermé',
        'rejete' => 'Rejeté'
    ];

    // Priorités avec couleurs
    public static $priorites = [
        'basse' => ['nom' => 'Basse', 'couleur' => '#10B981'],
        'normale' => ['nom' => 'Normale', 'couleur' => '#3B82F6'],
        'haute' => ['nom' => 'Haute', 'couleur' => '#F59E0B'],
        'urgente' => ['nom' => 'Urgente', 'couleur' => '#EF4444']
    ];

    /**
     * ✅ CORRECTION : Générer un numéro de réclamation unique
     */
    public static function genererNumeroReclamation()
    {
        $prefix = 'REC-' . date('Ym') . '-';
        $tentatives = 0;
        $maxTentatives = 50;
        
        do {
            $tentatives++;
            
            // ✅ Utiliser microsecondes + random pour éviter les doublons
            $numero = $prefix . str_pad(
                (self::where('numero_reclamation', 'LIKE', $prefix . '%')->count() + 1 + $tentatives), 
                4, 
                '0', 
                STR_PAD_LEFT
            ) . substr(uniqid(), -2);
            
            Log::info('🔢 Génération numéro réclamation tentative ' . $tentatives . ': ' . $numero);
            
            // Vérifier l'unicité
            $existe = self::where('numero_reclamation', $numero)->exists();
            
            if (!$existe) {
                Log::info('✅ Numéro unique généré: ' . $numero);
                return $numero;
            }
            
            Log::warning('⚠️ Numéro déjà existant: ' . $numero);
            
            // Petit délai pour éviter les collisions
            usleep(1000); // 1ms
            
        } while ($tentatives < $maxTentatives);
        
        // Fallback ultime avec timestamp
        $numeroFallback = $prefix . time() . '-' . substr(uniqid(), -4);
        Log::warning('🆘 Utilisation numéro fallback: ' . $numeroFallback);
        
        return $numeroFallback;
    }

    /**
     * Obtenir le libellé du statut
     */
    public function getStatutLibelleAttribute()
    {
        return self::$statutsLibelles[$this->statut] ?? $this->statut;
    }
    
    /**
     * Obtenir les informations de priorité
     */
    public function getPrioriteInfoAttribute()
    {
        return self::$priorites[$this->priorite] ?? self::$priorites['normale'];
    }

    /**
     * Obtenir le type de réclamation avec détails
     */
    public function getTypeReclamationInfoAttribute()
    {
        return self::$typesReclamations[$this->type_reclamation] ?? null;
    }

    /**
     * Calculer le temps écoulé depuis la soumission
     */
    public function getTempsEcouleAttribute()
    {
        return $this->date_soumission->diffForHumans();
    }

    /**
     * Vérifier si la réclamation est en cours
     */
    public function getEnCoursAttribute()
    {
        return in_array($this->statut, ['en_attente', 'en_cours', 'en_revision']);
    }

    /**
     * Obtenir la couleur du statut
     */
    public function getCouleurStatutAttribute()
    {
        $couleurs = [
            'en_attente' => '#F59E0B',
            'en_cours' => '#3B82F6',
            'en_revision' => '#8B5CF6',
            'resolu' => '#10B981',
            'ferme' => '#6B7280',
            'rejete' => '#EF4444'
        ];

        return $couleurs[$this->statut] ?? '#6B7280';
    }

    /**
     * Scope pour filtrer par statut
     */
    public function scopeParStatut($query, $statut)
    {
        return $query->where('statut', $statut);
    }

    /**
     * Scope pour les réclamations en cours
     */
    public function scopeEnCours($query)
    {
        return $query->whereIn('statut', ['en_attente', 'en_cours', 'en_revision']);
    }

    /**
     * Scope pour un utilisateur (CORRIGÉ)
     */
    public function scopePourUtilisateur($query, $userId, $userType)
    {
        return $query->where('user_id', $userId)
                     ->where('user_type', $userType);
    }

    /**
     * Relation avec l'historique des statuts
     */
    public function historique()
    {
        return $this->hasMany(ReclamationHistorique::class);
    }

    /**
     * ✅ NOUVELLE MÉTHODE : Changer le statut avec historique
     */
    public function changerStatut($nouveauStatut, $commentaire = null, $modifiePar = 'Système')
    {
        $ancienStatut = $this->statut;
        
        // Mettre à jour le statut
        $this->statut = $nouveauStatut;
        $this->save();
        
        // Ajouter à l'historique
        $this->historique()->create([
            'ancien_statut' => $ancienStatut,
            'nouveau_statut' => $nouveauStatut,
            'commentaire' => $commentaire,
            'modifie_par' => $modifiePar
        ]);
        
        Log::info('📝 Statut réclamation changé:', [
            'reclamation_id' => $this->id,
            'numero' => $this->numero_reclamation,
            'ancien_statut' => $ancienStatut,
            'nouveau_statut' => $nouveauStatut,
            'modifie_par' => $modifiePar
        ]);
        
        return $this;
    }
}