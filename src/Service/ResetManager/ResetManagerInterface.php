<?php

declare(strict_types=1);

namespace BehatOrmContext\Service\ResetManager;

use Symfony\Component\HttpKernel\KernelInterface;

interface ResetManagerInterface
{
    public function needsReset(string $httpMethod): bool;

    public function reset(KernelInterface $kernel): void;
}
