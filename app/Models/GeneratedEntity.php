<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneratedEntity extends Model
{
    protected $fillable = [
        'generated_project_id',
        'name',
    ];

    public function project()
    {
        return $this->belongsTo(GeneratedProject::class, 'generated_project_id');
    }

    public function fields()
    {
        return $this->hasMany(GeneratedField::class);
    }
}
