<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'church_name', 'created_by'])]
class Ministry extends Model
{
    use HasFactory;

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class)->orderBy('name');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class)->orderBy('sort_order');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function songs(): HasMany
    {
        return $this->hasMany(Song::class)->orderBy('title');
    }

    public function songCategories(): HasMany
    {
        return $this->hasMany(SongCategory::class);
    }
}
