<?php

namespace App\Models;

use App\Enums\SongCategoryLevel;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['ministry_id', 'level', 'name', 'active'])]
class SongCategory extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'level' => SongCategoryLevel::class,
            'active' => 'boolean',
        ];
    }

    public function options(): HasMany
    {
        return $this->hasMany(SongCategoryOption::class)->orderBy('sort_order');
    }

    public function activityLabel(): string
    {
        return "classificação {$this->name}";
    }
}
