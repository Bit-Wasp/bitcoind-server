<?php

declare(strict_types=1);

namespace BitWasp\Bitcoind\Config;

abstract class Loader
{
    abstract public function load(string $filePath): Config;
}
