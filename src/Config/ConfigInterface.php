<?php

namespace Hgraca\MicroOrm\Config;

use Hgraca\MicroOrm\DataMapper\DataMapperInterface;

interface ConfigInterface
{
    public function getDefaultDbName(): string;

    public function getDataMapperFor(string $entityFqcn, string $dbName = ''): DataMapperInterface;
}
