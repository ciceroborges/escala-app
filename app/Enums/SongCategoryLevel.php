<?php

namespace App\Enums;

enum SongCategoryLevel: string
{
    case Primary = 'primary';
    case Secondary = 'secondary';

    public function label(): string
    {
        return match ($this) {
            self::Primary => 'Primária',
            self::Secondary => 'Secundária',
        };
    }
}
