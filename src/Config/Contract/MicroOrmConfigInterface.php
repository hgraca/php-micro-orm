<?php
namespace Hgraca\MicroOrm\Config\Contract;

use Hgraca\MicroOrm\Entity\Contract\EntityDataMapperInterface;

interface MicroOrmConfigInterface
{
    public function getDataMapperFor(string $entityFqcn, string $dbName = ''): EntityDataMapperInterface;
}
