<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailEnvoye extends Model
{
    protected $table = 'emails_envoyes';
    
    protected $fillable = [
        'retraite_id',
        'type',
        'destinataire',
        'envoye_le',
        'ouvert',
        'ouvert_le'
    ];
    
    protected $casts = [
        'envoye_le' => 'datetime',
        'ouvert_le' => 'datetime',
        'ouvert' => 'boolean'
    ];
    
    public function retraite()
    {
        return $this->belongsTo(Retraite::class);
    }
}