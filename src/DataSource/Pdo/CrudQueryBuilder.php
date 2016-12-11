<?php

namespace Hgraca\MicroOrm\DataSource\Pdo;

use Hgraca\MicroOrm\DataSource\CrudQueryBuilderInterface;
use Hgraca\MicroOrm\DataSource\Exception\BindingMicroOrmException;
use Hgraca\MicroOrm\DataSource\Exception\ExecutionMicroOrmException;
use Hgraca\MicroOrm\DataSource\Pdo\PdoClient;

final class CrudQueryBuilder implements CrudQueryBuilderInterface
{
    /**
     * @var PdoClient
     */
    private $pdoClient;

    public function __construct(PdoClient $pdoClient)
    {
        $this->pdoClient = $pdoClient;
    }

    /**
     * @param string $table
     * @param array  $data [$columnName => $value, ...]
     */
    public function getCreateQuery(string $table, array $data): string
    {
        $columnNamesArray = array_keys($data);
        $columnNamesList = '`' . implode('`, `', $columnNamesArray) . '`';
        $columnPlaceholdersList = implode(', ', array_fill(0, count($columnNamesArray), '?'));

        return "INSERT INTO `$table` ($columnNamesList) VALUES ($columnPlaceholdersList)";
    }

    /**
     * @throws BindingMicroOrmException
     * @throws ExecutionMicroOrmException
     */
    public function getReadQuery(string $table, array $filter = [], array $orderBy = [], int $limit = null, int $offset = 1): string
    {
        $sqlSelect = "SELECT * FROM `$table`";
        $sqlFilter = $this->createWhere($filter);
        $sqlOrderBy = $this->createOrderBy($orderBy);
        $sqlLimit = $this->createLimit($limit);
        $sqlOffset = $this->createOffset($offset);

        return $sqlSelect
            . ($sqlFilter ? ' ' . $sqlFilter : '')
            . ($sqlOrderBy ? ' ' . $sqlOrderBy : '')
            . ($sqlLimit ? ' ' . $sqlLimit : '')
            . ($sqlOffset ? ' ' . $sqlOffset : '');
    }

    /**
     * @param string $table
     * @param array  $data [$columnName => $value, ...]
     */
    public function getUpdateQuery(string $table, array $filter = [], array $data = []): string
    {
        $sqlUpdate = $this->createUpdate($table, $data);
        $sqlFilter = $this->createWhere($filter);

        return $sqlUpdate . ($sqlFilter ? ' ' . $sqlFilter : '');
    }

    /**
     * @return int The nbr of affected rows
     */
    public function getDeleteQuery(string $table, array $filter = []): string
    {
        $sqlDelete = "DELETE FROM `$table`";
        $sqlFilter = $this->createWhere($filter);

        return $sqlDelete . ($sqlFilter ? ' ' . $sqlFilter : '');
    }

    private function createUpdate(string $tableName, array $parameterList): string
    {
        $setColumnsList = [];
        foreach ($parameterList as $columnName => $columnValue) {
            $setColumnsList[] = '`' . $columnName . '`=?';
        }
        $setColumnsList = implode(', ', $setColumnsList);

        return "UPDATE `$tableName` SET $setColumnsList";
    }

    private function createWhere(array $filterByColumnNames = []): string
    {
        if (empty($filterByColumnNames)) {
            return '';
        }

        $sqlFilter = [];
        foreach ($filterByColumnNames as $columnName => $value) {
            $sqlFilter[] = $this->createColumnFilter($columnName, $value);
        }
        $sqlFilter = 'WHERE ' . implode(' AND ', $sqlFilter);

        return $sqlFilter;
    }

    private function createColumnFilter(string $columnName, $value)
    {
        if (null === $value) {
            return "`$columnName` IS ?";
        }

        if (is_array($value)) {
            $filter = [];
            foreach ($value as $filterName => $filterValue) {
                $filter[] = $this->createColumnFilter($columnName, $filterValue);
            }

            return empty($filter) ? '' : '(' . implode(' OR ', $filter) . ')';
        }

        return "`$columnName`=?";
    }

    private function createOrderBy(array $orderBy = []): string
    {
        if (empty($orderBy)) {
            return '';
        }

        $orderByItems = [];
        foreach ($orderBy as $column => $direction) {
            $orderByItems[] = $column . ' ' . $direction;
        }

        return 'ORDER BY ' . implode(', ', $orderByItems);
    }

    private function createLimit(int $limit = null): string
    {
        return $limit === null ? '' : "LIMIT $limit";
    }

    private function createOffset(int $offset = 1): string
    {
        return $offset === 1 ? '' : "OFFSET $offset";
    }
}
