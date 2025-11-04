<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_type',
        'sender_id',
        'message',
        'attachments',
        'is_read',
        'read_at',
        'is_template',
        'template_type',
        'is_system_message',
        'ip_address',
        'is_edited',        // ✅ AJOUTER
        'edited_at',        // ✅ AJOUTER
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_read' => 'boolean',
        'is_template' => 'boolean',
        'is_system_message' => 'boolean',
        'read_at' => 'datetime',
        'is_edited' => 'boolean',      // ✅ AJOUTER
        'edited_at' => 'datetime',  
    ];

    protected $appends = ['sender_name', 'formatted_time'];

    /**
     * Boot du modèle
     */
    protected static function booted()
    {
        static::created(function ($message) {
            // Mettre à jour derniere_activite de la conversation
            $message->conversation->touch('derniere_activite');
        });
    }

    /**
     * Relation avec la conversation
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * ✅ PAS DE RELATION sender() - On utilise un accessor à la place
     */

    /**
     * Scopes
     */
    public function scopeNonLus($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeLus($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeRecents($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopePourConversation($query, $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    public function scopeDeAdmin($query)
    {
        return $query->where('sender_type', 'admin');
    }

    public function scopeDeUtilisateur($query)
    {
        return $query->whereIn('sender_type', ['agent', 'retraite']);
    }

    /**
     * ✅ Attribut calculé pour le nom de l'expéditeur - VERSION SIMPLIFIÉE
     */
    public function getSenderNameAttribute()
    {
        if ($this->is_system_message) {
            return 'Système';
        }

        if ($this->sender_type === 'admin') {
            $sender = \App\Models\Admin::find($this->sender_id);
            return $sender ? $sender->prenoms . ' ' . $sender->nom : 'Admin';
        } 
        
        if ($this->sender_type === 'agent') {
            $sender = \App\Models\Agent::find($this->sender_id);
            return $sender ? $sender->prenoms . ' ' . $sender->nom : 'Utilisateur';
        } 
        
        if ($this->sender_type === 'retraite') {
            $sender = \App\Models\Retraite::find($this->sender_id);
            return $sender ? $sender->prenoms . ' ' . $sender->nom : 'Utilisateur';
        }
        
        return 'Inconnu';
    }

    public function getFormattedTimeAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * ✅ Méthodes pour les pièces jointes - VERSION CORRIGÉE v2.2
     */
    public function ajouterPieceJointe($file)
    {
        $filename = uniqid() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('messages/attachments', $filename, 'public');
        
        $attachments = $this->attachments ?? [];
        
        // ✅ CORRECTION: Storage::url() retourne déjà une URL complète si APP_URL est défini
        // On doit juste utiliser le path relatif
        
        // Construire l'URL manuellement pour éviter les doublons
        $appUrl = config('app.url');
        
        // S'assurer que APP_URL a le protocole
        if (!str_starts_with($appUrl, 'http://') && !str_starts_with($appUrl, 'https://')) {
            $appUrl = 'http://' . $appUrl;
        }
        
        // Enlever le slash final
        $appUrl = rtrim($appUrl, '/');
        
        // Construire l'URL avec le path SANS utiliser Storage::url()
        $fullUrl = $appUrl . '/storage/' . $path;
        
        $attachments[] = [
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
            'url' => $fullUrl,
        ];
        
        $this->attachments = $attachments;
        $this->save();
        
        return $path;
    }

    public function getAttachmentsFormatted()
    {
        if (!$this->attachments || count($this->attachments) === 0) {
            return [];
        }

        return collect($this->attachments)->map(function ($attachment) {
            // ✅ Régénérer l'URL si elle n'a pas le bon format
            $url = $attachment['url'] ?? null;
            
            // Vérifier si l'URL est correcte
            $needsRegeneration = false;
            if (!$url) {
                $needsRegeneration = true;
            } else {
                // Vérifier si l'URL commence par http:// ou https://
                if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                    $needsRegeneration = true;
                }
                // Vérifier si l'URL est dupliquée
                if (substr_count($url, 'http://') > 1 || substr_count($url, 'https://') > 1) {
                    $needsRegeneration = true;
                }
            }
            
            // Régénérer l'URL si nécessaire
            if ($needsRegeneration) {
                $appUrl = config('app.url');
                if (!str_starts_with($appUrl, 'http://') && !str_starts_with($appUrl, 'https://')) {
                    $appUrl = 'http://' . $appUrl;
                }
                $appUrl = rtrim($appUrl, '/');
                
                // Utiliser directement le path sans Storage::url()
                $url = $appUrl . '/storage/' . $attachment['path'];
            }
            
            return [
                'name' => $attachment['name'],
                'url' => $url,
                'size' => $this->formatFileSize($attachment['size']),
                'icon' => $this->getFileIcon($attachment['mime']),
            ];
        })->toArray();
    }

    private function formatFileSize($bytes)
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / (1024 * 1024), 1) . ' MB';
    }

    private function getFileIcon($mime)
    {
        if (str_contains($mime, 'pdf')) return '📄';
        if (str_contains($mime, 'image')) return '🖼️';
        if (str_contains($mime, 'word') || str_contains($mime, 'document')) return '📝';
        if (str_contains($mime, 'sheet') || str_contains($mime, 'excel')) return '📊';
        if (str_contains($mime, 'zip') || str_contains($mime, 'rar')) return '📦';
        return '📎';
    }

    /**
     * Filtrer le contenu des messages (mots interdits, etc.)
     */
    public static function filtrerContenu($message)
    {
        // Liste de mots à filtrer (exemple)
        $motsInterdits = ['spam', 'viagra', 'casino'];
        
        foreach ($motsInterdits as $mot) {
            $message = str_ireplace($mot, str_repeat('*', strlen($mot)), $message);
        }
        
        return $message;
    }

    /**
     * Marquer comme lu
     */
    public function marquerCommeLu()
    {
        if (!$this->is_read) {
            $this->is_read = true;
            $this->read_at = now();
            $this->save();
        }
    }
}