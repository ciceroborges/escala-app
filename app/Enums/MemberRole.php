<?php

namespace App\Enums;

enum MemberRole: string
{
    case Admin = 'admin';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Member => 'Membro',
        };
    }
}
