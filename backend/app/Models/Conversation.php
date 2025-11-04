<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_type',
        'user_id',
        'admin_id',
        'sujet',
        'statut',
        'priorite',
        'categorie',
        'numero_ticket',
        'derniere_activite',
        'resolu_le',
        'resolu_par',
        'notes_internes',
    ];

    protected $casts = [
        'derniere_activite' => 'datetime',
        'resolu_le' => 'datetime',
    ];

    protected $appends = ['unread_count'];

    /**
     * Boot du modèle
     */
    protected static function booted()
    {
        static::creating(function ($conversation) {
            // Générer un numéro de ticket unique
            if (!$conversation->numero_ticket) {
                $conversation->numero_ticket = self::genererNumeroTicket();
            }
            
            $conversation->derniere_activite = now();
        });
    }

    /**
     * Générer un numéro de ticket unique
     */
    public static function genererNumeroTicket()
    {
        do {
            $numero = 'TKT-' . strtoupper(substr(uniqid(), -8));
        } while (self::where('numero_ticket', $numero)->exists());
        
        return $numero;
    }

    /**
     * ✅ Relation avec l'utilisateur - VERSION SIMPLIFIÉE
     */
    public function user()
    {
        // ✅ On retourne directement le modèle selon le type
        if ($this->user_type === 'agent') {
            return $this->belongsTo(\App\Models\Agent::class, 'user_id');
        } elseif ($this->user_type === 'retraite') {
            return $this->belongsTo(\App\Models\Retraite::class, 'user_id');
        }
        
        // Fallback
        return null;
    }

    /**
     * ✅ Accessor pour obtenir les infos utilisateur
     */
    public function getUserInfoAttribute()
    {
        if ($this->user_type === 'agent') {
            $user = \App\Models\Agent::find($this->user_id);
            return $user ? [
                'nom_complet' => $user->prenoms . ' ' . $user->nom,
                'type' => 'Agent actif',
                'matricule' => $user->matricule ?? null,
            ] : null;
        } elseif ($this->user_type === 'retraite') {
            $user = \App\Models\Retraite::find($this->user_id);
            return $user ? [
                'nom_complet' => $user->prenoms . ' ' . $user->nom,
                'type' => 'Retraité',
                'numero_pension' => $user->numero_pension ?? null,
            ] : null;
        }
        
        return null;
    }

    /**
     * Relation avec l'admin assigné
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Relation avec l'admin qui a résolu
     */
    public function resoluteur()
    {
        return $this->belongsTo(Admin::class, 'resolu_par');
    }

    /**
     * Relation avec les messages
     */
    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Dernier message
     */
    public function dernierMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Messages non lus
     */
    public function messagesNonLus()
    {
        return $this->hasMany(Message::class)->where('is_read', false);
    }

    /**
     * Scopes
     */
    public function scopePourUtilisateur($query, $userType, $userId)
    {
        return $query->where('user_type', $userType)->where('user_id', $userId);
    }

    public function scopeOuverts($query)
    {
        return $query->where('statut', 'ouvert');
    }

    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }

    public function scopeResolus($query)
    {
        return $query->where('statut', 'resolu');
    }

    public function scopeFermes($query)
    {
        return $query->where('statut', 'ferme');
    }

    public function scopeUrgents($query)
    {
        return $query->where('priorite', 'urgente');
    }

    /**
     * Attributs calculés
     */
    public function getStatutBadgeAttribute()
    {
        $badges = [
            'ouvert' => ['text' => 'Ouvert', 'color' => 'blue'],
            'en_cours' => ['text' => 'En cours', 'color' => 'yellow'],
            'resolu' => ['text' => 'Résolu', 'color' => 'green'],
            'ferme' => ['text' => 'Fermé', 'color' => 'gray'],
        ];

        return $badges[$this->statut] ?? ['text' => $this->statut, 'color' => 'gray'];
    }

    public function getPrioriteBadgeAttribute()
    {
        $badges = [
            'basse' => ['text' => 'Basse', 'icon' => '⬇️', 'color' => 'gray'],
            'normale' => ['text' => 'Normale', 'icon' => '➡️', 'color' => 'blue'],
            'haute' => ['text' => 'Haute', 'icon' => '⬆️', 'color' => 'orange'],
            'urgente' => ['text' => 'Urgente', 'icon' => '🔥', 'color' => 'red'],
        ];

        return $badges[$this->priorite] ?? ['text' => $this->priorite, 'icon' => '➡️', 'color' => 'gray'];
    }

    public function getUnreadCountAttribute()
    {
        return $this->messagesNonLus()
            ->where(function ($query) {
                $query->where('sender_type', '!=', $this->user_type)
                      ->orWhere('sender_id', '!=', $this->user_id);
            })
            ->count();
    }

    /**
     * Méthodes utilitaires
     */
    public function marquerMessagesCommeLus($userType, $userId)
    {
        return $this->messages()
            ->where('is_read', false)
            ->where(function ($query) use ($userType, $userId) {
                $query->where('sender_type', '!=', $userType)
                      ->orWhere('sender_id', '!=', $userId);
            })
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    public function changerStatut($statut, $adminId = null)
    {
        $this->statut = $statut;
        
        if ($statut === 'resolu') {
            $this->resolu_le = now();
            $this->resolu_par = $adminId;
        }
        
        $this->save();
        
        // Créer un message système
        Message::create([
            'conversation_id' => $this->id,
            'sender_type' => 'system',
            'sender_id' => 0,
            'message' => "Statut changé en : {$statut}",
            'is_system_message' => true,
        ]);
    }
}