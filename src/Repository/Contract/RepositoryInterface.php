<?php
namespace Hgraca\MicroOrm\Repository\Contract;

use Hgraca\Common\Collection\Contract\CollectionInterface;
use Hgraca\Common\Entity\Concept\EntityAbstract;

interface RepositoryInterface
{
    const ID_PROPERTY_NAME = 'id';
    const ID_PROPERTY_TYPE = 'integer';

    public function findAll(): CollectionInterface;

    public function findOneById(int $id): EntityAbstract;

    public function findOneBy(array $filter): EntityAbstract;

    public function findBy(array $filter, array $orderBy = [], int $limit = null, int $offset = null): CollectionInterface;

    /**
     * Saves entities to the DB.
     * If the entity has the ID set it will update, otherwise it will insert.
     * If an ID is given but its not found, it will throw a NotFoundExceptions
     *
     * @param EntityAbstract $entity
     *
     * @return int The nbr of affected rows
     */
    public function persist(EntityAbstract $entity): int;

    /**
     * @param int $id
     *
     * @return int The nbr of affected rows
     */
    public function deleteById(int $id): int;

    /**
     * @param array $filter
     *
     * @return int The nbr of affected rows
     */
    public function deleteBy(array $filter): int;

    /**
     * @param EntityAbstract $entity
     *
     * @return int The nbr of affected rows
     */
    public function delete(EntityAbstract $entity): int;
}
