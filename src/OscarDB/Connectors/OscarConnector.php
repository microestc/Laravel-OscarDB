<?php

namespace OscarDB\Connectors;

use Illuminate\Database\Connectors\Connector as Connector;
use Illuminate\Database\Connectors\ConnectorInterface as ConnectorInterface;
use OscarDB\ACI_PDO\ACI as ACI;

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
        \PDO::ATTR_Oscar_NULLS => \PDO::NULL_NATURAL,
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
            return new ACI($dsn, $config['username'], $config['password'], $options);
        }
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
}
