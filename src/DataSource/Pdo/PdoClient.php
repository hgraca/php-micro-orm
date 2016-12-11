<?php

namespace Hgraca\MicroOrm\DataSource\Pdo;

use Exception;
use Hgraca\Helper\ArrayHelper;
use Hgraca\MicroOrm\DataSource\ClientInterface;
use Hgraca\MicroOrm\DataSource\Exception\BindingException;
use Hgraca\MicroOrm\DataSource\Exception\ExecutionException;
use Hgraca\MicroOrm\DataSource\Exception\TypeResolutionException;
use PDO;
use PDOStatement;

final class PdoClient implements ClientInterface
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function executeQuery(string $sql): array
    {
        return $this->execute($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function executeCommand(string $sql, array $bindingsList = [])
    {
        if (!ArrayHelper::isTwoDimensional($bindingsList)) {
            $bindingsList = [$bindingsList];
        }

        try {
            $this->pdo->beginTransaction();
            foreach ($bindingsList as $bindings) {
                $preparedStatement = $this->execute($sql, $bindings, $preparedStatement ?? null);
            }
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @throws ExecutionException
     */
    private function execute(string $sql, array $bindings = [], PDOStatement $preparedStatement = null): PDOStatement
    {
        $preparedStatement = $preparedStatement ?? $this->pdo->prepare($sql);

        $this->bindParameterList($preparedStatement, $bindings);

        if (!$preparedStatement->execute()) {
            throw new ExecutionException(
                "Could not execute query: '$sql'"
                . ' Error code: ' . $preparedStatement->errorCode()
                . ' Error Info: ' . json_encode($preparedStatement->errorInfo())
            );
        }

        return $preparedStatement;
    }

    private function bindParameterList(PDOStatement $stmt, array $parameterList)
    {
        foreach ($parameterList as $name => $value) {
            if (is_array($value)) {
                /** @var array $value */
                foreach ($value as $filterName => $filterValue) {
                    $this->bindParameter($stmt, $filterName, $filterValue);
                }
            } else {
                $this->bindParameter($stmt, $name, $value);
            }
        }
    }

    /**
     * @throws BindingException
     */
    private function bindParameter(PDOStatement $stmt, string $name, $value)
    {
        $pdoType = $this->resolvePdoType($value);
        $bound = $stmt->bindValue(
            ':' . $name,
            $pdoType === PDO::PARAM_STR ? strval($value) : $value,
            $pdoType
        );

        if (false === $bound) {
            throw new BindingException(
                'Could not bind value: ' . json_encode(['name' => $name, 'value' => $value, 'type' => $pdoType])
            );
        }
    }

    /**
     * @param mixed $value
     *
     * @throws TypeResolutionException
     */
    private function resolvePdoType($value): int
    {
        $type = gettype($value);
        switch ($type) {
            case 'boolean':
                $pdoType = PDO::PARAM_BOOL;
                break;
            case 'string':
            case 'double': // float
                $pdoType = PDO::PARAM_STR;
                break;
            case 'integer':
                $pdoType = PDO::PARAM_INT;
                break;
            case 'NULL':
                $pdoType = PDO::PARAM_NULL;
                break;
            case 'object':
                $class = get_class($value);
                throw new TypeResolutionException("Invalid type '$class' for query filter.");
            default:
                throw new TypeResolutionException("Invalid type '$type' for query filter.");
        }

        return $pdoType;
    }
}
