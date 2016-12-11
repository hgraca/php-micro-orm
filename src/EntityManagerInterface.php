<?php

namespace Hgraca\MicroOrm;

use Hgraca\MicroOrm\DataSource\Exception\BindingMicroOrmException;
use Hgraca\MicroOrm\DataSource\Exception\ExecutionMicroOrmException;

interface EntityManagerInterface
{
    public function persist($entity);

    public function find(
        string $entityType,
        array $propertyFilter,
        array $orderBy = [],
        int $limit = null,
        int $offset = 1
    );

    public function delete($entity);
}
