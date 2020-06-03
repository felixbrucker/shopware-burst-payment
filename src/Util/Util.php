<?php declare(strict_types=1);

namespace Burst\BurstPayment\Util;

class Util
{
    public static function arrayFind(array $array, callable $callable)
    {
        foreach ($array as $value) {
            if (call_user_func($callable, $value) === true) {
                return $value;
            }
        }
        return null;
    }
}
