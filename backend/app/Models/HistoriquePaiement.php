<?php
// app/Models/HistoriquePaiement.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class HistoriquePaiement extends Model
{
    use HasFactory;

    protected $table = 'historique_paiements';

    protected $fillable = [
        'retraite_id',
        'numero_titre',
        'type_paiement',
        'date_paiement',
        'nom_beneficiaire',
        'prenoms_beneficiaire',
        'complement_nom',
        'regime',
        'disponibilite',
        'mode_reglement',
        'montant_net',
        'etat_paiement',
        'date_comptable',
        'poste_comptable',
        'observations',
        'reference_bancaire',
        'numero_cheque'
    ];

    protected $casts = [
        'date_paiement' => 'date',
        'date_comptable' => 'date',
        'montant_net' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relations
    public function retraite()
    {
        return $this->belongsTo(Retraite::class);
    }

    // Accesseurs
    public function getNomCompletAttribute()
    {
        $nom = $this->prenoms_beneficiaire . ' ' . $this->nom_beneficiaire;
        if ($this->complement_nom) {
            $nom .= ' ' . $this->complement_nom;
        }
        return $nom;
    }

    public function getMontantFormateAttribute()
    {
        return number_format($this->montant_net, 0, ',', ' ') . ' FCFA';
    }

    public function getDatePaiementFormattedAttribute()
    {
        return $this->date_paiement ? $this->date_paiement->format('d/m/Y') : null;
    }

    public function getMoisAnneeAttribute()
    {
        return $this->date_paiement ? $this->date_paiement->format('m/Y') : null;
    }

    public function getStatutCouleurAttribute()
    {
        $couleurs = [
            'en_attente' => '#F59E0B',
            'traite' => '#3B82F6',
            'verse' => '#10B981',
            'rejete' => '#EF4444',
            'annule' => '#6B7280'
        ];
        return $couleurs[$this->etat_paiement] ?? '#6B7280';
    }

    // Scopes
    public function scopePourRetraite($query, $retraiteId)
    {
        return $query->where('retraite_id', $retraiteId);
    }

    public function scopeParAnnee($query, $annee)
    {
        return $query->whereYear('date_paiement', $annee);
    }

    public function scopeParMois($query, $mois, $annee = null)
    {
        $query->whereMonth('date_paiement', $mois);
        if ($annee) {
            $query->whereYear('date_paiement', $annee);
        }
        return $query;
    }

    public function scopeVerses($query)
    {
        return $query->where('etat_paiement', 'verse');
    }

    public function scopeEnCours($query)
    {
        return $query->whereIn('etat_paiement', ['en_attente', 'traite']);
    }

    public function scopeOrdonnes($query)
    {
        return $query->orderBy('date_paiement', 'desc');
    }

    // Méthodes statiques simplifiées
    public static function genererNumeroTitre()
    {
        $annee = date('Y');
        $mois = date('m');
        $prefix = "PAY{$annee}{$mois}";
        
        $dernierNumero = self::where('numero_titre', 'like', $prefix . '%')
            ->orderBy('numero_titre', 'desc')
            ->first();
        
        if ($dernierNumero) {
            $sequence = intval(substr($dernierNumero->numero_titre, -6)) + 1;
        } else {
            $sequence = 1;
        }
        
        return $prefix . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }

    // Méthodes utilitaires simples
    public function estEnRetard()
    {
        if ($this->etat_paiement === 'verse') {
            return false;
        }
        return $this->date_paiement < now()->startOfMonth();
    }
}
