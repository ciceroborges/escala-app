<?php

namespace App\Services;

use App\Enums\MemberRole;
use App\Enums\SongCategoryLevel;
use App\Models\Ministry;
use App\Models\User;
use App\Repositories\Contracts\IMemberRepository;
use App\Repositories\Contracts\IMinistryRepository;
use App\Repositories\Contracts\IPositionRepository;
use App\Repositories\Contracts\ISongRepository;
use App\Services\Contracts\IMinistryService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MinistryService implements IMinistryService
{
    /** Padrão que cobre a maioria dos ministérios; tudo editável depois. */
    private const DEFAULT_POSITIONS = [
        ['name' => 'Vocal', 'emoji' => '🎤', 'uses_microphone' => true, 'is_vocal' => true, 'min_per_service' => 3, 'max_per_service' => 4],
        ['name' => 'DM', 'emoji' => '🎧', 'uses_microphone' => true, 'is_vocal' => false, 'min_per_service' => 0, 'max_per_service' => 1],
        ['name' => 'Teclado', 'emoji' => '🎹', 'min_per_service' => 1, 'max_per_service' => 1],
        ['name' => 'Violão', 'emoji' => '🎸', 'min_per_service' => 0, 'max_per_service' => 1],
        ['name' => 'Guitarra', 'emoji' => '🎸', 'min_per_service' => 0, 'max_per_service' => 1],
        ['name' => 'Baixo', 'emoji' => '🎸', 'min_per_service' => 0, 'max_per_service' => 1],
        ['name' => 'Bateria', 'emoji' => '🥁', 'min_per_service' => 1, 'max_per_service' => 1],
        ['name' => 'Mesa', 'emoji' => '🎛️', 'min_per_service' => 1, 'max_per_service' => 1],
    ];

    public function __construct(
        private readonly IMinistryRepository $ministries,
        private readonly IMemberRepository $members,
        private readonly IPositionRepository $positions,
        private readonly ISongRepository $songs,
    ) {}

    public function listForUser(User $user): Collection
    {
        return $this->ministries->findForUser($user->id);
    }

    public function create(User $user, array $data): Ministry
    {
        return DB::transaction(function () use ($user, $data) {
            $ministry = $this->ministries->create([
                'name' => $data['name'],
                'church_name' => $data['church_name'] ?? null,
                'created_by' => $user->id,
            ]);

            foreach (self::DEFAULT_POSITIONS as $order => $position) {
                $this->positions->create($position + ['ministry_id' => $ministry->id, 'sort_order' => $order]);
            }

            $this->members->create([
                'ministry_id' => $ministry->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => MemberRole::Admin,
                'monthly_limit' => null,
            ]);

            $rhythm = $this->songs->upsertCategory($ministry->id, SongCategoryLevel::Primary, ['name' => 'Ritmo', 'active' => true]);
            $this->songs->syncOptions($rhythm, [
                ['name' => 'Rápida', 'emoji' => '🔴'],
                ['name' => 'Média', 'emoji' => '🟡'],
                ['name' => 'Lenta', 'emoji' => '🔵'],
            ]);

            $type = $this->songs->upsertCategory($ministry->id, SongCategoryLevel::Secondary, ['name' => 'Tipo', 'active' => false]);
            $this->songs->syncOptions($type, [
                ['name' => 'Louvor', 'emoji' => null],
                ['name' => 'Adoração', 'emoji' => null],
                ['name' => 'Ceia', 'emoji' => null],
            ]);

            return $ministry;
        });
    }

    public function update(Ministry $ministry, array $data): Ministry
    {
        return $this->ministries->update($ministry, $data);
    }
}
