<?php

declare(strict_types=1);

namespace BitWasp\Test\Bitcoind;

use BitWasp\Bitcoind\Config\Config;
use BitWasp\Bitcoind\Config\FilesystemWriter;
use BitWasp\Bitcoind\Config\Writer;
use BitWasp\Bitcoind\Exception\SetupException;
use BitWasp\Bitcoind\Node\NodeOptions;
use BitWasp\Bitcoind\Node\Server;
use BitWasp\Bitcoind\NodeService;

class NodeServiceTest extends TestCase
{
    public function testCreateNewEnsureWritesToFile()
    {
        $bitcoind = $this->getBitcoindPath();
        $dataDir = $this->registerTmpDir("datadir-createnew");

        $options = new NodeOptions($bitcoind, $dataDir);
        $config = new Config([
            'daemon' => '1',
            'regtest' => '1',
            'server' => '1',
        ]);

        $mock = $this->getMockForAbstractClass(Writer::class);
        $mock->expects($this->once())
            ->method("create")
            ->with($options->getAbsoluteConfigPath(), $config);

        $service = new NodeService();
        $node = $service->createNewNode($options, $config, $mock);
        $this->assertInstanceOf(Server::class, $node);
    }

    public function testCreateNewWithFilesystem()
    {
        $bitcoind = $this->getBitcoindPath();
        $dataDir = $this->registerTmpDir("datadir-createnew-real");

        $options = new NodeOptions($bitcoind, $dataDir);
        $config = new Config([
            'daemon' => '1',
            'regtest' => '1',
            'server' => '1',
        ]);

        $mock = new FilesystemWriter();

        $service = new NodeService();
        $node = $service->createNewNode($options, $config, $mock);
        $this->assertInstanceOf(Server::class, $node);
    }

    public function testChecksBitcoindExists()
    {
        $bitcoind = "/some/invalid/path/bitcoind";
        $dataDir = $this->registerTmpDir("datadir-check-bitcoind-exists");

        $options = new NodeOptions($bitcoind, $dataDir);
        $config = new Config([
            'daemon' => '1',
            'server' => '1',
            'regtest' => '1',
        ]);

        $service = new NodeService();
        $this->expectException(SetupException::class);
        $this->expectExceptionMessage("Path to bitcoind executable is invalid");

        $service->createNewNode($options, $config, new FilesystemWriter());
    }

    public function testChecksDataDirNotExists()
    {
        $bitcoind = $this->getBitcoindPath();
        $dataDir = $this->registerTmpDir("datadir-check-datadir-not-exists");
        mkdir($dataDir);

        $options = new NodeOptions($bitcoind, $dataDir);
        $config = new Config([
            'daemon' => '1',
            'server' => '1',
            'regtest' => '1',
        ]);

        $service = new NodeService();
        $this->expectException(SetupException::class);
        $this->expectExceptionMessage("Cannot create a node in non-empty datadir");

        $service->createNewNode($options, $config, new FilesystemWriter());
    }

    public function testChecksBitcoindPathIsAnExecutable()
    {
        $bitcoind = $this->registerTmpFile("non-executable-bitcoind");
        file_put_contents($bitcoind, "");

        $dataDir = $this->registerTmpDir("datadir-check-bitcoind-executable");

        $options = new NodeOptions($bitcoind, $dataDir);
        $config = new Config([
            'daemon' => '1',
            'server' => '1',
            'regtest' => '1',
        ]);

        $service = new NodeService();
        $this->expectException(SetupException::class);
        $this->expectExceptionMessage("Bitcoind must be executable");

        $service->createNewNode($options, $config, new FilesystemWriter());
    }
}
