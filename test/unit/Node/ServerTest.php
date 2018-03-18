<?php

declare(strict_types=1);

namespace BitWasp\Test\Bitcoind\Node;

use BitWasp\Bitcoind\Config\Config;
use BitWasp\Bitcoind\Config\FilesystemLoader;
use BitWasp\Bitcoind\Config\FilesystemWriter;
use BitWasp\Bitcoind\Exception\ServerException;
use BitWasp\Bitcoind\Node\NodeOptions;
use BitWasp\Bitcoind\Node\Server;
use BitWasp\Bitcoind\NodeService;
use BitWasp\Test\Bitcoind\TestCase;
use Matomo\Ini\IniReader;
use Nbobtc\Command\Command;

class ServerTest extends TestCase
{
    public function testRequiresValidDataDir()
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage("Cannot create server without a valid datadir");

        new Server(new NodeOptions("/usr/bin/bitcoind", "unknowndir"));
    }

    public function testServerShouldBeRunningForClient()
    {
        $bitcoind = $this->getBitcoindPath();
        $dataDir = $this->registerTmpDir("client-requires-server");

        $options = new NodeOptions($bitcoind, $dataDir);
        $config = new Config([
            'regtest' => '1',
            'daemon' => '1',
        ]);

        $writer = new FilesystemWriter();
        $service = new NodeService();
        $node = $service->createNewNode($options, $config, $writer);
        $this->assertFalse($node->isRunning());

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage("Cannot get Client for non-running server");

        $node->getClient();
    }

    public function testStartAndStop()
    {
        $bitcoind = $this->getBitcoindPath();
        $dataDir = $this->registerTmpDir("datadir-start-stop");

        $options = new NodeOptions($bitcoind, $dataDir);
        $config = new Config([
            'regtest' => '1',
            'daemon' => '1',
        ]);

        $writer = new FilesystemWriter();
        $loader = new FilesystemLoader(new IniReader());

        $service = new NodeService();
        $node = $service->createNewNode($options, $config, $writer);
        $this->assertFalse($node->isRunning());

        $node->start($loader);
        $this->assertTrue($node->waitForStartup());
        $this->assertTrue($node->isRunning());
        $this->assertNotEquals(0, $node->getPid());

        $node->shutdown();
        $this->assertFalse($node->isRunning());

        $node2 = $service->loadNode($options);
        $node2->start($loader);
        $this->assertTrue($node2->waitForStartup());
        $this->assertTrue($node2->isRunning());
        $this->assertNotEquals(0, $node2->getPid());

        $node2->shutdown();
        $this->assertFalse($node2->isRunning());
    }

    public function testStartRpcAndStop()
    {
        $bitcoind = $this->getBitcoindPath();
        $dataDir = $this->registerTmpDir("datadir-start-stop-rpc");

        $options = new NodeOptions($bitcoind, $dataDir);
        $config = new Config([
            'daemon' => '1',
            'rpcuser' => 'u',
            'rpcallowip' => '127.0.0.1',
            'regtest' => '1',
            'rpcpassword' => 'p',
        ]);

        $writer = new FilesystemWriter();
        $loader = new FilesystemLoader(new IniReader());

        $service = new NodeService();
        $node = $service->createNewNode($options, $config, $writer);
        $this->assertFalse($node->isRunning());

        $node->start($loader);
        $this->assertTrue($node->waitForStartup());
        $this->assertNotEquals(0, $node->getPid());
        $this->assertTrue($node->isRunning());
        $this->assertTrue($node->waitForRpc());
        $this->assertNotEquals(0, $node->getPid());

        $client = $node->getClient();
        $result = json_decode($client->sendCommand(new Command('getblockchaininfo'))->getBody()->getContents(), true);

        $this->assertEquals(0, $result['result']['blocks']);
        $this->assertEquals(0, $result['result']['headers']);

        $node->shutdown();
        $this->assertFalse($node->isRunning());
    }


    public function testNeedsToBeRunningForPid()
    {
        $bitcoind = $this->getBitcoindPath();
        $dataDir = $this->registerTmpDir("datadir-not-running-no-pid");

        $options = new NodeOptions($bitcoind, $dataDir);
        $config = new Config([
            'daemon' => '1',
            'regtest' => '1',
        ]);

        $writer = new FilesystemWriter();

        $service = new NodeService();
        $node = $service->createNewNode($options, $config, $writer);

        $this->assertFalse($node->isRunning());

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage("Server is not running - no PID file");

        $node->getPid();
    }

    public function testNeedsToBeRunningForShutdown()
    {
        $bitcoind = $this->getBitcoindPath();
        $dataDir = $this->registerTmpDir("datadir-not-running-no-shutdown");

        $options = new NodeOptions($bitcoind, $dataDir);
        $config = new Config([
            'daemon' => '1',
            'regtest' => '1',
        ]);

        $writer = new FilesystemWriter();

        $service = new NodeService();
        $node = $service->createNewNode($options, $config, $writer);

        $this->assertFalse($node->isRunning());

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage("Server is not running, cannot shut down");

        $node->shutdown();
    }
}
