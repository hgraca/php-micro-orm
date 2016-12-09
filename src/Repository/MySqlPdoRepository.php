<?php
namespace Hgraca\MicroOrm\Repository;

use Hgraca\Common\Collection\Contract\CollectionInterface;
use Hgraca\Common\Entity\Concept\EntityAbstract;
use Hgraca\Common\Http\HttpStatusCode;
use Hgraca\MicroOrm\Entity\Contract\EntityDataMapperInterface;
use Hgraca\MicroOrm\Entity\EntityDataMapper;
use Hgraca\MicroOrm\Repository\Client\PdoClient;
use Hgraca\MicroOrm\Repository\Contract\RepositoryInterface;
use Hgraca\MicroOrm\Repository\Exception\EntityNotFoundMicroOrmException;
use Hgraca\MicroOrm\Repository\Exception\RepositoryMicroOrmException;

class MySqlPdoRepository implements RepositoryInterface
{
    /** @var  PdoClient */
    protected $pdoClient;

    /** @var  EntityDataMapperInterface */
    protected $entityDataMapper;

    public function __construct(
        PdoClient $pdoClient,
        EntityDataMapper $entityDataMapper
    ) {
        $this->pdoClient        = $pdoClient;
        $this->entityDataMapper = $entityDataMapper;
    }

    /**
     * {@inheritdoc}
     *
     * TODO implement $orderBy, $limit and $offset
     */
    public function findAll(): CollectionInterface
    {
        return $this->findBy([]);
    }

    /**
     * {@inheritdoc}
     */
    public function findOneById(int $id): EntityAbstract
    {
        return $this->findOneBy([static::ID_PROPERTY_NAME => $id]);
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $filter): EntityAbstract
    {
        $resultArray = $this->findBy($filter, []);

        $count = count($resultArray);

        $msg = "Should find exactly one entity and found $count.";

        if (0 === $count) {
            $statusCode = new HttpStatusCode(HttpStatusCode::NOT_FOUND);
            throw new EntityNotFoundMicroOrmException($msg, $statusCode->getValue());
        }

        if (1 < $count) {
            $statusCode = new HttpStatusCode(HttpStatusCode::INTERNAL_SERVER_ERROR);
            throw new RepositoryMicroOrmException($msg, $statusCode->getValue());
        }

        return $resultArray[0];
    }

    /**
     * {@inheritdoc}
     *
     * TODO implement $limit and $offset
     */
    public function findBy(
        array $propertyFilter,
        array $orderBy = [],
        int $limit = null,
        int $offset = null
    ): CollectionInterface
    {
        $tableName    = $this->entityDataMapper->getTableName();
        $columnFilter = $this->entityDataMapper->mapPropertiesToColumns($propertyFilter);
        $recordList   = $this->pdoClient->select($tableName, $columnFilter, $orderBy);

        return $this->entityDataMapper->mapRecordListToEntityCollection($recordList);
    }

    /**
     * {@inheritdoc}
     */
    public function persist(EntityAbstract $entity): int
    {
        $record = $this->entityDataMapper->mapEntityToRecord($entity);

        // if the entity has an ID set, it will update, otherwise it will insert
        $id = $entity->getId();
        if (empty($id)) {
            $affectedRows = $this->pdoClient->insert($this->entityDataMapper->getTableName(), $record);
        } else {
            $affectedRows = $this->pdoClient->update($this->entityDataMapper->getTableName(), $record);
        }

        $this->entityDataMapper->updateEntityFromRecord($entity, $record);

        return $affectedRows;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById(int $id): int
    {
        return $this->deleteBy([static::ID_PROPERTY_NAME => $id]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteBy(array $propertyFilter): int
    {
        $columnFilter = $this->entityDataMapper->mapPropertiesToColumns($propertyFilter);

        return $this->pdoClient->delete($this->entityDataMapper->getTableName(), $columnFilter);
    }

    /**
     * @param EntityAbstract $entity
     *
     * @return int The nbr of affected rows
     */
    public function delete(EntityAbstract $entity): int
    {
        return $this->deleteById($entity->getId());
    }
}
