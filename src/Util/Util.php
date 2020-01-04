<?php

namespace Burst\BurstPayment\Util;

class Util
{
    public static function array_find(array $array, callable $callable) {
        foreach ($array as $value) {
            if (call_user_func($callable, $value) === true) {
                return $value;
            }
        }
        return null;
    }
}
