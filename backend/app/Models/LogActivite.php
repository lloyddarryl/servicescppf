<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogActivite extends Model
{
    use HasFactory;

    protected $table = 'logs_activite';

    protected $fillable = [
        'admin_id',
        'action',
        'details',
        'ip_address',
        'user_agent',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    /**
     * Relations
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Scopes
     */
    public function scopeParAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeParAdmin($query, $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    public function scopeAujourdhui($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeCetteSemaine($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeCeMois($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    /**
     * Accessors
     */
    public function getActionFormatteeAttribute()
    {
        $actions = [
            'connexion' => 'Connexion admin',
            'deconnexion' => 'Déconnexion admin',
            'changement_statut_agent' => 'Changement statut agent',
            'changement_statut_retraite' => 'Changement statut retraité',
            'validation_document' => 'Validation document',
            'rejet_document' => 'Rejet document',
            'suppression_document' => 'Suppression document',
            'traitement_rdv' => 'Traitement RDV',
            'traitement_reclamation' => 'Traitement réclamation',
            'envoi_messages' => 'Envoi de messages',
            'reinitialisation_mot_de_passe' => 'Réinitialisation mot de passe',
            'modification_statut_message' => 'Modification statut message',
            'suppression_message' => 'Suppression message',
            'archivage_messages_expires' => 'Archivage messages expirés',
            'traitement_lot_documents' => 'Traitement en lot documents',
            'telechargement_document' => 'Téléchargement document'
        ];

        return $actions[$this->action] ?? $this->action;
    }

    public function getDateFormatteeAttribute()
    {
        return $this->created_at->format('d/m/Y H:i:s');
    }

    /**
     * Méthodes statiques utilitaires
     */
    public static function creerLog($action, $details = null, $metadata = null)
    {
        return self::create([
            'admin_id' => auth('admin')->id(),
            'action' => $action,
            'details' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata
        ]);
    }

    public static function statistiquesActivite($periode = 'mois')
    {
        $query = self::query();

        switch ($periode) {
            case 'jour':
                $query->aujourdhui();
                break;
            case 'semaine':
                $query->cetteSemaine();
                break;
            case 'mois':
            default:
                $query->ceMois();
                break;
        }

        return [
            'total_actions' => $query->count(),
            'par_action' => $query->selectRaw('action, count(*) as total')
                ->groupBy('action')
                ->get()
                ->pluck('total', 'action'),
            'par_admin' => $query->with('admin')
                ->selectRaw('admin_id, count(*) as total')
                ->groupBy('admin_id')
                ->get()
                ->map(function($log) {
                    return [
                        'admin' => $log->admin ? $log->admin->nom_complet : 'Admin supprimé',
                        'total' => $log->total
                    ];
                })
        ];
    }
}