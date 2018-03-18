<?php

declare(strict_types=1);

namespace BitWasp\Test\Util;

use BitWasp\Bitcoind\Exception\BitcoindException;
use BitWasp\Bitcoind\Utils\File;
use BitWasp\Test\Bitcoind\TestCase;

class FileTest extends TestCase
{
    public function testRequiresDirectory()
    {
        $file = $this->registerTmpFile("tmpfile-requires-directory");
        file_put_contents($file, "");

        $this->expectException(BitcoindException::class);
        $this->expectExceptionMessage("Parameter 1 for recursiveDelete should be a directory");
        File::recursiveDelete($file);
    }
}
