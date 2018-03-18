<?php

declare(strict_types=1);

namespace BitWasp\Test\Bitcoind\Config;

use BitWasp\Bitcoind\Config\Config;
use BitWasp\Bitcoind\Exception\BitcoindException;
use BitWasp\Bitcoind\Exception\ServerException;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testConfigWithVal()
    {
        $config = new Config([
            'yourkey' => 'yourval',
        ]);

        $this->assertTrue($config->has('yourkey'));
        $this->assertEquals('yourval', $config->get('yourkey'));

        $this->assertFalse($config->has('unknown'));
        $this->assertNull($config->get('unknown'));
        $this->assertEquals('defaultValue', $config->get('unknown', 'defaultValue'));

        $this->assertEquals([
            'yourkey' => 'yourval',
        ], $config->all());
    }

    public function testIsRpcServer()
    {
        $config = new Config();
        $this->assertFalse($config->isRpcServer());

        $config = new Config([
            'server' => 1,
        ]);

        $this->assertTrue($config->isRpcServer());
    }

    public function testNetwork()
    {
        $config = new Config();
        $this->assertFalse($config->isTestnet());
        $this->assertFalse($config->isRegtest());
        $this->assertEquals(8332, $config->getDefaultRpcPort());
        $this->assertEquals("", $config->getRelativeChainPath());
        $this->assertEquals("bitcoind.pid", $config->getRelativePidPath());

        $config = new Config([
            'regtest' => 1,
        ]);

        $this->assertFalse($config->isTestnet());
        $this->assertTrue($config->isRegtest());
        $this->assertEquals(18443, $config->getDefaultRpcPort());
        $this->assertEquals("regtest/", $config->getRelativeChainPath());
        $this->assertEquals("regtest/bitcoind.pid", $config->getRelativePidPath());

        $config = new Config([
            'testnet' => 1,
        ]);

        $this->assertTrue($config->isTestnet());
        $this->assertFalse($config->isRegtest());
        $this->assertEquals(18332, $config->getDefaultRpcPort());
        $this->assertEquals("testnet3/", $config->getRelativeChainPath());
        $this->assertEquals("testnet3/bitcoind.pid", $config->getRelativePidPath());
    }

    public function testNetworkConflict()
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage("Configuration conflicts, cannot be regtest and testnet");

        new Config([
            'regtest' => true,
            'testnet' => true,
        ]);
    }

    public function testDsnRequiresFields()
    {
        $config = new Config([]);

        $this->expectException(BitcoindException::class);
        $this->expectExceptionMessage("Missing rpc credential fields");

        $config->getRpcDsn();
    }
    public function testDsn()
    {
        $config = new Config([
            'rpcuser' => 'username',
            'rpcpassword' => 'password',
            'rpcconnect' => '172.12.12.12',
        ]);

        $this->assertEquals("http://username:password@172.12.12.12:8332", $config->getRpcDsn());

        $config = new Config([
            'rpcuser' => 'u1',
            'rpcpassword' => 'p1',
            'testnet' => 1,
        ]);

        $this->assertEquals("http://u1:p1@127.0.0.1:18332", $config->getRpcDsn());

        $config = new Config([
            'rpcuser' => 'username',
            'rpcpassword' => 'password',
            'rpcconnect' => '127.0.0.1',
            'regtest' => 1,
        ]);

        $this->assertEquals("http://username:password@127.0.0.1:18443", $config->getRpcDsn());
    }
}
