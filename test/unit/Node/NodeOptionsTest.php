<?php

declare(strict_types=1);

namespace BitWasp\Test\Bitcoind\Node;

use BitWasp\Bitcoind\Config\Config;
use BitWasp\Bitcoind\Node\NodeOptions;
use BitWasp\Test\Bitcoind\TestCase;

class NodeOptionsTest extends TestCase
{
    public function testBasicOptions()
    {
        $bitcoindPath = "/usr/bin/bitcoind";
        $dataDir = "/data/";
        $options = new NodeOptions(
            $bitcoindPath,
            $dataDir
        );

        $this->assertEquals($bitcoindPath, $options->getBitcoindPath());
        $this->assertEquals($dataDir, $options->getDataDir());
        $this->assertEquals("bitcoin.conf", $options->getConfigFileName());
        $this->assertEquals("{$dataDir}bitcoin.conf", $options->getAbsoluteConfigPath());
        $this->assertEquals("{$bitcoindPath} -datadir={$dataDir}", $options->getStartupCommand());
        $this->assertFalse($options->hasConfig());
    }

    public function testDefaultConfigFile()
    {
        $bitcoindPath = "/usr/bin/bitcoind";
        $dataDir = "/data";
        $options = new NodeOptions(
            $bitcoindPath,
            $dataDir
        );

        $this->assertEquals("bitcoin.conf", $options->getConfigFileName());
        $this->assertEquals("{$dataDir}/", $options->getDataDir());
    }

    public function testOverrideConfigFile()
    {
        $bitcoindPath = "/usr/bin/bitcoind";
        $dataDir = "/data";
        $options = new NodeOptions(
            $bitcoindPath,
            $dataDir
        );
        $options->withConfigFileName("litecoin.conf");

        $this->assertEquals("{$dataDir}/", $options->getDataDir());
        $this->assertEquals("litecoin.conf", $options->getConfigFileName());
        $this->assertEquals("{$dataDir}/litecoin.conf", $options->getAbsoluteConfigPath());
    }

    public function testHasConfig()
    {
        $bitcoindPath = "/usr/bin/bitcoind";
        $datadir = sys_get_temp_dir()."/testdir/";
        $configpath = $datadir."bitcoin.conf";
        @mkdir($datadir);

        file_put_contents($configpath, "");

        $options = new NodeOptions(
            $bitcoindPath,
            $datadir
        );
        $this->assertTrue($options->hasConfig());
        unlink($configpath);
    }

    public function testGetPidPath()
    {
        $bitcoindPath = "/usr/bin/bitcoind";
        $datadir = sys_get_temp_dir()."/testdir/";
        $configpath = $datadir."bitcoin.conf";
        @mkdir($datadir);

        file_put_contents($configpath, "");

        $options = new NodeOptions(
            $bitcoindPath,
            $datadir
        );

        $mainnetConfig = new Config();
        $this->assertEquals("{$datadir}bitcoind.pid", $options->getAbsolutePidPath($mainnetConfig));

        $testnetConfig = new Config([
            'testnet' => 1
        ]);
        $this->assertEquals("{$datadir}testnet3/bitcoind.pid", $options->getAbsolutePidPath($testnetConfig));

        $regtestConfig = new Config([
            'regtest' => 1
        ]);
        $this->assertEquals("{$datadir}regtest/bitcoind.pid", $options->getAbsolutePidPath($regtestConfig));
        unlink($configpath);
    }
}
