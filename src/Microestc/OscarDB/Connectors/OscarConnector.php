<?php

namespace Microestc\OscarDB\Connectors;

use Illuminate\Database\Connectors\Connector as Connector;
use Illuminate\Database\Connectors\ConnectorInterface as ConnectorInterface;
use Microestc\OscarDB\ACI_PDO\ACI as ACI;

class OscarConnector extends Connector implements ConnectorInterface
{
    /**
     * The default PDO connection options.
     *
     * @var array
     */
    protected $options = [
        \PDO::ATTR_CASE => \PDO::CASE_LOWER,
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_ORACLE_NULLS => \PDO::NULL_NATURAL,
    ];

    /**
     * Create a new PDO connection.
     *
     * @param  string  $dsn
     * @param  array   $config
     * @param  array   $options
     * @return PDO
     */
    public function createConnection($dsn, array $config, array $options)
    {
        if ($config['driver'] == 'pdo') {
            return parent::createConnection($dsn, $config, $options);
        } else {
            if ($this->getAttribute($options,\PDO::ATTR_PERSISTENT)) {
                return new \PDO($dsn, $config['username'], $config['password'], array(PDO::ATTR_PERSISTENT => true));
            } else {
                return new \PDO($dsn, $config['username'], $config['password']);
            }
        }
    }

    public function getAttribute(array $options, $attribute)
    {
        if (isset($options[$attribute])) {
            return $options[$attribute];
        }

        return;
    }

    /**
     * Establish a database connection.
     *
     * @param  array  $config
     * @return PDO
     */
    public function connect(array $config)
    {
        $dsn = $this->getDsn($config);

        $options = $this->getOptions($config);

        $connection = $this->createConnection($dsn, $config, $options);

        $this->configureSchema($connection, $config);

        return $connection;
    }

    /**
     * Create a DSN string from a configuration.
     *
     * @param  array   $config
     * @return string
     */
    protected function getDsn(array $config)
    {
        $rv = (empty($config['host']) ? '127.0.0.1' : $config['host']);
        $rv = $rv.(empty($config['port']) ? ':2003' : ':'.$config['port']);
        $rv = $rv.(empty($config['database'])? '/osrdb':'/'.$config['database']);

        $rv = 'aci:dbname='.$rv.(empty($config['charset']) ? '' : ';charset='.$config['charset']);

        return $rv;
    }

    /**
     * Set the schema on the connection.
     *
     * @param  \PDO  $connection
     * @param  array  $config
     * @return void
     */
    protected function configureSchema($connection, $config)
    {
        if (isset($config['schema'])) {
            $schema = $this->formatSchema($config['schema']);

            $connection->prepare("set search_path to {$schema}")->execute();
        }
    }
}
