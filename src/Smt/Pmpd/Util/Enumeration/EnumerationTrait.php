<?php

namespace Smt\Util\Enumeration;

/**
 * 
 * 
 * @package Smt\Pmpd\Util
 * @author Roman Dmitrienko <doctorlines@gmail.com>
 */
trait EnumerationTrait
{
    public static function all()
    {
        $rc = new \ReflectionClass(self::class);
        return $rc->getConstants();
    }
}
