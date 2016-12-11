<?php

namespace Hgraca\MicroOrm;

use Hgraca\MicroOrm\DataSource\ClientInterface;
use Hgraca\MicroOrm\DataSource\CrudQueryBuilderInterface;
use Hgraca\MicroOrm\DataMapper\DataMapperInterface;
use Hgraca\MicroOrm\Registry\EntityRegistryInterface;

final class EntityManager
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var CrudQueryBuilderInterface
     */
    private $crudQueryBuilder;

    /**
     * @var DataMapperInterface
     */
    private $dataMapper;

    /**
     * @var EntityRegistryInterface
     */
    private $entityRegistry;

    public function __construct(
        ClientInterface $client,
        CrudQueryBuilderInterface $crudQueryBuilder,
        DataMapperInterface $dataMapper,
        EntityRegistryInterface $entityRegistry
    ) {
        $this->client = $client;
        $this->crudQueryBuilder = $crudQueryBuilder;
        $this->dataMapper = $dataMapper;
        $this->entityRegistry = $entityRegistry;
    }

    public function __destruct()
    {
        $this->flush();
    }

    public function persist($entity)
    {
        if (!$this->entityRegistry->contains($entity)) {
            $this->entityRegistry->markForCreation($entity);
        }
    }

    public function find(
        string $entityType,
        array $propertyFilter,
        array $orderBy = [],
        int $limit = null,
        int $offset = 1
    ) {
        $tableName = $this->dataMapper->getTableName($entityType);

        $columnFilter = $this->dataMapper->mapPropertiesToColumns($propertyFilter);

        $recordList = $this->client->executeQuery(
            $this->crudQueryBuilder->getReadQuery($tableName, $columnFilter, $orderBy, $limit, $offset),
            $columnFilter
        );

        $entityList = $this->dataMapper->inflateRecordListToEntityList($recordList);

        // pass as reference so we can replace the entities for the ones already existing in the entityRegistry
        // create a snapshot of the entities new in the registry
        $this->entityRegistry->registerEntityList($entityList);

        return $this->dataMapper->inflateEntityListToCollection($entityList);
    }

    public function delete($entity)
    {
        $this->entityRegistry->markForDeletion($entity);
    }

    private function flush()
    {
        // Create entities marked for creation
        $newEntitiesList = $this->entityRegistry->getNewEntities();
        foreach ($newEntitiesList as $newEntity) {
            $record = $this->dataMapper->deflateEntityToRecord($newEntity);
            $this->crudQueryBuilder->getCreateQuery($record);
        }
        //      $record = $this->dataMapper->deflateEntityToRecord($entity);

        // Update entities marked for update
        //      $record = $this->dataMapper->deflateEntityToRecord($entity);
        //      only update the fields changed, use the snapshot to know which

        // Delete entities marked for deletion
    }
}
