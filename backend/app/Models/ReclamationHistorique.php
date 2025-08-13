<?php
// backend/app/Models/ReclamationHistorique.php - Version complète corrigée

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReclamationHistorique extends Model
{
    use HasFactory;

    protected $table = 'reclamation_historique';

    protected $fillable = [
        'reclamation_id',
        'ancien_statut',
        'nouveau_statut',
        'commentaire',
        'modifie_par'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relation avec la réclamation
     */
    public function reclamation()
    {
        return $this->belongsTo(Reclamation::class);
    }

    /**
     * Obtenir les libellés des statuts
     */
    public function getAncienStatutLibelleAttribute()
    {
        if ($this->ancien_statut === null) {
            return null;
        }
        return Reclamation::$statutsLibelles[$this->ancien_statut] ?? $this->ancien_statut;
    }

    public function getNouveauStatutLibelleAttribute()
    {
        return Reclamation::$statutsLibelles[$this->nouveau_statut] ?? $this->nouveau_statut;
    }
}