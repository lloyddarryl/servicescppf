<?php
// backend/app/Models/MessageDashboard.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MessageDashboard extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'messages_dashboard';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'admin_id',
        'destinataire_id',
        'destinataire_type',
        'titre',
        'message',
        'priorite',
        'lu',
        'lu_le',
        'expire_le',
        'date_envoi',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'lu' => 'boolean',
        'lu_le' => 'datetime',
        'expire_le' => 'datetime',
        'date_envoi' => 'datetime',
    ];

    /**
     * Relation avec l'admin qui a envoyé le message
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Obtenir le destinataire (polymorphique)
     */
    public function destinataire()
    {
        if ($this->destinataire_type === 'agent') {
            return Agent::find($this->destinataire_id);
        } else {
            return Retraite::find($this->destinataire_id);
        }
    }

    /**
     * Scope pour les messages non lus
     */
    public function scopeNonLus($query)
    {
        return $query->where('lu', false);
    }

    /**
     * Scope pour les messages d'un destinataire
     */
    public function scopePourDestinataire($query, $destinataireId, $destinataireType)
    {
        return $query->where('destinataire_id', $destinataireId)
                     ->where('destinataire_type', $destinataireType);
    }

    /**
     * Scope pour les messages non expirés
     */
    public function scopeActifs($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expire_le')
              ->orWhere('expire_le', '>', now());
        });
    }

    /**
     * Marquer le message comme lu
     */
    public function marquerCommeLu()
    {
        $this->update([
            'lu' => true,
            'lu_le' => now()
        ]);
    }

    /**
     * Vérifier si le message est encore valide
     */
    public function getIsValideAttribute()
    {
        if (!$this->expire_le) {
            return true;
        }
        return $this->expire_le->isFuture();
    }

    /**
     * Obtenir la couleur selon la priorité
     */
    public function getCouleurPrioriteAttribute()
    {
        $couleurs = [
            'normale' => '#3B82F6',
            'importante' => '#F59E0B',
            'urgente' => '#EF4444'
        ];
        
        return $couleurs[$this->priorite] ?? '#3B82F6';
    }
}