<?php

declare(strict_types=1);

namespace BitWasp\Bitcoind;

class IncrementalDataDirGenerator implements DataDirGeneratorInterface
{
    /**
     * @var string
     */
    private $baseDir;

    /**
     * @var string[]
     */
    private $dirs = [];

    public function __construct(string $baseDir)
    {
        while (substr($baseDir, -1) === "/") {
            $baseDir = substr($baseDir, 0, -1);
        }

        $this->baseDir = $baseDir;
    }

    protected function getNextId(): string
    {
        return (string) count(array_filter(glob("{$this->baseDir}/*"), "is_dir"));
    }

    public function createNextDir(): string
    {
        $this->dirs[] = $dataDir = "{$this->baseDir}/{$this->getNextId()}";
        return $dataDir;
    }

    public function getDirs(): array
    {
        return $this->dirs;
    }
}
