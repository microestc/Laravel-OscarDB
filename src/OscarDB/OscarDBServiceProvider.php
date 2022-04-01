<?php

namespace OscarDB;

use Illuminate\Support\ServiceProvider;

/**
 * Class OscarDBServiceProvider.
 */
class OscarDBServiceProvider extends ServiceProvider
{
    /**
     * Boot.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/oscardb.php' => config_path('oscardb.php'),
        ], 'oscardb-config');
    }

    /**
     * Register the service provider.
     *
     * @returns OrcaleDB\OscarConnection
     */
    public function register()
    {
        if (file_exists(config_path('oscardb.php'))) {
            // merge config with other connections
            $this->mergeConfigFrom(config_path('oscardb.php'), 'database.connections');

            // get only Oscar configs to loop thru and extend DB
            $config = $this->app['config']->get('oscardb', []);

            $connection_keys = array_keys($config);

            if (is_array($connection_keys)) {
                foreach ($connection_keys as $key) {
                    $this->app['db']->extend($key, function ($config) {
                        $oConnector = new Connectors\OscarConnector();

                        $connection = $oConnector->connect($config);

                        return new OscarConnection($connection, $config['database'], $config['prefix']);
                    });
                }
            }
        }
    }
}
