<?php

namespace Hgraca\MicroOrm\Config;

use Hgraca\Helper\ClassHelper;
use Hgraca\Helper\StringHelper;
use Hgraca\MicroOrm\DataMapper\DataMapper;
use Hgraca\MicroOrm\DataMapper\DataMapperInterface;

class ArrayConfig implements ConfigInterface
{
    /** @var array */
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getDefaultDbName(): string
    {
        reset($this->config);

        return key($this->config);
    }

    public function getDataMapperFor(string $entityFqcn, string $dbName = ''): DataMapperInterface
    {
        $dbName = $dbName ? $dbName : $this->getDefaultDbName();

        $dataMapperArray = $this->config[$dbName]['entityDataMapper'][$entityFqcn] ?? [];

        $dataMapperArray['repositoryFqcn'] = $dataMapperArray['repositoryFqcn']
            ?? $this->config[$dbName]['repositoryFqcn']
            ?? StringHelper::replace(
                'Domain',
                'Persistence',
                StringHelper::replace('Entity', 'Repository', $entityFqcn)
            );

        $dataMapperArray['collectionFqcn'] = $dataMapperArray['collectionFqcn']
            ?? $this->config[$dbName]['collectionFqcn']
            ?? StringHelper::replace('Entity', 'Collection', $entityFqcn);

        $dataMapperArray['dateTimeFormat'] = $dataMapperArray['dateTimeFormat']
            ?? $this->config[$dbName]['dateTimeFormat']
            ?? 'Y-m-d H:i:s';

        $dataMapperArray['tableName'] = $dataMapperArray['tableName']
            ?? ClassHelper::extractCanonicalClassName($entityFqcn);

        $dataMapperArray['attributes'] = $dataMapperArray['attributes'] ?? [];

        return new DataMapper($entityFqcn, $dataMapperArray);
    }
}
