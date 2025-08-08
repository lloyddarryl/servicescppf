<?php
// app/Models/Reclamation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
        'date_derniere_mise_a_jour',
        'date_resolution',
        'commentaires_admin',
        'traite_par'
    ];

    protected $casts = [
        'documents' => 'array',
        'necessite_document' => 'boolean',
        'date_soumission' => 'datetime',
        'date_derniere_mise_a_jour' => 'datetime',
        'date_resolution' => 'datetime'
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
     * Générer un numéro de réclamation unique
     */
    public static function genererNumeroReclamation()
    {
        $prefix = 'REC-' . date('Ym') . '-';
        $lastNumber = self::where('numero_reclamation', 'LIKE', $prefix . '%')
                         ->count() + 1;
        
        return $prefix . str_pad($lastNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relation avec l'historique
     */
    public function historique()
    {
        return $this->hasMany(ReclamationHistorique::class);
    }

    /**
     * Obtenir l'utilisateur (Agent ou Retraite)
     */
    public function utilisateur()
    {
        if ($this->user_type === 'agent') {
            return $this->belongsTo(Agent::class, 'user_id');
        }
        return $this->belongsTo(Retraite::class, 'user_id');
    }

    /**
     * Vérifier si la réclamation nécessite un document
     */
    public function getNecessiteDocumentAttribute()
    {
        $typeConfig = self::$typesReclamations[$this->type_reclamation] ?? null;
        return $typeConfig ? $typeConfig['necessite_document'] : false;
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
     * Mettre à jour le statut avec historique
     */
    public function changerStatut($nouveauStatut, $commentaire = null, $modifiePar = null)
    {
        $ancienStatut = $this->statut;
        
        // Créer l'entrée d'historique
        ReclamationHistorique::create([
            'reclamation_id' => $this->id,
            'ancien_statut' => $ancienStatut,
            'nouveau_statut' => $nouveauStatut,
            'commentaire' => $commentaire,
            'modifie_par' => $modifiePar
        ]);

        // Mettre à jour la réclamation
        $this->statut = $nouveauStatut;
        $this->date_derniere_mise_a_jour = now();
        
        if (in_array($nouveauStatut, ['resolu', 'ferme', 'rejete'])) {
            $this->date_resolution = now();
        }

        $this->save();
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
     * Scope pour un utilisateur
     */
    public function scopePourUtilisateur($query, $userId, $userType)
    {
        return $query->where('user_id', $userId)->where('user_type', $userType);
    }
}