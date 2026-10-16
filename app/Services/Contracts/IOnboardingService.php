<?php

namespace App\Services\Contracts;

interface IOnboardingService
{
    /**
     * Passos para deixar o ministério rodando, na ordem em que fazem sentido.
     *
     * @return array<int, array{key: string, title: string, description: string, done: bool, route: string}>
     */
    public function checklist(int $ministryId): array;
}
