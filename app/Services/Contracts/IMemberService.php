<?php

namespace App\Services\Contracts;

use App\Models\Member;
use Illuminate\Database\Eloquent\Collection;

interface IMemberService
{
    public function list(int $ministryId): Collection;

    public function find(int $ministryId, int $memberId): ?Member;

    public function create(int $ministryId, array $data): Member;

    public function update(Member $member, array $data, Member $actor): Member;

    public function delete(Member $member, Member $actor): void;
}
