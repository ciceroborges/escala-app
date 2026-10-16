<?php

namespace App\Services\Contracts;

use App\Models\Schedule;

interface IMessageService
{
    /** Texto da escala do mês, no formato que o ministério manda no grupo. */
    public function scheduleMessage(Schedule $schedule): string;

    /** Texto do repertório do mês. */
    public function repertoireMessage(Schedule $schedule): string;
}
