<?php
namespace Hgraca\MicroOrm\DataSource;

use Hgraca\MicroOrm\DataSource\Exception\BindingMicroOrmException;
use Hgraca\MicroOrm\DataSource\Exception\ExecutionMicroOrmException;

interface CrudQueryBuilderInterface
{
    public function getCreateQuery(string $table, array $data): string;

    public function getReadQuery(
        string $table,
        array $filter = [],
        array $orderBy = [],
        int $limit = null,
        int $offset = 1
    ): string;

    public function getUpdateQuery(string $table, array $filter = [], array $data = []): string;

    public function getDeleteQuery(string $table, array $filter = []): string;
}
