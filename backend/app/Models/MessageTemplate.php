<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'titre',
        'contenu',
        'categorie',
        'visible_pour',
        'is_active',
        'ordre',
        'icon',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scopes
     */
    public function scopeActifs($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePourUser($query)
    {
        return $query->whereIn('visible_pour', ['user', 'both']);
    }

    public function scopePourAdmin($query)
    {
        return $query->whereIn('visible_pour', ['admin', 'both']);
    }

    public function scopeParCategorie($query, $categorie)
    {
        return $query->where('categorie', $categorie);
    }

    public function scopeOrdonnes($query)
    {
        return $query->orderBy('ordre', 'asc')->orderBy('titre', 'asc');
    }

    /**
     * Récupérer les templates pour les questions utilisateurs
     */
    public static function getTemplatesQuestions()
    {
        return self::actifs()
            ->pourUser()
            ->parCategorie('question')
            ->ordonnes()
            ->get();
    }

    /**
     * Récupérer les templates de réponses pour admins
     */
    public static function getTemplatesReponses()
    {
        return self::actifs()
            ->pourAdmin()
            ->parCategorie('reponse_admin')
            ->ordonnes()
            ->get();
    }

    /**
     * Récupérer un template par son code
     */
    public static function getByCode($code)
    {
        return self::where('code', $code)->first();
    }
}