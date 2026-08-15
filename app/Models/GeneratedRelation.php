<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneratedRelation extends Model
{
    protected $fillable = [
        'generated_entity_id',
        'type',
        'target',
        'pivot_table',
    ];

    public function entity()
    {
        return $this->belongsTo(GeneratedEntity::class, 'generated_entity_id');
    }
}
