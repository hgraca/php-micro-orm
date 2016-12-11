<?php

namespace Hgraca\MicroOrm\DataSource;

interface CrudClientInterface
{
    public function create(string $table, array $data);

    public function read(string $table, array $filter = [], array $orderBy = [], int $limit = null, int $offset = 1): array;

    public function update(string $table, array $filter = [], array $data = []);

    public function delete(string $table, array $filter = []);
}
