<?php

namespace App\Support;

use App\Models\Member;
use App\Models\Ministry;

/** Ministério ativo na sessão e o vínculo do usuário logado com ele. Preenchido pelo middleware. */
class CurrentMinistry
{
    private ?Member $member = null;

    public function set(Member $member): void
    {
        $this->member = $member->loadMissing('ministry');
    }

    public function member(): ?Member
    {
        return $this->member;
    }

    public function ministry(): ?Ministry
    {
        return $this->member?->ministry;
    }

    public function id(): int
    {
        return $this->member->ministry_id;
    }
}
