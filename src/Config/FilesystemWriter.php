<?php

declare(strict_types=1);

namespace BitWasp\Bitcoind\Config;

use BitWasp\Bitcoind\Exception\BitcoindException;

class FilesystemWriter extends Writer
{
    public function save(string $filePath, Config $config)
    {
        $file = "";
        foreach ($config->all() as $key => $option) {
            $file .= "{$key}={$option}\n";
        }
        if (!file_put_contents($filePath, $file)) {
            throw new BitcoindException("Failed to write config file");
        }
    }

    public function create(string $filePath, Config $config)
    {
        if (file_exists($filePath)) {
            throw new BitcoindException("Cannot overwrite existing files with FilesystemWriter::create");
        }

        return $this->save($filePath, $config);
    }
}
