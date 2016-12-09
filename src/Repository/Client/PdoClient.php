<?php
namespace Hgraca\MicroOrm\Repository\Client;

use Hgraca\Helper\StringHelper;
use Hgraca\MicroOrm\Repository\Client\Exception\BindingMicroOrmException;
use Hgraca\MicroOrm\Repository\Client\Exception\ExecutionMicroOrmException;
use Hgraca\MicroOrm\Repository\Client\Exception\TypeResolutionMicroOrmException;
use PDO;
use PDOStatement;

class PdoClient
{
    /** @var PDO */
    protected $pdo;

    /**
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param string $table
     * @param array  $filter
     * @param array  $orderBy
     * @param string $classFqcn
     *
     * @throws BindingMicroOrmException
     * @throws ExecutionMicroOrmException
     *
     * @return array
     */
    public function select(string $table, array $filter = [], array $orderBy = [], string $classFqcn = ''): array
    {
        $sqlSelect  = "SELECT * FROM `$table`";
        $sqlFilter  = $this->createSqlFilter($filter);
        $sqlOrderBy = $this->createSqlOrderBy($orderBy);

        $parameterBindings = $this->buildParameterBindings($filter, array_keys($filter));

        $sql = implode(' ', [$sqlSelect, $sqlFilter, $sqlOrderBy]);

        return $this->executeQuery($sql, $parameterBindings, $classFqcn);
    }

    /**
     * @param string $table
     * @param array  $data     [$columnName => $value, ...]
     * @param string $idColumn The name of the ID column
     *
     * @return int The nbr of affected rows
     */
    public function insert(string $table, array &$data, string $idColumn = 'id'): int
    {
        unset($data[$idColumn]);

        $columnNamesArray  = array_keys($data);
        $sql               = $this->createInsertSql($table, $columnNamesArray);
        $parameterBindings = $this->buildParameterBindings($data, $columnNamesArray);

        $statement = $this->execute($sql, $parameterBindings);

        $data[$idColumn] = $this->getLastInsertId();

        return $statement->rowCount();
    }

    /**
     * @param string $table
     * @param array  $data     [$columnName => $value, ...]
     * @param string $idColumn The name of the ID column
     *
     * @return int The nbr of affected rows
     */
    public function update(string $table, array &$data, string $idColumn = 'id'): int
    {
        $columnNamesArray  = array_keys($data);
        $sql               = $this->createUpdateSql($table, $columnNamesArray, $idColumn);
        $parameterBindings = $this->buildParameterBindings($data, $columnNamesArray);

        return $this->executeCommand($sql, $parameterBindings);
    }

    /**
     * @param string $table
     * @param array  $filter
     *
     * @return int The nbr of affected rows
     */
    public function delete(string $table, array $filter = [])
    {
        $sqlDelete = "DELETE FROM `$table`";
        $sqlFilter = $this->createSqlFilter($filter);
        $sql       = implode(' ', [$sqlDelete, $sqlFilter]);

        $columnNamesArray  = array_keys($filter);
        $parameterBindings = $this->buildParameterBindings($filter, $columnNamesArray);

        return $this->executeCommand($sql, $parameterBindings);
    }

    /**
     * @param string $sql
     * @param array  $parameterNameValueTypeList
     * @param string $classFqcn
     *
     * @throws BindingMicroOrmException
     * @throws ExecutionMicroOrmException
     *
     * @return array The result list
     */
    public function executeQuery(string $sql, array $parameterNameValueTypeList = [], string $classFqcn = ''): array
    {
        return $this->fetchData($this->execute($sql, $parameterNameValueTypeList), $classFqcn);
    }

    /**
     * @param string $sql
     * @param array  $parameterNameValueTypeList
     *
     * @throws BindingMicroOrmException
     * @throws ExecutionMicroOrmException
     *
     * @return int The nbr of affected rows
     */
    public function executeCommand(string $sql, array $parameterNameValueTypeList = []): int
    {
        return $this->execute($sql, $parameterNameValueTypeList)->rowCount();
    }

    /**
     * @param string $idName
     *
     * @return  string
     */
    public function getLastInsertId(string $idName = null): string
    {
        return $this->pdo->lastInsertId($idName);
    }

    /**
     * @param mixed $value
     *
     * @throws TypeResolutionMicroOrmException
     *
     * @return int
     */
    protected function resolvePdoType($value): int
    {
        $type = gettype($value);
        switch ($type) {
            case 'boolean':
                $pdoType = PDO::PARAM_BOOL;
                break;
            case 'string' :
                $pdoType = PDO::PARAM_STR;
                break;
            case 'integer':
            case 'double':
                $pdoType = PDO::PARAM_INT;
                break;
            case 'NULL':
                $pdoType = PDO::PARAM_NULL;
                break;
            case 'object':
                $class = get_class($value);
                throw new TypeResolutionMicroOrmException("Invalid type '$class' for query filter.");
            default:
                throw new TypeResolutionMicroOrmException("Invalid type '$type' for query filter.");
        }

        return $pdoType;
    }

    /**
     * @param string $sql
     * @param array  $parameterNameValueTypeList
     *
     * @throws BindingMicroOrmException
     * @throws ExecutionMicroOrmException
     *
     * @return PDOStatement
     */
    protected function execute(string $sql, array $parameterNameValueTypeList = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);

        foreach ($parameterNameValueTypeList as $nameValueTypeList) {
            $name  = ':' . $nameValueTypeList['name'];
            $value = $nameValueTypeList['value'];
            $type  = $nameValueTypeList['type'];

            $bound = $stmt->bindValue($name, $value, $type);

            if (false === $bound) {
                throw new BindingMicroOrmException("Could not bind value: " . json_encode($nameValueTypeList));
            }
        }

        $executed = $stmt->execute();
        if (false === $executed) {
            throw new ExecutionMicroOrmException(
                "Could not execute query: '$sql'"
                . " Error code: " . $stmt->errorCode()
                . " Error Info: " . json_encode($stmt->errorInfo())
            );
        }

        return $stmt;
    }

    protected function fetchData(PDOStatement $stmt, string $classFqcn = ''): array
    {
        if (! empty($classFqcn)) {
            return $stmt->fetchAll(PDO::FETCH_CLASS, $classFqcn);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function createSqlFilter(array $filterByColumnNames = []): string
    {
        if (empty($filterByColumnNames)) {
            return '';
        }

        $sqlFilter = [];
        foreach ($filterByColumnNames as $columnName => $value) {
            if (null === $value) {
                $sqlFilter[] = "`$columnName` IS :$columnName";
            } else {
                $sqlFilter[] = "`$columnName`=:$columnName";
            }
        }
        $sqlFilter = 'WHERE ' . implode(' AND ', $sqlFilter);

        return $sqlFilter;
    }

    protected function createSqlOrderBy(array $orderByColumnNames = []): string
    {
        if (empty($orderByColumnNames)) {
            return '';
        }

        return 'ORDER BY ' . implode(', ', $orderByColumnNames);
    }

    protected function createInsertSql(string $tableName, array $columnNamesArray): string
    {
        $columnNamesList        = "`" . implode("`, `", $columnNamesArray) . "`";
        $columnPlaceholdersList = ":" . implode(", :", $columnNamesArray);

        return "INSERT INTO `$tableName` ($columnNamesList) VALUES ($columnPlaceholdersList)";
    }

    protected function createUpdateSql(string $tableName, array $columnNamesArray, string $idColumn = 'id'): string
    {
        $setColumns = [];
        foreach ($columnNamesArray as $columnName) {
            $setColumns[] = '`' . $columnName . '`=:' . $columnName;
        }
        $setColumns = implode(', ', $setColumns);

        return "UPDATE `$tableName` SET $setColumns WHERE `$idColumn`=:$idColumn";
    }

    protected function buildParameterBindings(array $data, array $columnNamesArray): array
    {
        if (empty($data)) {
            return [];
        }

        $parameterBindings = [];
        foreach ($columnNamesArray as $columnName) {
            $parameterBindings[$columnName]['name']  = $columnName;
            $parameterBindings[$columnName]['value'] = $data[$columnName];
            $parameterBindings[$columnName]['type']  = $this->resolvePdoType($data[$columnName]);
        }

        return $parameterBindings;
    }

    protected function isSelect(string $sql): bool
    {
        return StringHelper::hasBeginning('SELECT', $sql);
    }
}
