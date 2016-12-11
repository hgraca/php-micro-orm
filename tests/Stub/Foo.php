<?php

namespace Hgraca\MicroOrm\Test\Stub;

final class Foo
{
    const PATTERN = 'Hello %s, from Foo!';

    public static function test($name)
    {
        return sprintf(self::PATTERN, $name);
    }
}
