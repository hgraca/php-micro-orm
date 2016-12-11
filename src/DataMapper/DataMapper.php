<?php

namespace Hgraca\MicroOrm\DataMapper;

use DateTime;
use Hgraca\Common\Collection\Contract\CollectionInterface;
use Hgraca\Common\Entity\Concept\EntityAbstract;
use Hgraca\Common\Entity\Contract\EntityInterface;
use Hgraca\Helper\ClassHelper;
use Hgraca\MicroOrm\Repository\RepositoryInterface;
use ReflectionProperty;

class DataMapper implements DataMapperInterface
{
    /** @var EntityAbstract|string */
    private $entityFqcn;

    /** @var CollectionInterface|string */
    private $collectionFqcn;

    /** @var string */
    private $tableName;

    /** @var RepositoryInterface|string */
    private $repositoryFqcn;

    /** @var array */
    private $attributes;

    /** @var array */
    private $propertyToColumnMapper;

    /** @var array */
    private $propertyTypeMapper;

    /** @var ReflectionProperty[] */
    private $reflectionPropertyList;

    /** @var string */
    private $dateTimeFormat;

    public function __construct(
        string $entityFqcn,
        array $config
    ) {
        $this->entityFqcn = $entityFqcn;
        $this->repositoryFqcn = $config['repositoryFqcn'];
        $this->collectionFqcn = $config['collectionFqcn'];
        $this->dateTimeFormat = $config['dateTimeFormat'];
        $this->tableName = $config['tableName'];
        $this->attributes = $config['attributes'];
    }

    private function getEntityFqcn(): string
    {
        return $this->entityFqcn;
    }

    private function getCollectionFqcn(): string
    {
        return $this->collectionFqcn;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @return RepositoryInterface|string
     */
    public function getRepositoryFqcn(): string
    {
        return $this->repositoryFqcn;
    }

    public function mapRecordListToEntityCollection(array $recordList): CollectionInterface
    {
        $entityResultList = [];
        foreach ($recordList as $record) {
            $entityResultList[] = $this->mapRecordToEntity($record);
        }

        $collectionFqcn = $this->getCollectionFqcn();

        return new $collectionFqcn($entityResultList);
    }

    public function mapEntityToRecord(EntityInterface $entity): array
    {
        $entityFqcn = $this->getEntityFqcn();
        $reflectionPropertyList = $this->getReflectionPropertyList($entityFqcn);

        $record = [];
        foreach ($reflectionPropertyList as $reflectionProperty) {
            $propertyName = $reflectionProperty->getName();
            $propertyValue = $reflectionProperty->getValue($entity);

            $record += $this->mapPropertyToColumn($propertyName, $propertyValue);
        }

        return $record;
    }

    public function mapPropertiesToColumns(array $propertyList): array
    {
        $columnList = [];
        foreach ($propertyList as $propertyName => $propertyValue) {
            $columnList += $this->mapPropertyToColumn($propertyName, $propertyValue);
        }

        return $columnList;
    }

    public function mapRecordToEntity(array $record): EntityInterface
    {
        $entity = $this->createEntity();

        $this->updateEntityFromRecord($entity, $record);

        return $entity;
    }

    public function mapColumnsToProperties(array $columnList): array
    {
        $propertyList = [];

        foreach ($columnList as $columnName => $columnValue) {
            $propertyList += $this->mapColumnToProperty($columnName, $columnValue);
        }

        return $propertyList;
    }

    public function updateEntityFromRecord(EntityInterface $entity, array $record)
    {
        $reflectionPropertyList = $this->getReflectionPropertyList($this->getEntityFqcn());
        $newPropertyValueArray = $this->mapColumnsToProperties($record);

        foreach ($reflectionPropertyList as $reflectionProperty) {
            $propertyName = $reflectionProperty->getName();

            if (!isset($newPropertyValueArray[$propertyName])) {
                continue;
            }

            $reflectionProperty->setValue($entity, $newPropertyValueArray[$propertyName]);
        }
    }

    public function getPropertyToColumnMapper(): array
    {
        if (null !== $this->propertyToColumnMapper) {
            return $this->propertyToColumnMapper;
        }

        $this->propertyToColumnMapper = [];
        foreach ($this->attributes as $attributeName => $attributeMetadata) {
            $this->propertyToColumnMapper[$attributeName] = $attributeMetadata['column'] ?? $attributeName;
        }

        return $this->propertyToColumnMapper;
    }

    /**
     * @param string $entityFqcn
     *
     * @return ReflectionProperty[]
     */
    private function getReflectionPropertyList(string $entityFqcn): array
    {
        if (null !== $this->reflectionPropertyList) {
            return $this->reflectionPropertyList;
        }

        $this->reflectionPropertyList = ClassHelper::getReflectionProperties($entityFqcn);
        ClassHelper::setReflectionPropertiesAccessible($this->reflectionPropertyList);

        return $this->reflectionPropertyList;
    }

    private function mapColumnValueToPropertyValue($value, string $type)
    {
        // TODO use a serializer service for this, so we can have ValueObjects
        switch ($type) {
            case 'datetime':
                return DateTime::createFromFormat($this->dateTimeFormat, $value);

            case 'text':
                settype($value, 'string');

                return $value;

            default:
                settype($value, $type);

                return $value;
        }
    }

    private function mapPropertyValueToColumnValue($value, string $type)
    {
        if (null === $value) {
            return $value;
        }

        // TODO use a serializer service for this, so we can have ValueObjects
        switch ($type) {
            case 'datetime':
                return date_format($value, $this->dateTimeFormat);

            case 'text':
                settype($value, 'string');

                return $value;

            default:
                settype($value, $type);

                return $value;
        }
    }

    private function createEntity(): EntityInterface
    {
        $entityFqcn = $this->getEntityFqcn();

        return new $entityFqcn();
    }

    private function mapPropertyToColumn(string $propertyName, $propertyValue): array
    {
        $propertyType = $this->getPropertyType($propertyName);
        $columnName = $this->getColumnName($propertyName);

        return [$columnName => $this->mapPropertyValueToColumnValue($propertyValue, $propertyType)];
    }

    private function mapColumnToProperty(string $columnName, $columnValue): array
    {
        $propertyName = $this->getPropertyName($columnName);
        $propertyType = $this->getPropertyType($propertyName);

        return [$propertyName => $this->mapColumnValueToPropertyValue($columnValue, $propertyType)];
    }

    private function getColumnToPropertyMapper(): array
    {
        return array_flip($this->getPropertyToColumnMapper());
    }

    private function getPropertyTypeMapper(): array
    {
        if (null !== $this->propertyTypeMapper) {
            return $this->propertyTypeMapper;
        }

        $this->propertyTypeMapper = [];
        foreach ($this->attributes as $attributeName => $attributeMetadata) {
            $this->propertyTypeMapper[$attributeName] = $attributeMetadata['type'] ?? 'string';
        }

        return $this->propertyTypeMapper;
    }

    private function getPropertyType($propertyName): string
    {
        $propertyTypeMapper = $this->getPropertyTypeMapper();

        return $propertyTypeMapper[$propertyName] ?? 'string';
    }

    private function getColumnName(string $propertyName): string
    {
        $propertyToColumnMapper = $this->getPropertyToColumnMapper();

        return $propertyToColumnMapper[$propertyName] ?? $propertyName;
    }

    private function getPropertyName(string $columnName): string
    {
        $columnToPropertyMapper = $this->getColumnToPropertyMapper();

        return $columnToPropertyMapper[$columnName] ?? $columnName;
    }
}
