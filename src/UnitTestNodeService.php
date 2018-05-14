<?php

declare(strict_types=1);

namespace BitWasp\Bitcoind;

use BitWasp\Bitcoind\Config;
use BitWasp\Bitcoind\Exception\UnitTestException;
use BitWasp\Bitcoind\Node\NodeOptions;
use BitWasp\Bitcoind\Node\Server;
use BitWasp\Bitcoind\Utils\File;
use Matomo\Ini\IniReader;

class UnitTestNodeService
{
    /**
     * @var string
     */
    private $tmpDir;

    /**
     * @var string
     */
    private $bitcoindPath;

    /**
     * @var NodeService
     */
    private $service;

    /**
     * @var Config\FilesystemWriter
     */
    private $writer;

    /**
     * @var Config\FilesystemLoader
     */
    private $reader;

    /**
     * @var array
     */
    private $nodeDirs = [];

    /**
     * @var Server[]
     */
    private $servers = [];

    /**
     * @var DataDirGeneratorInterface
     */
    private $dataDirGenerator;

    private $cleanupWhenFinished = true;

    public function __construct(string $bitcoindPath, NodeService $nodeService, DataDirGeneratorInterface $dataDirGenerator = null)
    {
        $this->tmpDir = sys_get_temp_dir();
        $this->bitcoindPath = $bitcoindPath;
        $this->dataDirGenerator = $dataDirGenerator ?: new IncrementalDataDirGenerator($this->tmpDir);
        $this->service = $nodeService;
        $this->writer = new Config\FilesystemWriter();
        $this->reader = new Config\FilesystemLoader(new IniReader());
    }

    public function setCleanup(bool $doCleanup)
    {
        $this->cleanupWhenFinished = $doCleanup;
        return $this;
    }

    /**
     * @param int $testId
     * @return int
     * @throws UnitTestException
     */
    protected function findAvailablePort(int $testId): int
    {
        $port = $testId;
        do {
            if ($port > 65535) {
                throw new UnitTestException("Invalid port for node");
            }
            $connection = @fsockopen('localhost', $port);
            $isUsed = is_resource($connection);
            if (!$isUsed) {
                return $port;
            }
            $port++;
        } while ($isUsed);
    }

    /**
     * @param int $testId
     * @return Config\Config
     * @throws UnitTestException
     * @throws \Exception
     */
    protected function createRandomConfig(int $testId): Config\Config
    {
        $this->findAvailablePort($testId);
        $password = preg_replace("/[^A-Za-z0-9 ]/", '', base64_encode(random_bytes(16)));
        $nonce = preg_replace("/[^A-Za-z0-9 ]/", '', base64_encode(random_bytes(4)));
        return new Config\Config([
            'rpcuser' => "user-{$testId}-{$nonce}",
            'rpcpassword' => $password,
            'rpcallowip' => '127.0.0.1',
            'regtest' => '1',
            'rpcport' => '18443',
            'server' => '1',
            'daemon' => '1',
        ]);
    }

    /**
     * @return Server
     * @throws Exception\ServerException
     * @throws UnitTestException
     * @throws \Exception
     */
    public function create(bool $wait = null): Server
    {
        if ($wait === null) {
            $wait = true;
        }

        $testId = count($this->nodeDirs);
        $dataDir = $this->dataDirGenerator->createNextDir();
        $config = $this->createRandomConfig($testId);
        $options = new NodeOptions($this->bitcoindPath, $dataDir);

        $node = $this->service->createNewNode($options, $config, $this->writer);
        $node->start($this->reader);
        if ($wait) {
            $node->waitForStartup();
            $node->waitForRpc();
        }

        $this->servers[] = $node;

        return $node;
    }

    /**
     *
     */
    protected function cleanup()
    {
        $servers = 0;
        foreach ($this->servers as $server) {
            $servers++;
            if ($server->isRunning()) {
                $server->shutdown();

                $dataDir = $server->getNodeOptions()->getDataDir();
                if (file_exists($dataDir)) {
                    File::recursiveDelete($dataDir);
                }
            }
        }

        foreach ($this->dataDirGenerator->getDirs() as $dir) {
            if (is_dir($dir)) {
                File::recursiveDelete($dataDir);
            }
        }
    }

    public function __destruct()
    {
        $this->cleanup();
    }
}
