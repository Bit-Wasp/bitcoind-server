<?php

declare(strict_types=1);

namespace BitWasp\Bitcoind\Config;

use Matomo\Ini\IniReader;

class FilesystemLoader extends Loader
{
    /**
     * @var IniReader
     */
    private $reader;

    public function __construct(IniReader $reader)
    {
        $this->reader = $reader;
    }

    public function load(string $filePath): Config
    {
        return new Config($this->reader->readFile($filePath));
    }
}
