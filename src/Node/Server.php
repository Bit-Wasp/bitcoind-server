<?php

declare(strict_types=1);

namespace BitWasp\Bitcoind\Node;

use BitWasp\Bitcoind\Config\Config;
use BitWasp\Bitcoind\Config\Loader as ConfigLoader;
use BitWasp\Bitcoind\Exception\ServerException;
use BitWasp\Bitcoind\HttpDriver\CurlDriver;
use Nbobtc\Command\Command;
use Nbobtc\Http\Client;
use Nbobtc\Http\Driver\DriverInterface;

class Server
{
    const ERROR_STARTUP = -28;
    const ERROR_TX_MEMPOOL_CONFLICT = -26;

    /**
     * @var NodeOptions
     */
    private $options;

    /**
     * @var Config
     */
    private $config;

    /**
     * Server constructor.
     * @param NodeOptions $options
     */
    public function __construct(NodeOptions $options)
    {
        if (!is_dir($options->getDataDir())) {
            throw new ServerException("Cannot create server without a valid datadir");
        }
        $this->options = $options;
    }

    public function getNodeOptions(): NodeOptions
    {
        return $this->options;
    }

    private function secondsToMicro(float $seconds): int
    {
        return (int) $seconds * 1000000;
    }

    /**
     * @return bool
     */
    public function waitForStartup(): bool
    {
        for ($i = 0; $i < 5; $i++) {
            if (file_exists($this->options->getAbsolutePidPath($this->config))) {
                return true;
            }
            sleep(1);
        }
        return false;
    }

    public function waitForRpc(): bool
    {
        $start = microtime(true);
        $limit = 10;
        $connected = false;

        $conn = $this->getClient();
        do {
            try {
                $result = json_decode($conn->sendCommand(new Command("getblockchaininfo"))->getBody()->getContents(), true);
                if ($result['error'] === null) {
                    $connected = true;
                } else {
                    if ($result['error']['code'] !== self::ERROR_STARTUP) {
                        throw new \RuntimeException("Unexpected error code during startup");
                    }

                    usleep($this->secondsToMicro(0.02));
                }
            } catch (\Exception $e) {
                usleep($this->secondsToMicro(0.02));
            }

            if (microtime(true) > $start + $limit) {
                throw new \RuntimeException("Timeout elapsed, never made connection to bitcoind");
            }
        } while (!$connected);

        return $connected;
    }

    public function getConfig(ConfigLoader $loader): Config
    {
        return $loader->load(
            $this->options->getAbsoluteConfigPath()
        );
    }

    /**
     * @param ConfigLoader $loader
     */
    public function start(ConfigLoader $loader)
    {
        if ($this->isRunning()) {
            return;
        }

        $this->config = $this->getConfig($loader);

        $res = null;
        $out = [];
        exec($this->options->getStartupCommand(), $out, $res);

        if (0 !== $res) {
            if (getenv('BITCOINDSERVER_DEBUG_START')) {
                echo file_get_contents($this->options->getAbsoluteLogPath($this->config));
            }
            throw new \RuntimeException("Failed to start bitcoind: {$this->options->getDataDir()}\n");
        }

        $tries = 3;
        do {
            if (!$this->isRunning()) {
                if ($tries === 0) {
                    if (getenv('BITCOINDSERVER_DEBUG_START')) {
                        echo file_get_contents($this->options->getAbsoluteLogPath($this->config));
                    }
                    throw new \RuntimeException("node didn't start");
                }
                usleep(50000);
            }
        } while ($tries-- > 0 && !$this->isRunning());
    }

    /**
     * @return Client
     * @throws ServerException
     * @throws \BitWasp\Bitcoind\Exception\BitcoindException
     */
    public function getClient(DriverInterface $driver = null): Client
    {
        if (!$this->isRunning()) {
            throw new ServerException("Cannot get Client for non-running server");
        }

        $client = new Client($this->config->getRpcDsn());
        $client->withDriver($driver ?: new CurlDriver());
        return $client;
    }

    public function shutdown()
    {
        if (!$this->isRunning()) {
            throw new ServerException("Server is not running, cannot shut down");
        }

        $out = null;
        $ret = null;
        exec("kill -15 {$this->getPid()}", $out, $ret);
        if ($ret !== 0) {
            throw new ServerException("Failed sending SIGTERM to node");
        }

        $timeoutSeconds = 5;
        $sleepsPerSecond = 5;
        $steps = $timeoutSeconds * $sleepsPerSecond;
        $usleep = pow(10, 6) / $sleepsPerSecond;

        for ($i = 0; $i < $steps; $i++) {
            if ($this->isRunning()) {
                usleep($usleep);
            }
        }

        if ($this->isRunning()) {
            throw new ServerException("Failed to shutdown node!");
        }
    }

    public function isRunning(): bool
    {
        return $this->config != null && file_exists($this->options->getAbsolutePidPath($this->config));
    }

    public function getPid(): int
    {
        if (!$this->isRunning()) {
            throw new ServerException("Server is not running - no PID file");
        }

        return (int) trim(file_get_contents($this->options->getAbsolutePidPath($this->config)));
    }
}
