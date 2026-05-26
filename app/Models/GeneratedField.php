<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneratedField extends Model
{
    protected $fillable = [
        'generated_entity_id',
        'name',
        'type',
        'is_required',
        'is_unique',
    ];

    public function entity()
    {
        return $this->belongsTo(GeneratedEntity::class, 'generated_entity_id');
    }
}
