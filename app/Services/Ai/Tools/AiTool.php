<?php

declare(strict_types=1);

namespace App\Services\Ai\Tools;

use App\Models\User;

interface AiTool
{
    public function name(): string;

    public function description(): string;

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function run(array $arguments, User $user): array;
}
