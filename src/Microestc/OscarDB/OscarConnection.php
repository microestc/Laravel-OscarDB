<?php

namespace Microestc\OscarDB;

use Illuminate\Database\Connection;
use Microestc\OscarDB\Schema\OscarSchemaBuilder;
use Microestc\OscarDB\Query\Processors\OscarProcessor;
use Doctrine\DBAL\Driver\ACI\Driver as DoctrineDriver;
use Microestc\OscarDB\Query\Grammars\OscarGrammar as QueryGrammer;
use Microestc\OscarDB\Query\OscarBuilder as OscarQueryBuilder;
use Microestc\OscarDB\Schema\Grammars\OscarGrammar as SchemaGrammer;
use PDO;

class OscarConnection extends Connection
{
    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Microestc\OscarDB\Schema\OscarBuilder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new OscarSchemaBuilder($this);
    }

    /**
     * Get a new query builder instance.
     *
     * @return \Microestc\OscarDB\Query\OscarBuilder
     */
    public function query()
    {
        return new OscarQueryBuilder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \Microestc\OscarDB\Query\Grammars\OscarGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammer);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Microestc\OscarDB\Schema\Grammars\OscarGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new SchemaGrammer);
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Microestc\OscarDB\Query\Processors\OscarProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new OscarProcessor;
    }

    /**
     * Get the Doctrine DBAL driver.
     *
     * @return \Doctrine\DBAL\Driver\ACI\Driver
     */
    protected function getDoctrineDriver()
    {
        return new DoctrineDriver;
    }

    /**
     * Bind values to their parameters in the given statement.
     *
     * @param  \PDOStatement $statement
     * @param  array  $bindings
     * @return void
     */
    public function bindValues($statement, $bindings)
    {
        foreach ($bindings as $key => $value) {
            if (is_int($value)) {
                $pdoParam = PDO::PARAM_INT;
            } elseif (is_resource($value)) {
                $pdoParam = PDO::PARAM_LOB;
            } else {
                $pdoParam = PDO::PARAM_STR;
            }

            $statement->bindValue(
                is_string($key) ? $key : $key + 1,
                $value,
                $pdoParam
            );
        }
    }


}
