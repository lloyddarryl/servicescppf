<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use App\Models\RendezVousDemande;
use App\Models\Reclamation;
use App\Models\DocumentRetraite;
use App\Models\MessageDashboard;
use App\Models\LogActivite;


class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nom',
        'prenoms',
        'email',
        'password',
        'telephone',
        'role',
        'niveau_acces',
        'permissions',
        'is_active',
        'last_login_at',
        'statut',
        'derniere_connexion',
        'created_by'
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'permissions' => 'array',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'derniere_connexion' => 'datetime',
    ];

    /**
     * Relations
     */
    public function rendezVousTraites()
    {
        return $this->hasMany(RendezVousDemande::class, 'admin_id');
    }

    public function reclamationsTraitees()
    {
        return $this->hasMany(Reclamation::class, 'admin_id');
    }

    public function documentsTraites()
    {
        return $this->hasMany(DocumentRetraite::class, 'admin_id');
    }

    public function messagesEnvoyes()
{
    return $this->hasMany(Message::class, 'sender_id')
        ->where('sender_type', 'admin');
}

public function conversationsGerees()
{
    return $this->hasMany(Conversation::class, 'admin_id');
}

    public function logsActivites()
    {
        return $this->hasMany(LogActivite::class, 'admin_id');
    }

    /**
     * Scopes
     */
    public function scopeActifs($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeParRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Accessors & Mutators
     */
    public function getNomCompletAttribute()
    {
        return $this->prenoms . ' ' . $this->nom;
    }

    public function getInitialesAttribute()
    {
        return strtoupper(substr($this->prenoms, 0, 1) . substr($this->nom, 0, 1));
    }

    /**
     * Vérifier si l'admin a une permission spécifique
     */
    public function hasPermission(string $module, string $action): bool
    {
        // Admin1 a tous les droits
        if ($this->role === 'admin1') {
            return true;
        }

        // Vérifier les permissions spécifiques
        if (!$this->permissions || !isset($this->permissions[$module])) {
            return false;
        }

        return in_array($action, $this->permissions[$module]);
    }

    /**
     * Méthodes utilitaires
     */
    public function peutGerer($action)
    {
        $permissions = [
            'admin1' => [
                'gerer_rdv', 'gerer_reclamations', 'gerer_documents', 
                'envoyer_messages', 'voir_statistiques', 'exporter_donnees'
            ],
            'admin2' => [
                'gerer_rdv', 'gerer_reclamations', 'gerer_documents', 
                'envoyer_messages', 'voir_statistiques', 'exporter_donnees'
            ],
            'super_admin' => [
                'gerer_rdv', 'gerer_reclamations', 'gerer_documents', 
                'envoyer_messages', 'voir_statistiques', 'exporter_donnees',
                'gerer_admins', 'voir_logs', 'configuration_systeme'
            ]
        ];

        return in_array($action, $permissions[$this->role] ?? []);
    }

    public function statistiquesActivite()
    {
        return [
            'rdv_traites' => $this->rendezVousTraites()->count(),
            'rdv_ce_mois' => $this->rendezVousTraites()
                ->whereMonth('updated_at', now()->month)->count(),
            'reclamations_traitees' => $this->reclamationsTraitees()->count(),
            'reclamations_ce_mois' => $this->reclamationsTraitees()
                ->whereMonth('updated_at', now()->month)->count(),
            'documents_traites' => $this->documentsTraites()->count(),
            'documents_ce_mois' => $this->documentsTraites()
                ->whereMonth('updated_at', now()->month)->count(),
            'messages_envoyes' => $this->messagesEnvoyes()->count(),
            'messages_ce_mois' => $this->messagesEnvoyes()
                ->whereMonth('created_at', now()->month)->count()
        ];
    }

    /**
     * Enregistrer une activité
     */
    public function enregistrerActivite($action, $description, $model = null)
    {
        LogActivite::create([
            'admin_id' => $this->id,
            'action' => $action,
            'description' => $description,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model ? $model->id : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }
}