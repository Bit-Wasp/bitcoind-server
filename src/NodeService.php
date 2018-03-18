<?php

declare(strict_types=1);

namespace BitWasp\Bitcoind;

use BitWasp\Bitcoind\Config;
use BitWasp\Bitcoind\Node\NodeOptions;
use BitWasp\Bitcoind\Node\Server;

class NodeService
{
    protected function checkBitcoindExists(string $bitcoind)
    {
        if (!file_exists($bitcoind)) {
            throw new Exception\SetupException("Path to bitcoind executable is invalid: {$bitcoind}");
        }

        if (!is_executable($bitcoind)) {
            throw new Exception\SetupException("Bitcoind must be executable");
        }
    }

    protected function setupDataDir(NodeOptions $options, Config\Config $config, Config\Writer $writer)
    {
        if (is_dir($options->getDataDir())) {
            throw new Exception\SetupException("Cannot create a node in non-empty datadir");
        }

        if (!mkdir($options->getDataDir())) {
            throw new Exception\SetupException("Could not create datadir ({$options->getDataDir()}) - is it writable?");
        }

        $writer->create($options->getAbsoluteConfigPath(), $config);
    }

    public function createNewNode(NodeOptions $options, Config\Config $config, Config\Writer $writer): Server
    {
        $this->checkBitcoindExists($options->getBitcoindPath());
        $this->setupDataDir($options, $config, $writer);

        return new Server($options);
    }

    public function loadNode(NodeOptions $options): Server
    {
        $this->checkBitcoindExists($options->getBitcoindPath());
        return new Server($options);
    }
}
