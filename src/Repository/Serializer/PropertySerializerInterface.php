<?php

namespace Hgraca\MicroOrm\Repository\Serializer;

interface PropertySerializerInterface
{
    public static function serialize($data): string;

    public static function deserialize(string $data);
}
