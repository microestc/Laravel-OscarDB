<?php

use Microestc\OscarDB\OscarConnection;
use Microestc\OscarDB\Query\OscarBuilder;
use Mockery as m;
use PHPUnit\Framework\TestCase;

include 'mocks/ACIMocks.php';

class OscarDBACIProcessorTest extends TestCase
{
    protected function setUp(): void
    {
        if (! extension_loaded('aci')) {
            $this->markTestSkipped('The oscar extension is not available.');
        }
    }

    public function tearDown(): void
    {
        m::close();
    }

    public function testInsertGetIdProcessing()
    {
        $stmt = m::mock(new ProcessorTestACIStatementStub());
        $stmt->shouldReceive('bindValue')->times(4)->withAnyArgs();
        $stmt->shouldReceive('bindParam')->once()->with(5, 0, \PDO::PARAM_INT | \PDO::PARAM_INPUT_OUTPUT, 8);
        $stmt->shouldReceive('execute')->once()->withNoArgs();

        $pdo = m::mock(new ProcessorTestACIStub());
        $pdo->shouldReceive('prepare')->once()->with('sql')->andReturn($stmt);

        $connection = m::mock(OscarConnection::class);
        $connection->shouldReceive('getPdo')->once()->andReturn($pdo);

        $builder = m::mock(OscarBuilder::class);
        $builder->shouldReceive('getConnection')->once()->andReturn($connection);

        $processor = new Microestc\OscarDB\Query\Processors\OscarProcessor;

        $result = $processor->processInsertGetId($builder, 'sql', [1, 'foo', true, null], 'id');
        $this->assertSame(0, $result);
    }

    public function testProcessColumnListing()
    {
        $processor = new Microestc\OscarDB\Query\Processors\OscarProcessor();
        $listing = [['column_name' => 'id'], ['column_name' => 'name'], ['column_name' => 'email']];
        $expected = ['id', 'name', 'email'];
        $this->assertEquals($expected, $processor->processColumnListing($listing));

        // convert listing to objects to simulate PDO::FETCH_CLASS
        foreach ($listing as &$row) {
            $row = (object) $row;
        }

        $this->assertEquals($expected, $processor->processColumnListing($listing));
    }
}
