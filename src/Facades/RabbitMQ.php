<?php
namespace Nonfishz\RabbitMQ\Facades;

use Illuminate\Support\Facades\Facade;

class RabbitMQ extends Facade
{

    protected static function getFacadeAccessor()
    {
        return 'Nonfishz\RabbitMQ\RabbitMQ';
    }

}
