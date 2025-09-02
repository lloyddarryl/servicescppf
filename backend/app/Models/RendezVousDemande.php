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
    'date_soumission',
    // ✅ NOUVELLES COLONNES POUR LES RAPPELS
    'rappel_j1_envoye',
    'date_rappel_j1',
    'rappel_j7_envoye',
    'date_rappel_j7',
    'notification_dashboard_lue'
];

    protected $casts = [
        'date_demandee' => 'date',
        'heure_demandee' => 'string', // Changé de 'datetime:H:i' à 'string'
        'date_soumission' => 'datetime',
        'date_reponse' => 'datetime',
        'date_rdv_confirme' => 'datetime',
        'email_admin_envoye' => 'boolean',
        'email_user_reponse_envoye' => 'boolean',
        // ✅ NOUVEAUX CASTS
        'rappel_j1_envoye' => 'boolean',
        'date_rappel_j1' => 'datetime',
        'rappel_j7_envoye' => 'boolean',
        'date_rappel_j7' => 'datetime',
        'notification_dashboard_lue' => 'boolean'
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
 * ✅ SOLUTION FINALE : Obtenir la date et heure formatées (ROBUSTE)
 */
public function getDateHeureFormatteeAttribute()
{
    try {
        // Log de débogage détaillé
        Log::info('🔍 [ACCESSOR DEBUG] Données brutes:', [
            'rdv_id' => $this->id ?? 'null',
            'date_demandee_raw' => $this->getRawOriginal('date_demandee'),
            'heure_demandee_raw' => $this->getRawOriginal('heure_demandee'),
            'date_demandee_cast' => $this->date_demandee,
            'heure_demandee_cast' => $this->heure_demandee,
            'date_type' => gettype($this->date_demandee),
            'heure_type' => gettype($this->heure_demandee)
        ]);

        // Récupérer les valeurs brutes directement de la base
        $dateRaw = $this->getRawOriginal('date_demandee');
        $heureRaw = $this->getRawOriginal('heure_demandee');

        if (!$dateRaw || !$heureRaw) {
            Log::warning('❌ [ACCESSOR] Données manquantes:', [
                'date_raw' => $dateRaw,
                'heure_raw' => $heureRaw
            ]);
            return 'Date non disponible';
        }

        // Formatage de la date (YYYY-MM-DD vers DD/MM/YYYY)
        $dateFormatee = 'Date invalide';
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $dateRaw, $matches)) {
            $dateFormatee = $matches[3] . '/' . $matches[2] . '/' . $matches[1];
        } else {
            // Fallback avec Carbon si le format est différent
            try {
                $dateCarbon = Carbon::parse($dateRaw);
                $dateFormatee = $dateCarbon->format('d/m/Y');
            } catch (\Exception $carbonError) {
                Log::error('💥 [ACCESSOR] Erreur parsing date Carbon:', [
                    'date_raw' => $dateRaw,
                    'carbon_error' => $carbonError->getMessage()
                ]);
                return 'Format de date invalide';
            }
        }

        // Formatage de l'heure (garder HH:MM)
        $heureFormatee = substr((string) $heureRaw, 0, 5);

        $resultat = $dateFormatee . ' à ' . $heureFormatee;

        Log::info('✅ [ACCESSOR] Formatage réussi:', [
            'date_raw' => $dateRaw,
            'heure_raw' => $heureRaw,
            'date_formatee' => $dateFormatee,
            'heure_formatee' => $heureFormatee,
            'resultat' => $resultat
        ]);

        return $resultat;

    } catch (\Exception $e) {
        Log::error('💥 [ACCESSOR] Exception critique:', [
            'rdv_id' => $this->id ?? 'null',
            'error_message' => $e->getMessage(),
            'error_line' => $e->getLine(),
            'error_file' => basename($e->getFile()),
            'stack_trace' => $e->getTraceAsString(),
            'all_attributes' => $this->getAttributes()
        ]);

        // Retourner quelque chose de plus descriptif en cas d'erreur
        return 'Erreur: ' . $e->getMessage();
    }
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

    /**
     * ✅ CORRECTION FINALE : Méthode statique pour obtenir créneaux (utilisée par le modèle)
     */
    public static function getCreneauxDisponibles($date)
    {
        try {
            // Vérifier jour ouvrable
            $dateCarbon = Carbon::parse($date);
            if ($dateCarbon->isWeekend()) {
                return [];
            }

            // Vérifier délai 48h
            $maintenant = Carbon::now();
            $heuresRestantes = $maintenant->diffInHours($dateCarbon->startOfDay(), false);
            if ($heuresRestantes < 48) {
                return [];
            }

            // Générer créneaux
            $creneaux = [];
            for ($heure = 9; $heure < 16; $heure++) {
                for ($minute = 0; $minute < 60; $minute += 30) {
                    $heureFormatee = sprintf('%02d:%02d', $heure, $minute);

                    if (self::estCreneauDisponible($date, $heureFormatee)) {
                        $creneaux[] = $heureFormatee;
                    }
                }
            }

            return $creneaux;

        } catch (\Exception $e) {
            Log::error('💥 [MODEL] Erreur récupération créneaux:', [
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * ✅ CORRECTION FINALE : Validation des créneaux disponibles
     */
    public static function estCreneauDisponible($date, $heure)
    {
        try {
            Log::info('🔍 [MODEL] Vérification créneau:', [
                'date' => $date,
                'heure' => $heure
            ]);

            // Vérifier que c'est un jour ouvrable
            $dateCarbon = Carbon::parse($date);
            if ($dateCarbon->isWeekend()) {
                Log::info('❌ [MODEL] Weekend détecté');
                return false;
            }

            // Vérifier que l'heure est dans les créneaux (9h-15h30)
            $heureNum = (int) explode(':', $heure)[0];
            $minuteNum = (int) explode(':', $heure)[1];

            if ($heureNum < 9 || $heureNum >= 16) {
                Log::info('❌ [MODEL] Heure hors créneaux:', [
                    'heure_num' => $heureNum,
                    'minute_num' => $minuteNum
                ]);
                return false;
            }

            // Pour 15h, seule 15h30 est impossible (on s'arrête à 15h30)
            if ($heureNum == 15 && $minuteNum > 30) {
                Log::info('❌ [MODEL] Après 15h30');
                return false;
            }

            // Vérifier délai de 48h
            $maintenant = Carbon::now();
            $dateHeureComplete = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $heure);

            $heuresRestantes = $maintenant->diffInHours($dateHeureComplete, false);
            if ($heuresRestantes < 48) {
                Log::info('❌ [MODEL] Délai insuffisant:', [
                    'heures_restantes' => $heuresRestantes
                ]);
                return false;
            }

            // Normaliser l'heure pour la recherche en BDD
            $heureNormalisee = substr($heure, 0, 5); // HH:MM

            // Vérifier si déjà réservé
            $dejaReserve = self::where('date_demandee', $date)
                ->where(function ($query) use ($heureNormalisee, $heure) {
                    $query->where('heure_demandee', $heureNormalisee)
                        ->orWhere('heure_demandee', $heure)
                        ->orWhere('heure_demandee', $heure . ':00');
                })
                ->whereIn('statut', ['en_attente', 'accepte'])
                ->exists();

            Log::info('🔍 [MODEL] Résultat vérification:', [
                'deja_reserve' => $dejaReserve,
                'disponible' => !$dejaReserve
            ]);

            return !$dejaReserve;

        } catch (\Exception $e) {
            Log::error('💥 [MODEL] Erreur vérification créneau:', [
                'date' => $date,
                'heure' => $heure,
                'error' => $e->getMessage()
            ]);
            return false;
        }
        
    }

    // ✅ NOUVELLES MÉTHODES UTILITAIRES

/**
 * Scope pour les RDV nécessitant un rappel J-1
 */
public function scopeNeedingJ1Reminder($query)
{
    return $query->where('statut', 'accepte')
                 ->whereNotNull('date_rdv_confirme')
                 ->where('date_rdv_confirme', '>', now())
                 ->where(function($q) {
                     $q->whereNull('rappel_j1_envoye')
                       ->orWhere('rappel_j1_envoye', false);
                 });
}

/**
 * Scope pour les RDV de demain
 */
public function scopeForTomorrow($query)
{
    $tomorrow = now()->addDay()->startOfDay();
    $endOfTomorrow = now()->addDay()->endOfDay();
    
    return $query->whereBetween('date_rdv_confirme', [$tomorrow, $endOfTomorrow]);
}

/**
 * Vérifier si le RDV nécessite une notification urgente
 */
public function getIsUrgentAttribute()
{
    if (!$this->date_rdv_confirme || $this->statut !== 'accepte') {
        return false;
    }
    
    $heuresRestantes = now()->diffInHours($this->date_rdv_confirme, false);
    return $heuresRestantes <= 24 && $heuresRestantes > 0;
}

/**
 * Obtenir le niveau de priorité pour les notifications
 */
public function getPrioriteNotificationAttribute()
{
    if (!$this->date_rdv_confirme || $this->statut !== 'accepte') {
        return 'normale';
    }
    
    $heuresRestantes = now()->diffInHours($this->date_rdv_confirme, false);
    
    if ($heuresRestantes <= 2) {
        return 'critique';
    } elseif ($heuresRestantes <= 24) {
        return 'urgent';
    } elseif ($heuresRestantes <= 72) {
        return 'haute';
    } else {
        return 'normale';
    }
}

/**
 * Marquer la notification comme lue
 */
public function markNotificationAsRead()
{
    $this->update(['notification_dashboard_lue' => true]);
    return $this;
}
}
