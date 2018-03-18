<?php

declare(strict_types=1);

namespace BitWasp\Bitcoind\Node;

use BitWasp\Bitcoind\Config\Config;

class NodeOptions
{
    /**
     * @var string
     */
    private $configFileName = "bitcoin.conf";

    /**
     * @var string
     */
    private $dataDir;

    /**
     * @var string
     */
    private $bitcoind;

    /**
     * NodeOptions constructor.
     * @param string $bitcoind - path to bitcoind executable
     * @param string $dataDir - path to bitcoin datadir
     */
    public function __construct(string $bitcoind, string $dataDir)
    {
        if (substr($dataDir, -1) !== "/") {
            $dataDir = "{$dataDir}/";
        }

        $this->bitcoind = $bitcoind;
        $this->dataDir = $dataDir;
    }

    public function withConfigFileName(string $fileName): NodeOptions
    {
        $this->configFileName = $fileName;
        return $this;
    }

    public function getBitcoindPath(): string
    {
        return $this->bitcoind;
    }

    public function getDataDir(): string
    {
        return $this->dataDir;
    }

    public function getConfigFileName(): string
    {
        return $this->configFileName;
    }

    private function getAbsolutePath(string $path): string
    {
        return "{$this->dataDir}{$path}";
    }

    public function getAbsoluteConfigPath(): string
    {
        return $this->getAbsolutePath($this->configFileName);
    }

    public function getAbsolutePidPath(Config $config): string
    {
        return $this->getAbsolutePath($config->getRelativePidPath());
    }

    public function getAbsoluteLogPath(Config $config): string
    {
        return $this->getAbsolutePath($config->getRelativeLogPath());
    }

    public function getStartupCommand(): string
    {
        return sprintf("%s -datadir=%s", $this->getBitcoindPath(), $this->getDataDir());
    }

    public function hasConfig(): bool
    {
        $configPath = $this->getAbsoluteConfigPath();
        return file_exists($configPath) && is_file($configPath);
    }
}
