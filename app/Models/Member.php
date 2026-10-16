<?php

namespace App\Models;

use App\Enums\MemberRole;
use App\Enums\Permission;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['ministry_id', 'user_id', 'name', 'email', 'phone', 'role', 'permissions',
    'monthly_limit', 'fixed_position_id', 'active'])]
class Member extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'role' => MemberRole::class,
            'permissions' => 'array',
            'monthly_limit' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class)->orderBy('sort_order');
    }

    public function fixedPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'fixed_position_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function unavailabilities(): HasMany
    {
        return $this->hasMany(Unavailability::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === MemberRole::Admin;
    }

    public function hasPermission(Permission $permission): bool
    {
        $granted = in_array($permission->value, $this->permissions ?? [], true);

        return $permission->isRestriction() ? $granted && ! $this->isAdmin() : $granted || $this->isAdmin();
    }

    public function hasJoined(): bool
    {
        return $this->user_id !== null;
    }
}
