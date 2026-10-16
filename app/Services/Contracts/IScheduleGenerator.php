<?php

namespace App\Services\Contracts;

use App\Services\Scheduling\GenerationInput;
use App\Services\Scheduling\GenerationResult;

interface IScheduleGenerator
{
    public function generate(GenerationInput $input): GenerationResult;
}
