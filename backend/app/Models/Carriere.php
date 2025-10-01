<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Carriere extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id','matricule_solde', 'numero_ordre', 'date_debut', 'date_fin',
        'position', 'etablissement', 'corps', 'grade', 'indice',
        'retenue', 'nombre_mois', 'regime', 'sous_regime',
        'annuite', 'statut', 'observations', 'total_cotisations'
    ];

    protected $dates = ['date_debut', 'date_fin'];

    protected $casts = [
        'date_debut' => 'date:Y-m-d',
        'date_fin' => 'date:Y-m-d',
        'grade' => 'integer',
        'indice' => 'integer',
        'retenue' => 'decimal:2',
        'nombre_mois' => 'integer',
        'regime' => 'integer',
        'annuite' => 'decimal:4',
        'total_cotisations' => 'decimal:2'
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    // ✅ SYNCHRONISATION AUTOMATIQUE AMÉLIORÉE
    protected static function booted()
    {
        // Après création d'une carrière
        static::created(function ($carriere) {
            \Log::info('Carrière créée, synchronisation...', ['carriere_id' => $carriere->id]);
            $carriere->synchroniserVersAgent();
        });

        // Après mise à jour d'une carrière
        static::updated(function ($carriere) {
            \Log::info('Carrière mise à jour, synchronisation...', [
                'carriere_id' => $carriere->id,
                'agent_id' => $carriere->agent_id,
                'statut' => $carriere->statut
            ]);
            $carriere->synchroniserVersAgent();
        });

        // Après sauvegarde (couvre create et update)
        static::saved(function ($carriere) {
            \Log::info('Carrière sauvegardée, synchronisation...', ['carriere_id' => $carriere->id]);
            $carriere->synchroniserVersAgent();
        });
    }

    /**
     * Synchroniser les données de la carrière vers l'agent
     */
    public function synchroniserVersAgent()
    {
        try {
            \Log::info('Début synchronisation vers agent', [
                'carriere_id' => $this->id,
                'agent_id' => $this->agent_id,
                'statut' => $this->statut
            ]);

            // Vérifier que l'agent existe
            if (!$this->agent) {
                \Log::warning('Agent non trouvé pour la carrière', ['carriere_id' => $this->id]);
                return;
            }

            // Synchroniser seulement si la carrière est valide
            if ($this->statut === 'VALIDE') {
                
                // Calculer le total des cotisations pour cet agent
                $totalCotisations = self::where('agent_id', $this->agent_id)
                    ->where('statut', 'VALIDE')
                    ->sum('retenue');

                // Calculer la durée totale de service
                $dureeTotaleMois = self::where('agent_id', $this->agent_id)
                    ->where('statut', 'VALIDE')
                    ->sum('nombre_mois');

                // Données à mettre à jour
                $dataToUpdate = [
                    'grade' => $this->grade,
                    'indice' => $this->indice,
                    'retenue_mensuelle' => $this->retenue,
                    'total_cotisations' => $totalCotisations,
                    'duree_service_mois' => $dureeTotaleMois,
                    'regime' => $this->regime,
                    'sous_regime' => $this->sous_regime,
                    'corps' => $this->corps,
                    'etablissement' => $this->etablissement,
                    'derniere_cotisation_date' => $this->date_fin ?: $this->date_debut
                ];

                \Log::info('Données à synchroniser', $dataToUpdate);

                // Mettre à jour sans déclencher les events de l'agent
                $updated = $this->agent->withoutEvents(function () use ($dataToUpdate) {
                    return $this->agent->update($dataToUpdate);
                });

                if ($updated) {
                    \Log::info('Synchronisation réussie', [
                        'agent_id' => $this->agent_id,
                        'retenue_avant' => $this->agent->getOriginal('retenue_mensuelle'),
                        'retenue_apres' => $this->retenue
                    ]);
                } else {
                    \Log::error('Échec de la synchronisation', ['agent_id' => $this->agent_id]);
                }
            } else {
                \Log::info('Carrière non valide, synchronisation ignorée', [
                    'carriere_id' => $this->id,
                    'statut' => $this->statut
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la synchronisation', [
                'carriere_id' => $this->id,
                'agent_id' => $this->agent_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function getPeriodeFormateeAttribute()
    {
        $debut = $this->date_debut->format('d/m/Y');
        $fin = $this->date_fin ? $this->date_fin->format('d/m/Y') : 'En cours';
        return "{$debut} - {$fin}";
    }

    public function getRetenueFormateeAttribute()
    {
        return number_format($this->retenue, 0, ',', ' ') . ' FCFA';
    }

    public function getDureeFormateeAttribute()
    {
        return $this->nombre_mois . ' mois';
    }

    public function getSalaireBrutAttribute()
    {
        return $this->indice * 500;
    }

    public function getTauxCotisationAttribute()
    {
        return ($this->retenue / $this->salaire_brut) * 100;
    }

    public function isActive()
    {
        return $this->statut === 'VALIDE' && 
               $this->date_debut <= now() && 
               ($this->date_fin === null || $this->date_fin >= now());
    }

    public function scopeValides($query)
    {
        return $query->where('statut', 'VALIDE');
    }

    public static function getStatistiquesAgent($agentId)
    {
        $carrieres = self::where('agent_id', $agentId)->valides()->get();
        
        if ($carrieres->isEmpty()) {
            return [
                'nombre_carrieres' => 0,
                'duree_totale_mois' => 0,
                'total_cotisations' => 0,
                'cotisation_moyenne' => 0,
                'premiere_carriere' => null,
                'derniere_carriere' => null,
            ];
        }
        
        return [
            'nombre_carrieres' => $carrieres->count(),
            'duree_totale_mois' => $carrieres->sum('nombre_mois'),
            'total_cotisations' => $carrieres->first()->total_cotisations ?? 0,
            'cotisation_moyenne' => $carrieres->avg('retenue'),
            'premiere_carriere' => $carrieres->min('date_debut'),
            'derniere_carriere' => $carrieres->max('date_fin') ?: $carrieres->max('date_debut'),
        ];
    }
}