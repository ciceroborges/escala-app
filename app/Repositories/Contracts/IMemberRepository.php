<?php

namespace App\Repositories\Contracts;

use App\Models\Member;
use Illuminate\Database\Eloquent\Collection;

interface IMemberRepository
{
    public function findInMinistry(int $ministryId, int $memberId): ?Member;

    public function findForUser(int $ministryId, int $userId): ?Member;

    public function findByMinistry(int $ministryId, bool $onlyActive = false): Collection;

    public function emailExists(int $ministryId, string $email, ?int $ignoreId = null): bool;

    public function countAdmins(int $ministryId): int;

    public function create(array $data): Member;

    public function update(Member $member, array $data): Member;

    public function delete(Member $member): bool;

    public function syncPositions(Member $member, array $positionIds): void;

    /** Vincula ao usuário os pré-cadastros feitos com o e-mail dele. */
    public function linkPendingByEmail(string $email, int $userId): int;
}
