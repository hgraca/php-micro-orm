<?php

namespace Hgraca\MicroOrm\DataSource;

final class CrudClient implements CrudClientInterface
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var CrudQueryBuilderInterface
     */
    private $crudQueryBuilder;

    public function __construct(ClientInterface $client, CrudQueryBuilderInterface $crudQueryBuilder)
    {
        $this->client = $client;
        $this->crudQueryBuilder = $crudQueryBuilder;
    }

    public function create(string $table, array $data)
    {
        // TODO implement the DataSet class, which can have 2 dimensions, and the innermost can have AndSets and OrSets
        $normalizedData = new DataSet($data);
        $queryString = $this->crudQueryBuilder->getCreateQuery($table, $normalizedData);
        $this->client->executeCommand($queryString, $normalizedData);
    }

    public function read(
        string $table,
        array $filter = [],
        array $orderBy = [],
        int $limit = null,
        int $offset = 1
    ): array {
        // TODO: Implement read() method.
    }

    public function update(string $table, array $filter = [], array $data = [])
    {
        // TODO: Implement update() method.
    }

    public function delete(string $table, array $filter = [])
    {
        // TODO: Implement delete() method.
    }
}
