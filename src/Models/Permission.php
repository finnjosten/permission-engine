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

    /**
     * Generate the wildcard and deny variants of a permission.
     *
     * "post.create.special" -> post.create.*, !post.create.*, post.*, !post.*, *, !*, !post.create.special
     *
     * @return string[]
     */
    public static function variantsFor(string $name): array
    {
        // Skip if already a wildcard or deny
        if (str_contains($name, '*') || str_starts_with($name, '!')) {
            return [];
        }

        $variants = [];
        $segments = explode('.', $name);

        for ($i = count($segments) - 1; $i >= 1; $i--) {
            $prefix = implode('.', array_slice($segments, 0, $i));
            $variants[] = "{$prefix}.*";
            $variants[] = "!{$prefix}.*";
        }

        $variants[] = '*';
        $variants[] = '!*';
        $variants[] = "!{$name}";

        return $variants;
    }
}
