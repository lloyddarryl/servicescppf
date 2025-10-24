<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'user_type',
        'type',
        'titre',
        'message',
        'lien',
        'lu'
    ];

    protected $casts = [
        'lu' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Obtenir le temps écoulé depuis la création
     */
    public function getTempsEcouleAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Scope pour les notifications non lues
     */
    public function scopeNonLues($query)
    {
        return $query->where('lu', false);
    }

    /**
     * Scope pour un utilisateur spécifique
     */
    public function scopePourUtilisateur($query, $userId, $userType)
    {
        return $query->where('user_id', $userId)
                     ->where('user_type', $userType);
    }
}