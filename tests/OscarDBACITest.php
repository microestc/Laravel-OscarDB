<?php

use Microestc\OscarDB\ACI_PDO\ACI;
use Microestc\OscarDB\ACI_PDO\ACIException;
use Microestc\OscarDB\ACI_PDO\ACIStatement;
use Mockery as m;
use PHPUnit\Framework\TestCase;

include 'mocks/ACIMocks.php';
include 'mocks/ACIFunctions.php';

class OscarDBACITest extends TestCase
{
    private $aci;

    protected function setUp(): void
    {
        if (! extension_loaded('aci')) {
            $this->markTestSkipped(
              'The oscar extension is not available.'
            );
        } else {
            global $ACITransactionStatus, $ACIStatementStatus, $ACIExecuteStatus;

            $ACITransactionStatus = true;
            $ACIStatementStatus = true;
            $ACIExecuteStatus = true;

            $this->aci = m::mock(new \TestACIStub('', null, null, [\PDO::ATTR_CASE => \PDO::CASE_LOWER]));
        }
    }

    public function tearDown(): void
    {
        m::close();
    }

    public function testConstructorSuccessWithPersistentConnection()
    {
        $aci = new ACI('dsn', null, null, [\PDO::ATTR_PERSISTENT => 1]);
        $this->assertInstanceOf(ACI::class, $aci);
        $this->assertEquals(1, $aci->getAttribute(\PDO::ATTR_PERSISTENT));
    }

    public function testConstructorSuccessWithoutPersistentConnection()
    {
        $aci = new ACI('dsn', null, null, [\PDO::ATTR_PERSISTENT => 0]);
        $this->assertInstanceOf(ACI::class, $aci);
        $this->assertEquals(0, $aci->getAttribute(\PDO::ATTR_PERSISTENT));
    }

    public function testConstructorFailWithPersistentConnection()
    {
        global $ACITransactionStatus;
        $ACITransactionStatus = false;
        $this->expectException(ACIException::class);
        $aci = new ACI('dsn', null, null, [\PDO::ATTR_PERSISTENT => 1]);
    }

    public function testConstructorFailWithoutPersistentConnection()
    {
        global $ACITransactionStatus;
        $ACITransactionStatus = false;
        $this->expectException(ACIException::class);
        $aci = new ACI('dsn', null, null, [\PDO::ATTR_PERSISTENT => 0]);
    }

    public function testDestructor()
    {
        global $ACITransactionStatus;

        $aci = new ACI('dsn', '', '');
        unset($aci);
        $this->assertFalse($ACITransactionStatus);
    }

    public function testBeginTransaction()
    {
        $result = $this->aci->beginTransaction();
        $this->assertTrue($result);

        $this->assertEquals(0, $this->aci->getExecuteMode());
    }

    public function testBeginTransactionAlreadyInTransaction()
    {
        $this->expectException(ACIException::class);
        $result = $this->aci->beginTransaction();
        $result = $this->aci->beginTransaction();
    }

    public function testCommitInTransactionPasses()
    {
        $this->aci->beginTransaction();
        $this->assertTrue($this->aci->commit());
    }

    public function testCommitInTransactionFails()
    {
        global $ACITransactionStatus;
        $ACITransactionStatus = false;
        $this->expectException(ACIException::class);
        $this->aci->beginTransaction();
        $this->aci->commit();
    }

    public function testCommitNotInTransaction()
    {
        $this->assertFalse($this->aci->commit());
    }

    public function testErrorCode()
    {
        $aci = new \TestACIStub();
        $this->assertNull($aci->errorCode());

        // use reflection to test values of protected properties
        $reflection = new \ReflectionClass($aci);

        // setErrorInfo
        $method = $reflection->getMethod('setErrorInfo');
        $method->setAccessible(true);
        $method->invoke($aci, '11111', '2222', 'Testing the errors');

        $this->assertEquals('11111', $aci->errorCode());
    }

    public function testErrorInfo()
    {
        $aci = new \TestACIStub();
        $this->assertEquals([0 => '', 1 => null, 2 => null], $aci->errorInfo());

        // use reflection to test values of protected properties
        $reflection = new \ReflectionClass($aci);

        // setErrorInfo
        $method = $reflection->getMethod('setErrorInfo');
        $method->setAccessible(true);
        $method->invoke($aci, '11111', '2222', 'Testing the errors');

        $this->assertEquals([0 => '11111', 1 => '2222', 2 => 'Testing the errors'], $aci->errorInfo());
    }

    public function testExec()
    {
        $sql = 'select * from table';
        $aci = new \TestACIStub();
        $stmt = $aci->exec($sql);
        $this->assertEquals(1, $stmt);

        // use reflection to test values of protected properties of ACI object
        $reflection = new \ReflectionClass($aci);

        // stmt property
        $property = $reflection->getProperty('stmt');
        $property->setAccessible(true);
        $aci_stmt = $property->getValue($aci);
        $this->assertInstanceOf(ACIStatement::class, $aci_stmt);

        // use reflection to test values of protected properties of ACIStatement object
        $reflection = new \ReflectionClass($aci_stmt);
        //conn property
        $property = $reflection->getProperty('conn');
        $property->setAccessible(true);
        $this->assertEquals($aci, $property->getValue($aci_stmt));

        //attributes property
        $property = $reflection->getProperty('attributes');
        $property->setAccessible(true);
        $this->assertEquals([], $property->getValue($aci_stmt));
    }

    public function testExecFails()
    {
        global $ACIExecuteStatus;
        $ACIExecuteStatus = false;
        $sql = 'select * from table';
        $aci = new \TestACIStub();
        $stmt = $aci->exec($sql);
        $this->assertFalse($stmt);
    }

    public function testGetAttributeForValidAttribute()
    {
        $this->assertEquals(1, $this->aci->getAttribute(\PDO::ATTR_AUTOCOMMIT));
    }

    public function testGetAttributeForInvalidAttribute()
    {
        $this->assertEquals(null, $this->aci->getAttribute('doesnotexist'));
    }

    public function testInTransactionWhileNotInTransaction()
    {
        $this->assertFalse($this->aci->inTransaction());
    }

    public function testInTransactionWhileInTransaction()
    {
        $this->aci->beginTransaction();
        $this->assertTrue($this->aci->inTransaction());
    }

    public function testLastInsertIDWithName()
    {
        $this->expectException(ACIException::class);
        $result = $this->aci->lastInsertID('foo');
    }

    public function testLastInsertIDWithoutName()
    {
        $this->expectException(ACIException::class);
        $result = $this->aci->lastInsertID();
    }

    public function testPrepareWithNonParameterQuery()
    {
        $sql = 'select * from table';
        $aci = new \TestACIStub();
        $stmt = $aci->prepare($sql);
        $this->assertInstanceOf(ACIStatement::class, $stmt);

        // use reflection to test values of protected properties
        $reflection = new \ReflectionClass($stmt);

        // stmt property
        $property = $reflection->getProperty('stmt');
        $property->setAccessible(true);
        $this->assertEquals('aci statement', $property->getValue($stmt));

        //conn property
        $property = $reflection->getProperty('conn');
        $property->setAccessible(true);
        $this->assertEquals($aci, $property->getValue($stmt));

        //attributes property
        $property = $reflection->getProperty('attributes');
        $property->setAccessible(true);
        $this->assertEquals([], $property->getValue($stmt));
    }

    public function testPrepareWithParameterQuery()
    {
        $sql = 'select * from table where id = ? and date = ?';
        $aci = new \TestACIStub();
        $stmt = $aci->prepare($sql);
        $this->assertInstanceOf(ACIStatement::class, $stmt);

        // use reflection to test values of protected properties
        $reflection = new \ReflectionClass($stmt);

        // stmt property
        $property = $reflection->getProperty('stmt');
        $property->setAccessible(true);
        $this->assertEquals('aci statement', $property->getValue($stmt));

        //conn property
        $property = $reflection->getProperty('conn');
        $property->setAccessible(true);
        $this->assertEquals($aci, $property->getValue($stmt));

        //attributes property
        $property = $reflection->getProperty('attributes');
        $property->setAccessible(true);
        $this->assertEquals([], $property->getValue($stmt));
    }

    public function testPrepareFail()
    {
        global $ACIStatementStatus;
        $ACIStatementStatus = false;
        $sql = 'select * from table where id = ? and date = ?';
        $aci = new \TestACIStub();
        $this->expectException(ACIException::class);
        $stmt = $aci->prepare($sql);
    }

    public function testQuery()
    {
        $sql = 'select * from table';
        $aci = new \TestACIStub();
        $stmt = $aci->query($sql);
        $this->assertInstanceOf(ACIStatement::class, $stmt);

        // use reflection to test values of protected properties
        $reflection = new \ReflectionClass($stmt);

        // stmt property
        $property = $reflection->getProperty('stmt');
        $property->setAccessible(true);
        $this->assertEquals('aci statement', $property->getValue($stmt));

        //conn property
        $property = $reflection->getProperty('conn');
        $property->setAccessible(true);
        $this->assertEquals($aci, $property->getValue($stmt));

        //attributes property
        $property = $reflection->getProperty('attributes');
        $property->setAccessible(true);
        $this->assertEquals([], $property->getValue($stmt));
    }

    public function testQueryWithModeParams()
    {
        $sql = 'select * from table';
        $aci = new \TestACIStub();
        $stmt = $aci->query($sql, \PDO::FETCH_CLASS, 'stdClass', []);
        $this->assertInstanceOf(ACIStatement::class, $stmt);

        // use reflection to test values of protected properties
        $reflection = new \ReflectionClass($stmt);

        // stmt property
        $property = $reflection->getProperty('stmt');
        $property->setAccessible(true);
        $this->assertEquals('aci statement', $property->getValue($stmt));

        //conn property
        $property = $reflection->getProperty('conn');
        $property->setAccessible(true);
        $this->assertEquals($aci, $property->getValue($stmt));

        //attributes property
        $property = $reflection->getProperty('attributes');
        $property->setAccessible(true);
        $this->assertEquals([], $property->getValue($stmt));
    }

    public function testQueryFail()
    {
        global $ACIExecuteStatus;
        $ACIExecuteStatus = false;
        $sql = 'select * from table';
        $aci = new \TestACIStub();
        $stmt = $aci->query($sql);
        $this->assertFalse($stmt);
    }

    public function testQuote()
    {
        $this->assertFalse($this->aci->quote('String'));
        $this->assertFalse($this->aci->quote('String', \PDO::PARAM_STR));
    }

    public function testRollBackInTransactionPasses()
    {
        $this->aci->beginTransaction();
        $this->assertTrue($this->aci->rollBack());
    }

    public function testRollBackInTransactionFails()
    {
        global $ACITransactionStatus;
        $ACITransactionStatus = false;
        $this->expectException(ACIException::class);
        $this->aci->beginTransaction();
        $this->aci->rollBack();
    }

    public function testRollBackNotInTransaction()
    {
        $this->assertFalse($this->aci->rollBack());
    }

    public function testSetAttribute()
    {
        $this->aci->setAttribute('attribute', 'value');
        $this->assertEquals('value', $this->aci->getAttribute('attribute'));
        $this->aci->setAttribute('attribute', 4);
        $this->assertEquals(4, $this->aci->getAttribute('attribute'));
    }

    public function testFlipExecuteMode()
    {
        $this->assertEquals(\ACI_COMMIT_ON_SUCCESS, $this->aci->getExecuteMode());
        $this->aci->flipExecuteMode();
        $this->assertEquals(\ACI_NO_AUTO_COMMIT, $this->aci->getExecuteMode());
    }

    public function testGetExecuteMode()
    {
        $this->assertEquals(\ACI_COMMIT_ON_SUCCESS, $this->aci->getExecuteMode());
    }

    public function testGetACIResource()
    {
        $this->assertEquals('aci', $this->aci->getACIResource());
    }

    public function testSetExecuteModeWithValidMode()
    {
        $this->aci->setExecuteMode(\ACI_COMMIT_ON_SUCCESS);
        $this->assertEquals(\ACI_COMMIT_ON_SUCCESS, $this->aci->getExecuteMode());
        $this->aci->setExecuteMode(\ACI_NO_AUTO_COMMIT);
        $this->assertEquals(\ACI_NO_AUTO_COMMIT, $this->aci->getExecuteMode());
    }

    public function testSetExecuteModeWithInvalidMode()
    {
        $this->expectException(ACIException::class);
        $this->aci->setExecuteMode('foo');
    }
}
