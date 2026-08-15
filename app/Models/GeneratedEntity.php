<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneratedEntity extends Model
{
    protected $fillable = [
        'generated_project_id',
        'name',
        'has_index',
        'has_create',
        'has_edit',
        'has_show',
        'allows_delete',
        'display_field',
    ];

    public function project()
    {
        return $this->belongsTo(GeneratedProject::class, 'generated_project_id');
    }

    public function fields()
    {
        return $this->hasMany(GeneratedField::class);
    }

    public function relations()
    {
        return $this->hasMany(GeneratedRelation::class);
    }
}
