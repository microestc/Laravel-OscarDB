<?php

namespace OscarDB;

use Illuminate\Database\Connection;
use OscarDB\Schema\OscarBuilder as OscarSchemaBuilder;
use OscarDB\Query\Processors\OscarProcessor;
use Doctrine\DBAL\Driver\ACI\Driver as DoctrineDriver;
use OscarDB\Query\Grammars\OscarGrammar as QueryGrammer;
use OscarDB\Query\OscarBuilder as OscarQueryBuilder;
use OscarDB\Schema\Grammars\OscarGrammar as SchemaGrammer;
use PDO;

class OscarConnection extends Connection
{
    /**
     * Get a schema builder instance for the connection.
     *
     * @return \OscarDB\Schema\OscarBuilder
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
     * @return \OscarDB\Query\OscarBuilder
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
     * @return \OscarDB\Query\Grammars\OscarGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammer);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \OscarDB\Schema\Grammars\OscarGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new SchemaGrammer);
    }

    /**
     * Get the default post processor instance.
     *
     * @return \OscarDB\Query\Processors\OscarProcessor
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
            $statement->bindValue(
                $key,
                $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
    }


}
