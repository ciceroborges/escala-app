<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Marca que o membro respondeu a disponibilidade do mês, mesmo sem nenhuma indisponibilidade. */
#[Fillable(['member_id', 'schedule_id'])]
class AvailabilityResponse extends Model
{
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
