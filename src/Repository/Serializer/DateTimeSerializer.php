<?php

namespace Hgraca\MicroOrm\Repository\Serializer;

use Hgraca\MicroOrm\Repository\Serializer\Contract\PropertySerializerInterface;

class DateTimeSerializer implements PropertySerializerInterface
{

    public static function serialize($data): string
    {
        return date_format($data, 'Y-m-d H:i:s');
    }

    public static function deserialize(string $data)
    {
        return date_create($data);
    }
}
