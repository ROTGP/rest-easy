<?php

namespace ROTGP\RestEasy\Test;

use ROTGP\RestEasy\Test\Controllers\UserController;
use ROTGP\RestEasy\Test\Controllers\AlbumController;
use ROTGP\RestEasy\Test\Controllers\ArtistController;
use ROTGP\RestEasy\Test\Controllers\PlayController;
use ROTGP\RestEasy\Test\Controllers\SongController;
use ROTGP\RestEasy\Test\Controllers\StreamingServiceController;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'host' => '127.0.0.1',
            'password' => '',
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer'
        ]);
        
        // https://github.com/orchestral/testbench/issues/252
        $router = $app['router'];
        $router->resource('users', UserController::class);
        $router->resource('albums', AlbumController::class);
        $router->resource('artists', ArtistController::class);
        $router->resource('plays', PlayController::class);
        $router->resource('songs', SongController::class);
        $router->resource('streaming_services', StreamingServiceController::class);
    }
}
