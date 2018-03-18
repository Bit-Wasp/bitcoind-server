<?php

namespace BitWasp\Bitcoind\Utils;

use BitWasp\Bitcoind\Exception\BitcoindException;

class File
{
    public static function recursiveDelete(string $src)
    {
        if (!is_dir($src)) {
            throw new BitcoindException("Parameter 1 for recursiveDelete should be a directory");
        }

        $dir = opendir($src);
        while (false !== ( $file = readdir($dir))) {
            if (( $file != '.' ) && ( $file != '..' )) {
                $full = $src . '/' . $file;
                if (is_dir($full)) {
                    self::recursiveDelete($full);
                } else {
                    unlink($full);
                }
            }
        }
        closedir($dir);
        rmdir($src);
    }
}
