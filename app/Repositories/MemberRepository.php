<?php

namespace App\Repositories;

use App\Enums\MemberRole;
use App\Models\Member;
use App\Repositories\Contracts\IMemberRepository;
use Illuminate\Database\Eloquent\Collection;

class MemberRepository implements IMemberRepository
{
    public function __construct(private readonly Member $model) {}

    public function findInMinistry(int $ministryId, int $memberId): ?Member
    {
        return $this->model->where('ministry_id', $ministryId)->with('positions')->find($memberId);
    }

    public function findForUser(int $ministryId, int $userId): ?Member
    {
        return $this->model->where('ministry_id', $ministryId)->where('user_id', $userId)->where('active', true)->first();
    }

    public function findByMinistry(int $ministryId, bool $onlyActive = false): Collection
    {
        return $this->model
            ->where('ministry_id', $ministryId)
            ->when($onlyActive, fn ($q) => $q->where('active', true))
            ->with('positions')
            ->orderBy('name')
            ->get();
    }

    public function emailExists(int $ministryId, string $email, ?int $ignoreId = null): bool
    {
        return $this->model->where('ministry_id', $ministryId)->where('email', $email)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();
    }

    public function countAdmins(int $ministryId): int
    {
        return $this->model->where('ministry_id', $ministryId)->where('role', MemberRole::Admin)->where('active', true)->count();
    }

    public function create(array $data): Member
    {
        return $this->model->create($data);
    }

    public function update(Member $member, array $data): Member
    {
        $member->update($data);

        return $member;
    }

    public function delete(Member $member): bool
    {
        return (bool) $member->delete();
    }

    public function syncPositions(Member $member, array $positionIds): void
    {
        $member->positions()->sync($positionIds);
    }

    public function linkPendingByEmail(string $email, int $userId): int
    {
        return $this->model->whereNull('user_id')->where('email', $email)->update(['user_id' => $userId]);
    }
}
