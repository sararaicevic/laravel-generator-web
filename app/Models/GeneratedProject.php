<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneratedProject extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'dsl_path',
        'output_path',
        'zip_path',
        'status',
        'error_message',
    ];

    public function entities()
    {
        return $this->hasMany(GeneratedEntity::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
