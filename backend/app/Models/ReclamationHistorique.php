<?php

// app/Models/ReclamationHistorique.php

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
        return Reclamation::$statutsLibelles[$this->ancien_statut] ?? $this->ancien_statut;
    }

    public function getNouveauStatutLibelleAttribute()
    {
        return Reclamation::$statutsLibelles[$this->nouveau_statut] ?? $this->nouveau_statut;
    }
}