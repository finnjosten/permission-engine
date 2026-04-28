<?php

namespace Finn\PermissionEngine\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    protected static function newFactory()
    {
        return \Finn\PermissionEngine\Database\Factories\RoleFactory::new();
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users()
    {
        return $this->hasMany(config('permission-engine.user_model', 'App\\Models\\User'));
    }
}
