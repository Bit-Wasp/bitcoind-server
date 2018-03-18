<?php

declare(strict_types=1);

namespace BitWasp\Bitcoind\Config;

use BitWasp\Bitcoind\Exception\BitcoindException;
use BitWasp\Bitcoind\Exception\ServerException;

class Config
{
    private $options = [];

    public function __construct(array $options = [])
    {
        $this->withOptions($options);
    }

    private function withOptions(array $options)
    {
        foreach ($options as $key => $option) {
            if (!is_string($key)) {
                throw new BitcoindException("Invalid config key");
            }
        }

        if ((array_key_exists('regtest', $options) && (bool) $options['regtest'])  &&
            (array_key_exists('testnet', $options) && (bool) $options['testnet'])) {
            throw new ServerException("Configuration conflicts, cannot be regtest and testnet");
        }

        $this->options = $options;
    }

    public function has(string $option): bool
    {
        return array_key_exists($option, $this->options);
    }

    public function get(string $option, string $default = null)
    {
        if ($this->has($option)) {
            return $this->options[$option];
        }

        return $default;
    }

    public function all(): array
    {
        return $this->options;
    }

    public function isRpcServer(): bool
    {
        return $this->has('server') &&
            (bool) $this->get('server')
        ;
    }

    public function hasRpcCredential(): bool
    {
        return $this->has('rpcuser') &&
            $this->has('rpcpassword');
    }

    public function getDefaultRpcPort(): int
    {
        if ($this->isTestnet()) {
            return 18332;
        } else if ($this->isRegtest()) {
            return 18443;
        } else {
            return 8332;
        }
    }

    public function getRpcDsn(): string
    {
        $host = $this->get('rpcconnect', '127.0.0.1');
        $port = (int) $this->get('rpcport', (string) $this->getDefaultRpcPort());

        if (!$this->hasRpcCredential()) {
            throw new BitcoindException("Missing rpc credential fields");
        }

        $user = $this->get('rpcuser');
        $pass = $this->get('rpcpassword');

        return "http://{$user}:{$pass}@{$host}:{$port}";
    }

    public function isTestnet(): bool
    {
        return $this->has('testnet') && (bool) $this->get('testnet');
    }

    public function isRegtest(): bool
    {
        return $this->has('regtest') && (bool) $this->get('regtest');
    }

    public function getRelativeChainPath(): string
    {
        if ($this->isTestnet()) {
            return "testnet3/";
        } else if ($this->isRegtest()) {
            return "regtest/";
        }

        return "";
    }

    private function getPathInChainDir($path): string
    {
        return "{$this->getRelativeChainPath()}$path";
    }

    public function getRelativePidPath(): string
    {
        return $this->getPathInChainDir("bitcoind.pid");
    }

    public function getRelativeLogPath(): string
    {
        return $this->getPathInChainDir("debug.log");
    }
}
