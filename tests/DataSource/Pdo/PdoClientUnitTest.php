<?php

namespace Hgraca\MicroOrm\Test\Repository\Client;

use Hgraca\MicroOrm\DataSource\Pdo\PdoClient;
use Hgraca\MicroOrm\Test\Stub\Foo;
use Mockery;
use Mockery\MockInterface;
use PDO;
use PDOStatement;
use PHPUnit_Framework_TestCase;

final class PdoClientUnitTest extends PHPUnit_Framework_TestCase
{
    /** @var MockInterface|PDO */
    private $pdo;

    /** @var PdoClient */
    private $client;

    /**
     * @before
     */
    protected function setUpClient()
    {
        $this->pdo = Mockery::mock(PDO::class);
        $this->pdo->shouldReceive('setAttribute');
        $this->client = new PdoClient($this->pdo);
    }

    /**
     * @test
     *
     * @small
     */
    public function executeQuery()
    {
        $sql = 'some dummy sql';
        $parameterList = [
            'trueVal' => true,
        ];

        $pdoStatementMock = Mockery::mock(PDOStatement::class);
        $this->pdo->shouldReceive('prepare')->once()->with($sql)->andReturn($pdoStatementMock);

        $pdoStatementMock->shouldReceive('execute')->once()->andReturn(true);
        $pdoStatementMock->shouldReceive('fetchAll')->once()->with(PDO::FETCH_ASSOC)->andReturn(
            $expectedResult = [
                ['propA' => 1, 'propB' => 2],
                ['propA' => 3, 'propB' => 4],
            ]
        );

        self::assertEquals($expectedResult, $this->client->executeQuery($sql, $parameterList));
    }

    /**
     * @test
     *
     * @small
     *
     * @expectedException \Hgraca\MicroOrm\DataSource\Exception\ExecutionException
     */
    public function executeQuery_ShouldThrowException()
    {
        $sql = 'some dummy sql';
        $parameterList = [
            'trueVal' => true,
        ];

        $pdoStatementMock = Mockery::mock(PDOStatement::class);
        $this->pdo->shouldReceive('prepare')->once()->with($sql)->andReturn($pdoStatementMock);

        $pdoStatementMock->shouldReceive('execute')->once()->andReturn(false);
        $pdoStatementMock->shouldReceive('errorCode')->once()->andReturn('123');
        $pdoStatementMock->shouldReceive('errorInfo')->once()->andReturn(['some error info']);

        $this->client->executeQuery($sql, $parameterList);
    }

    /**
     * @test
     *
     * @small
     */
    public function executeCommand()
    {
        $sql = 'some dummy sql';
        $parameterList = [
            'trueVal' => true,
            'stringVal' => 'some string',
            'intVal' => 1,
            'floatVal' => 1.5,
            'nullVal' => null,
        ];

        $pdoStatementMock = Mockery::mock(PDOStatement::class);
        $this->pdo->shouldReceive('prepare')->once()->with($sql)->andReturn($pdoStatementMock);
        $this->pdo->shouldReceive('beginTransaction')->once();
        $this->pdo->shouldReceive('commit')->once();

        $pdoStatementMock->shouldReceive('bindValue')
            ->once()
            ->with(':trueVal', $parameterList['trueVal'], PDO::PARAM_BOOL)
            ->andReturn(true);
        $pdoStatementMock->shouldReceive('bindValue')
            ->once()
            ->with(':stringVal', $parameterList['stringVal'], PDO::PARAM_STR)
            ->andReturn(true);
        $pdoStatementMock->shouldReceive('bindValue')
            ->once()
            ->with(':intVal', $parameterList['intVal'], PDO::PARAM_INT)
            ->andReturn(true);
        $pdoStatementMock->shouldReceive('bindValue')
            ->once()
            ->with(':floatVal', strval($parameterList['floatVal']), PDO::PARAM_STR)
            ->andReturn(true);
        $pdoStatementMock->shouldReceive('bindValue')
            ->once()
            ->with(':nullVal', $parameterList['nullVal'], PDO::PARAM_NULL)
            ->andReturn(true);
        $pdoStatementMock->shouldReceive('execute')->once()->andReturn(true);

        $this->client->executeCommand($sql, $parameterList);
    }

    /**
     * @test
     *
     * @small
     *
     * @expectedException \Hgraca\MicroOrm\DataSource\Exception\BindingException
     */
    public function executeCommand_ShouldThrowExceptionIfCantBind()
    {
        $sql = 'some dummy sql';
        $filterList = [
            'trueVal' => true,
        ];

        $pdoStatementMock = Mockery::mock(PDOStatement::class);
        $this->pdo->shouldReceive('prepare')->once()->with($sql)->andReturn($pdoStatementMock);
        $this->pdo->shouldReceive('beginTransaction')->once();
        $this->pdo->shouldReceive('rollBack')->once();

        $pdoStatementMock->shouldReceive('bindValue')
            ->once()
            ->with(':trueVal', $filterList['trueVal'], PDO::PARAM_BOOL)
            ->andReturn(false);

        $this->client->executeCommand($sql, $filterList);
    }

    /**
     * @test
     *
     * @small
     *
     * @expectedException \Hgraca\MicroOrm\DataSource\Exception\ExecutionException
     *
     * @covers \Hgraca\MicroOrm\DataSource\Pdo\PdoClient::__construct
     * @covers \Hgraca\MicroOrm\DataSource\Pdo\PdoClient::executeCommand
     * @covers \Hgraca\MicroOrm\DataSource\Pdo\PdoClient::execute
     */
    public function executeCommand_ShouldThrowExceptionIfCantExecute()
    {
        $sql = 'some dummy sql';
        $parameterList = [
            'trueVal' => true,
        ];

        $pdoStatementMock = Mockery::mock(PDOStatement::class);
        $this->pdo->shouldReceive('prepare')->once()->with($sql)->andReturn($pdoStatementMock);
        $this->pdo->shouldReceive('beginTransaction')->once();
        $this->pdo->shouldReceive('rollBack')->once();

        $pdoStatementMock->shouldReceive('bindValue')
            ->once()
            ->with(':trueVal', $parameterList['trueVal'], PDO::PARAM_BOOL)
            ->andReturn(true);
        $pdoStatementMock->shouldReceive('execute')->once()->andReturn(false);
        $pdoStatementMock->shouldReceive('errorCode')->once()->andReturn('123');
        $pdoStatementMock->shouldReceive('errorInfo')->once()->andReturn(['some error info']);

        $this->client->executeCommand($sql, $parameterList);
    }

    /**
     * @test
     *
     * @small
     *
     * @expectedException \Hgraca\MicroOrm\DataSource\Exception\TypeResolutionException
     */
    public function executeCommand_ShouldThrowExceptionIfCantResolvePdoType()
    {
        $sql = 'some dummy sql';
        $parameterList = [
            'trueVal' => new Foo(),
        ];

        $pdoStatementMock = Mockery::mock(PDOStatement::class);
        $this->pdo->shouldReceive('prepare')->once()->with($sql)->andReturn($pdoStatementMock);
        $this->pdo->shouldReceive('beginTransaction')->once();
        $this->pdo->shouldReceive('rollBack')->once();
        $pdoStatementMock->shouldNotReceive('bindValue');

        $this->client->executeCommand($sql, $parameterList);
    }
}
