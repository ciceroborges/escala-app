<?php

namespace App\Services;

use App\Enums\MemberRole;
use App\Models\Member;
use App\Repositories\Contracts\IMemberRepository;
use App\Repositories\Contracts\IUserRepository;
use App\Services\Contracts\IMemberService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MemberService implements IMemberService
{
    public function __construct(
        private readonly IMemberRepository $members,
        private readonly IUserRepository $users,
    ) {}

    public function list(int $ministryId): Collection
    {
        return $this->members->findByMinistry($ministryId);
    }

    public function find(int $ministryId, int $memberId): ?Member
    {
        return $this->members->findInMinistry($ministryId, $memberId);
    }

    public function create(int $ministryId, array $data): Member
    {
        $data['email'] = Str::lower($data['email']);
        $this->ensureUniqueEmail($ministryId, $data['email']);

        return DB::transaction(function () use ($ministryId, $data) {
            // se a pessoa já usa o app, o vínculo é imediato; senão acontece no primeiro login
            $user = $this->users->findByEmail($data['email']);

            $member = $this->members->create($this->attributes($data) + [
                'ministry_id' => $ministryId,
                'user_id' => $user?->id,
            ]);
            $this->members->syncPositions($member, $data['positions'] ?? []);

            return $member->load('positions');
        });
    }

    public function update(Member $member, array $data, Member $actor): Member
    {
        $data['email'] = Str::lower($data['email']);
        $this->ensureUniqueEmail($member->ministry_id, $data['email'], $member->id);

        $losesAdmin = $member->isAdmin()
            && (($data['role'] ?? MemberRole::Admin->value) !== MemberRole::Admin->value || ! ($data['active'] ?? true));
        if ($losesAdmin && $this->members->countAdmins($member->ministry_id) <= 1) {
            throw ValidationException::withMessages(['role' => 'O ministério precisa de pelo menos um administrador.']);
        }

        return DB::transaction(function () use ($member, $data) {
            $attributes = $this->attributes($data);
            if ($member->user_id === null) {
                $attributes['user_id'] = $this->users->findByEmail($data['email'])?->id;
            }
            $this->members->update($member, $attributes);
            $this->members->syncPositions($member, $data['positions'] ?? []);

            return $member->load('positions');
        });
    }

    public function delete(Member $member, Member $actor): void
    {
        if ($member->is($actor)) {
            throw ValidationException::withMessages(['member' => 'Você não pode remover a si mesmo.']);
        }
        if ($member->isAdmin() && $this->members->countAdmins($member->ministry_id) <= 1) {
            throw ValidationException::withMessages(['member' => 'O ministério precisa de pelo menos um administrador.']);
        }

        $this->members->delete($member);
    }

    private function attributes(array $data): array
    {
        $positions = $data['positions'] ?? [];
        $fixed = $data['fixed_position_id'] ?? null;

        return [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'] ?? MemberRole::Member->value,
            'permissions' => array_values($data['permissions'] ?? []),
            'monthly_limit' => ($data['unlimited'] ?? false) ? null : ($data['monthly_limit'] ?? 2),
            // fixo só faz sentido numa função que a pessoa tem
            'fixed_position_id' => in_array($fixed, $positions) ? $fixed : null,
            'active' => $data['active'] ?? true,
        ];
    }

    private function ensureUniqueEmail(int $ministryId, string $email, ?int $ignoreId = null): void
    {
        if ($this->members->emailExists($ministryId, $email, $ignoreId)) {
            throw ValidationException::withMessages(['email' => 'Já existe alguém com esse e-mail no ministério.']);
        }
    }
}
