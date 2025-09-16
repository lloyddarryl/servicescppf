<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Agent extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public $timestamps = true;

    protected $fillable = [
        'matricule_solde', 'num_affiliation', 'nom', 'prenoms', 'poste', 'direction',
        'grade', 'date_prise_service', 'email', 'telephone', 'password', 'first_login',
        'password_changed', 'status', 'is_active', 'is_radie', 'date_radiation',
        'date_naissance', 'situation_matrimoniale', 'sexe', 'corps', 'etablissement',
        'indice', 'retenue_mensuelle', 'total_cotisations', 'derniere_cotisation_date',
        'duree_service_mois', 'regime', 'sous_regime'
    ];

    protected $hidden = ['password'];

    protected $dates = [
        'date_naissance', 'date_prise_service', 'date_radiation',
        'derniere_cotisation_date', 'email_verified_at', 'phone_verified_at'
    ];

    protected $casts = [
        'date_naissance' => 'date:Y-m-d',
        'date_prise_service' => 'date:Y-m-d',
        'date_radiation' => 'date:Y-m-d',
        'derniere_cotisation_date' => 'date:Y-m-d',
        'first_login' => 'boolean',
        'password_changed' => 'boolean',
        'is_active' => 'boolean',
        'is_radie' => 'boolean',
        'grade' => 'integer',
        'indice' => 'integer',
        'retenue_mensuelle' => 'decimal:2',
        'total_cotisations' => 'decimal:2',
        'duree_service_mois' => 'integer',
        'regime' => 'integer'
    ];

    // ✅ SYNCHRONISATION BIDIRECTIONNELLE
    protected static function booted()
    {
        // Après mise à jour d'un agent
        static::updated(function ($agent) {
            $agent->synchroniserVersCarriere();
        });

        // Après sauvegarde d'un agent
        static::saved(function ($agent) {
            $agent->synchroniserVersCarriere();
        });
    }

    /**
     * Synchroniser les données de l'agent vers la carrière actuelle
     */
    public function synchroniserVersCarriere()
    {
        try {
            \Log::info('Synchronisation Agent → Carrière', [
                'agent_id' => $this->id,
                'retenue_mensuelle' => $this->retenue_mensuelle
            ]);

            // Récupérer la carrière actuelle (la plus récente et valide)
            $carriereActuelle = $this->carrieres()
                ->where('statut', 'VALIDE')
                ->orderBy('date_debut', 'desc')
                ->first();

            if (!$carriereActuelle) {
                \Log::warning('Aucune carrière valide trouvée pour l\'agent', ['agent_id' => $this->id]);
                return;
            }

            // Vérifier quels champs ont changé
            $champsASync = [];
            
            if ($this->isDirty('retenue_mensuelle')) {
                $champsASync['retenue'] = $this->retenue_mensuelle;
            }
            
            if ($this->isDirty('grade')) {
                $champsASync['grade'] = $this->grade;
            }
            
            if ($this->isDirty('indice')) {
                $champsASync['indice'] = $this->indice;
            }

            if ($this->isDirty('corps')) {
                $champsASync['corps'] = $this->corps;
            }

            if ($this->isDirty('etablissement')) {
                $champsASync['etablissement'] = $this->etablissement;
            }

            if ($this->isDirty('regime')) {
                $champsASync['regime'] = $this->regime;
            }

            if ($this->isDirty('sous_regime')) {
                $champsASync['sous_regime'] = $this->sous_regime;
            }

            // Si il y a des changements, les appliquer
            if (!empty($champsASync)) {
                \Log::info('Champs à synchroniser vers carrière', $champsASync);
                
                // Mettre à jour sans déclencher les events de la carrière
                $carriereActuelle->withoutEvents(function () use ($champsASync, $carriereActuelle) {
                    return $carriereActuelle->update($champsASync);
                });

                \Log::info('Synchronisation Agent → Carrière réussie', [
                    'agent_id' => $this->id,
                    'carriere_id' => $carriereActuelle->id,
                    'champs_modifies' => array_keys($champsASync)
                ]);
            } else {
                \Log::debug('Aucun champ pertinent modifié, synchronisation ignorée', ['agent_id' => $this->id]);
            }

        } catch (\Exception $e) {
            \Log::error('Erreur synchronisation Agent → Carrière', [
                'agent_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    // Relations avec les carrières
    public function carrieres()
    {
        return $this->hasMany(Carriere::class)->orderBy('date_debut', 'desc');
    }

    public function carrieresValides()
    {
        return $this->hasMany(Carriere::class)
                    ->where('statut', 'VALIDE')
                    ->orderBy('date_debut', 'desc');
    }

    public function carriereActuelle()
    {
        return $this->hasOne(Carriere::class)
                    ->where('statut', 'VALIDE')
                    ->orderBy('date_debut', 'desc');
    }

    // Compatibilité avec l'ancien système
    public function cotisations()
    {
        return $this->carrieresValides();
    }

    // Attributs calculés
    public function getDureeServiceFormateeAttribute()
    {
        $dureeTotaleMois = $this->carrieresValides()->sum('nombre_mois');
        
        if (!$dureeTotaleMois) return '0 an 0 mois';
        
        $annees = floor($dureeTotaleMois / 12);
        $mois = $dureeTotaleMois % 12;
        
        $anneesText = $annees > 1 ? "{$annees} ans" : ($annees == 1 ? "1 an" : "");
        $moisText = $mois > 0 ? "{$mois} mois" : "";
        
        return trim("{$anneesText} {$moisText}") ?: "0 mois";
    }

    public function getGradeActuelAttribute()
    {
        $carriereActuelle = $this->carriereActuelle;
        return $carriereActuelle ? $carriereActuelle->grade : $this->grade;
    }

    public function getIndiceActuelAttribute()
    {
        $carriereActuelle = $this->carriereActuelle;
        return $carriereActuelle ? $carriereActuelle->indice : $this->indice;
    }

    public function getStatutFormatAttribute()
    {
        if ($this->is_radie) return 'RADIÉ';
        
        switch ($this->status) {
            case 'actif': return 'ACTIF';
            case 'suspendu': return 'SUSPENDU';
            case 'transfere': return 'TRANSFÉRÉ';
            default: return strtoupper($this->status);
        }
    }

    public function genererReleveSituation()
    {
        return [
            'agent' => [
                'nom_complet' => $this->prenoms . ' ' . $this->nom,
                'num_affiliation' => $this->num_affiliation,
                'matricule_solde' => $this->matricule_solde,
                'situation_matrimoniale' => $this->situation_matrimoniale,
                'sexe' => $this->sexe,
                'date_naissance' => $this->date_naissance?->format('d/m/Y'),
                'date_recrutement' => $this->date_prise_service?->format('d/m/Y'),
                'statut' => $this->statut_format,
                'date_radiation' => $this->date_radiation?->format('d/m/Y')
            ],
            'carrieres' => $this->carrieresValides->map(function($carriere) {
                return [
                    'periode' => $carriere->date_debut->format('d/m/Y') . 
                               ($carriere->date_fin ? ' - ' . $carriere->date_fin->format('d/m/Y') : ' - En cours'),
                    'position' => $carriere->position,
                    'etablissement' => $carriere->etablissement,
                    'corps' => $carriere->corps,
                    'grade' => $carriere->grade,
                    'indice' => $carriere->indice,
                    'retenue' => number_format($carriere->retenue, 0, ',', ' '),
                    'duree' => $carriere->nombre_mois . ' mois'
                ];
            }),
            'resume' => [
                'duree_service_formatee' => $this->duree_service_formatee,
                'total_cotisations_formatee' => number_format($this->carrieresValides->sum('retenue'), 0, ',', ' ') . ' FCFA',
                'droit_pension' => $this->carrieresValides->sum('nombre_mois') >= 180 ? 'OUI' : 'NON',
                'derniere_cotisation' => $this->derniere_cotisation_date?->format('d/m/Y')
            ]
        ];
    }

    // Méthode dans Agent.php pour synchroniser depuis la carrière actuelle
    public function synchroniserDepuisCarriereActuelle()
    {
        $carriereActuelle = $this->carriereActuelle;
        
        if ($carriereActuelle) {
            $this->update([
                'grade' => $carriereActuelle->grade,
                'indice' => $carriereActuelle->indice,
                'retenue_mensuelle' => $carriereActuelle->retenue,
                'total_cotisations' => $carriereActuelle->total_cotisations,
            ]);
        }
    }

    // Scopes
    public function scopeActifs($query)
    {
        return $query->where('is_active', true)
                    ->where('is_radie', false)
                    ->where('status', 'actif');
    }

    public function scopeRadies($query)
    {
        return $query->where('is_radie', true);
    }

    // Autres relations existantes
    public function simulations()
    {
        return $this->hasMany(SimulationPension::class);
    }

    public function conjoint()
    {
        return $this->hasOne(Conjoint::class)->where('statut', 'ACTIF');
    }

    public function enfants()
    {
        return $this->hasMany(Enfant::class)->where('actif', true);
    }
}