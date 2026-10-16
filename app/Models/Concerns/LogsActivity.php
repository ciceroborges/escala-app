<?php

namespace App\Models\Concerns;

use App\Services\Contracts\IActivityLogService;

/**
 * Loga criação, edição (com antes/depois) e exclusão do model.
 * O model define activityLabel() para a descrição legível ("música Pão e Vinho").
 */
trait LogsActivity
{
    private const IGNORED = ['created_at', 'updated_at', 'remember_token'];

    public static function bootLogsActivity(): void
    {
        static::created(fn ($model) => $model->writeActivity('created', 'criou', ['new' => $model->loggableAttributes()]));

        static::updated(function ($model) {
            $changes = array_diff_key($model->getChanges(), array_flip(self::IGNORED));
            if (! $changes) {
                return;
            }
            $old = array_intersect_key($model->getOriginal(), $changes);
            $model->writeActivity('updated', 'alterou', ['old' => $old, 'new' => $changes]);
        });

        static::deleted(fn ($model) => $model->writeActivity('deleted', 'removeu', ['old' => $model->loggableAttributes()]));
    }

    abstract public function activityLabel(): string;

    private function writeActivity(string $event, string $verb, array $properties): void
    {
        app(IActivityLogService::class)->log(
            $this->getTable().'.'.$event,
            "{$verb} {$this->activityLabel()}",
            $this,
            $properties,
        );
    }

    private function loggableAttributes(): array
    {
        return array_diff_key($this->attributesToArray(), array_flip(self::IGNORED));
    }
}
