<?php

namespace Finn\PermissionEngine\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users()
    {
        return $this->hasMany(config('permission-engine.user_model', 'App\\Models\\User'));
    }
}
