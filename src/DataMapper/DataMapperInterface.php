<?php

namespace Hgraca\MicroOrm\DataMapper;

use Hgraca\Common\Collection\Contract\CollectionInterface;
use Hgraca\Common\Entity\Contract\EntityInterface;

interface DataMapperInterface
{
    //    public function getEntityFqcn(): string;
//
//    public function getCollectionFqcn(): string;

    public function getTableName(): string;

    public function getRepositoryFqcn(): string;

    public function getPropertyToColumnMapper(): array;

//    public function getColumnToPropertyMapper(): array;
//
//    public function getPropertyTypeMapper(): array;
//
//    public function getPropertyName(string $columnName): string;
//
//    public function getPropertyType($propertyName): string;
//
//    public function getColumnName(string $propertyName): string;

    public function mapEntityToRecord(EntityInterface $entity): array;

    public function mapPropertiesToColumns(array $properties): array;

    public function mapRecordToEntity(array $record): EntityInterface;

    public function mapRecordListToEntityCollection(array $recordList): CollectionInterface;
}
