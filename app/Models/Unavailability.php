<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['member_id', 'worship_service_id'])]
class Unavailability extends Model
{
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function worshipService(): BelongsTo
    {
        return $this->belongsTo(WorshipService::class);
    }
}
