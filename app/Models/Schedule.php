<?php

namespace App\Models;

use App\Enums\ScheduleStatus;
use App\Models\Concerns\LogsActivity;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['ministry_id', 'year', 'month', 'status', 'availability_deadline'])]
class Schedule extends Model
{
    use HasFactory, LogsActivity;

    private const MONTHS = [1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

    protected function casts(): array
    {
        return [
            'status' => ScheduleStatus::class,
            'availability_deadline' => 'date',
            'year' => 'integer',
            'month' => 'integer',
        ];
    }

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    public function worshipServices(): HasMany
    {
        return $this->hasMany(WorshipService::class)->orderBy('date');
    }

    public function availabilityResponses(): HasMany
    {
        return $this->hasMany(AvailabilityResponse::class);
    }

    public function label(): string
    {
        return self::MONTHS[$this->month].' / '.$this->year;
    }

    public function firstDay(): CarbonImmutable
    {
        return CarbonImmutable::create($this->year, $this->month, 1);
    }

    public function isPublished(): bool
    {
        return $this->status === ScheduleStatus::Published;
    }

    public function activityLabel(): string
    {
        return "escala de {$this->label()}";
    }
}
