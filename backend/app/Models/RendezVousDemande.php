<?php
// app/Models/RendezVousDemande.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RendezVousDemande extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_type',
        'user_email',
        'user_telephone',
        'user_nom',
        'user_prenoms',
        'numero_demande',
        'date_demandee',
        'heure_demandee',
        'motif',
        'motif_autre',
        'commentaires',
        'statut',
        'reponse_admin',
        'date_reponse',
        'date_rdv_confirme',
        'lieu_rdv',
        'email_admin_envoye',
        'email_user_reponse_envoye',
        'date_soumission'
    ];

    protected $casts = [
        'date_demandee' => 'date',
        'heure_demandee' => 'string', // Changé de 'datetime:H:i' à 'string'
        'date_soumission' => 'datetime',
        'date_reponse' => 'datetime',
        'date_rdv_confirme' => 'datetime',
        'email_admin_envoye' => 'boolean',
        'email_user_reponse_envoye' => 'boolean'
    ];

    // Motifs disponibles avec libellés
    public static $motifs = [
        'probleme_cotisations' => [
            'nom' => 'Problème de cotisations',
            'description' => 'Questions relatives aux cotisations, prélèvements, arriérés',
            'icon' => '💰'
        ],
        'questions_pension' => [
            'nom' => 'Questions sur la pension',
            'description' => 'Informations sur les droits à pension, calculs, versements',
            'icon' => '🏦'
        ],
        'mise_a_jour_dossier' => [
            'nom' => 'Mise à jour de dossier',
            'description' => 'Modification d\'informations personnelles, situation familiale',
            'icon' => '📂'
        ],
        'reclamation_complexe' => [
            'nom' => 'Réclamation complexe',
            'description' => 'Dossier nécessitant un entretien approfondi',
            'icon' => '🔍'
        ],
        'autre' => [
            'nom' => 'Autre motif',
            'description' => 'Autre demande nécessitant un rendez-vous',
            'icon' => '📋'
        ]
    ];

    // Statuts avec libellés et couleurs
    public static $statuts = [
        'en_attente' => [
            'nom' => 'En attente',
            'description' => 'Demande en cours d\'examen',
            'couleur' => '#F59E0B',
            'icon' => '⏳'
        ],
        'accepte' => [
            'nom' => 'Accepté',
            'description' => 'Rendez-vous confirmé',
            'couleur' => '#10B981',
            'icon' => '✅'
        ],
        'refuse' => [
            'nom' => 'Refusé',
            'description' => 'Demande refusée',
            'couleur' => '#EF4444',
            'icon' => '❌'
        ],
        'reporte' => [
            'nom' => 'Reporté',
            'description' => 'Rendez-vous reporté à une autre date',
            'couleur' => '#8B5CF6',
            'icon' => '📅'
        ],
        'annule' => [
            'nom' => 'Annulé',
            'description' => 'Rendez-vous annulé',
            'couleur' => '#6B7280',
            'icon' => '🚫'
        ]
    ];

    /**
     * Générer un numéro de demande unique
     */
    public static function genererNumeroDemande()
    {
        $prefix = 'RDV-' . date('Ym') . '-';
        $tentatives = 0;
        $maxTentatives = 50;
        
        do {
            $tentatives++;
            
            $numero = $prefix . str_pad(
                (self::where('numero_demande', 'LIKE', $prefix . '%')->count() + 1 + $tentatives), 
                4, 
                '0', 
                STR_PAD_LEFT
            ) . substr(uniqid(), -2);
            
            Log::info('🔢 Génération numéro RDV tentative ' . $tentatives . ': ' . $numero);
            
            $existe = self::where('numero_demande', $numero)->exists();
            
            if (!$existe) {
                Log::info('✅ Numéro RDV unique généré: ' . $numero);
                return $numero;
            }
            
            usleep(1000); // 1ms
            
        } while ($tentatives < $maxTentatives);
        
        $numeroFallback = $prefix . time() . '-' . substr(uniqid(), -4);
        Log::warning('🆘 Utilisation numéro RDV fallback: ' . $numeroFallback);
        
        return $numeroFallback;
    }

    /**
     * Obtenir les informations du motif
     */
    public function getMotifInfoAttribute()
    {
        return self::$motifs[$this->motif] ?? null;
    }

    /**
     * Obtenir les informations du statut
     */
    public function getStatutInfoAttribute()
    {
        return self::$statuts[$this->statut] ?? null;
    }

    /**
     * Obtenir le libellé du motif complet
     */
    public function getMotifCompletAttribute()
    {
        $motifInfo = $this->motif_info;
        if ($this->motif === 'autre' && $this->motif_autre) {
            return $motifInfo['nom'] . ': ' . $this->motif_autre;
        }
        return $motifInfo['nom'] ?? $this->motif;
    }

    /**
 * Obtenir la date et heure formatées
 */
public function getDateHeureFormatteeAttribute()
{
    // Traiter heure_demandee comme une chaîne HH:MM
    $heure = is_string($this->heure_demandee) 
        ? $this->heure_demandee 
        : Carbon::parse($this->heure_demandee)->format('H:i');
        
    return $this->date_demandee->format('d/m/Y') . ' à ' . substr($heure, 0, 5);
}

    /**
     * Obtenir le temps écoulé depuis la soumission
     */
    public function getTempsEcouleAttribute()
    {
        return $this->date_soumission->diffForHumans();
    }

    /**
     * Vérifier si la demande peut être modifiée/annulée
     */
    public function getPeutModifierAttribute()
    {
        return in_array($this->statut, ['en_attente']) && 
               $this->date_soumission->diffInHours(now()) < 24;
    }

    /**
     * Vérifier si la date demandée est dans le futur
     */
    public function getEstFutureAttribute()
    {
        $dateComplete = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $this->date_demandee->format('Y-m-d') . ' ' . 
            Carbon::createFromFormat('H:i:s', $this->heure_demandee)->format('H:i:s')
        );
        
        return $dateComplete->isFuture();
    }

    /**
     * Scope pour filtrer par utilisateur
     */
    public function scopePourUtilisateur($query, $userId, $userType)
    {
        return $query->where('user_id', $userId)
                     ->where('user_type', $userType);
    }

    /**
     * Scope pour les demandes en attente
     */
    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    /**
     * Scope pour les rendez-vous confirmés
     */
    public function scopeConfirmes($query)
    {
        return $query->where('statut', 'accepte');
    }

    /**
     * Changer le statut avec historique
     */
    public function changerStatut($nouveauStatut, $reponseAdmin = null, $dateRdvConfirme = null, $lieuRdv = null)
    {
        $this->statut = $nouveauStatut;
        $this->reponse_admin = $reponseAdmin;
        $this->date_reponse = now();
        
        if ($nouveauStatut === 'accepte') {
            $this->date_rdv_confirme = $dateRdvConfirme;
            $this->lieu_rdv = $lieuRdv;
        }
        
        $this->save();
        
        Log::info('📅 Statut RDV changé:', [
            'rdv_id' => $this->id,
            'numero' => $this->numero_demande,
            'nouveau_statut' => $nouveauStatut,
            'reponse_admin' => $reponseAdmin
        ]);
        
        return $this;
    }

    // Dans backend/app/Models/RendezVousDemande.php
/**
 * Obtenir les créneaux disponibles pour une date
 */
public static function getCreneauxDisponibles($date)
{
    $creneaux = [];
    
    // Vérifier que c'est un jour ouvrable
    $dateCarbon = Carbon::parse($date);
    if ($dateCarbon->isWeekend()) {
        return [];
    }
    
    // Vérifier que c'est au moins 48h à l'avance
    if ($dateCarbon->diffInHours(now()) < 48) {
        return [];
    }
    
    // Générer les créneaux de 9h à 16h (par tranches de 30 minutes)
    for ($heure = 9; $heure < 16; $heure++) {
        for ($minute = 0; $minute < 60; $minute += 30) {
            $heureFormatee = sprintf('%02d:%02d', $heure, $minute);
            
            // Vérifier si ce créneau est disponible (pas déjà pris)
            $dejaReserve = self::where('date_demandee', $date)
                              ->where('heure_demandee', $heureFormatee . ':00')
                              ->whereIn('statut', ['en_attente', 'accepte'])
                              ->exists();
            
            if (!$dejaReserve) {
                $creneaux[] = $heureFormatee;
            }
        }
    }
    
    return $creneaux;
}

/**
 * Validation des créneaux disponibles
 */
public static function estCreneauDisponible($date, $heure)
{
    // Vérifier que c'est un jour ouvrable (lundi à vendredi)
    $dateCarbon = Carbon::parse($date);
    if ($dateCarbon->isWeekend()) {
        return false;
    }
    
    // Vérifier que l'heure est dans les créneaux (9h-16h)
    $heureCarbon = Carbon::createFromFormat('H:i', $heure);
    if ($heureCarbon->hour < 9 || $heureCarbon->hour >= 16) {
        return false;
    }
    
    // Vérifier que c'est dans le futur avec au moins 48h de préavis
    $dateTimeComplete = Carbon::createFromFormat(
        'Y-m-d H:i',
        $dateCarbon->format('Y-m-d') . ' ' . $heure
    );
    
    if ($dateTimeComplete->diffInHours(now()) < 48) {
        return false;
    }
    
    // Vérifier que le créneau n'est pas déjà réservé
    $dejaReserve = self::where('date_demandee', $date)
                      ->where('heure_demandee', $heure . ':00')
                      ->whereIn('statut', ['en_attente', 'accepte'])
                      ->exists();
    
    return !$dejaReserve;
    }
}