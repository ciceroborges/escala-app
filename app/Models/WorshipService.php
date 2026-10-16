<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['schedule_id', 'date', 'notes'])]
class WorshipService extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['date' => 'immutable_date'];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function unavailabilities(): HasMany
    {
        return $this->hasMany(Unavailability::class);
    }

    public function setlistItems(): HasMany
    {
        return $this->hasMany(SetlistItem::class)->orderBy('sort_order');
    }

    /** "1º Domingo" — ordinal do dia da semana dentro do mês. */
    public function ordinalLabel(): string
    {
        $ordinal = intdiv($this->date->day - 1, 7) + 1;

        return $ordinal.'º '.ucfirst($this->date->locale('pt_BR')->dayName);
    }
}
