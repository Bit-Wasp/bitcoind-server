<?php

declare(strict_types=1);

namespace BitWasp\Bitcoind;

interface DataDirGeneratorInterface
{
    public function createNextDir(): string;
    public function getDirs(): array;
}
