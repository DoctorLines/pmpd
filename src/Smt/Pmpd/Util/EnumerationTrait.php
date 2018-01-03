<?php

namespace Smt\Pmpd\Util;

/**
 * @package Smt\Pmpd\Util
 * @author Roman Dmitrienko <doctorlines@gmail.com>
 */
trait EnumerationTrait
{
    private static $constants;

    private function __construct()
    {
        if (self::$constants === null) {
            $rc = new \ReflectionClass(self::class);
            self::$constants = $rc->getConstants();
        }
    }
    
    public static function all()
    {
        return self::$constants;
    }
}
