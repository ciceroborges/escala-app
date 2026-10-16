<?php

namespace App\Enums;

/** Permissões extras que o administrador libera para um membro. Admin tem todas. */
enum Permission: string
{
    case ManageSchedule = 'manage_schedule';
    case ManageRepertoire = 'manage_repertoire';
    case ViewOwnScheduleOnly = 'view_own_schedule_only';

    public function label(): string
    {
        return match ($this) {
            self::ManageSchedule => 'Montar e editar escalas',
            self::ManageRepertoire => 'Editar repertório e setlists',
            self::ViewOwnScheduleOnly => 'Ver apenas as próprias escalas',
        };
    }

    /** Restrições não são "ganhas" pelo admin: só se aplicam a quem foi marcado. */
    public function isRestriction(): bool
    {
        return $this === self::ViewOwnScheduleOnly;
    }
}
