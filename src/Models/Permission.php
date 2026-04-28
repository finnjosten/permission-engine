<?php

namespace Finn\PermissionEngine\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'last_seen_at'];

    protected static function newFactory()
    {
        return \Finn\PermissionEngine\Database\Factories\PermissionFactory::new();
    }

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
