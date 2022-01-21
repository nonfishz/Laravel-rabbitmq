<?php
/**
 * Desc:
 * User: maozhongyu
 * Date: 2022/1/20
 * Time: 下午3:01
 */
require_once __DIR__ . "/../vendor/autoload.php";

use Nonfishz\RabbitMQ\RabbitMQ;


sendDelayMQ(array(
    "delay_mq"=>1
),
    "tmp_exchange_3",
    "tmp_dead_exchange",
        "tmp_queue_3",
                "tmp_dead_queue",
                20
);


function sendDelayMQ($pubData, $exchange,$deadexchange,$queue,$deadQuery, $delayTime = 1)
{
    $config = array(
        "connections"=>array(
            "default"=>array(
                'host' => '47.103.78.179',
                'port' => '5672',
                'username' => 'admin',
                'password' => '123456',
                'vhost' => '/',
                "heartbeat_interval" => 30,
            )
        )
    );
    $mq = new RabbitMQ($config);
    $pub = $mq->createPublisher("default");
    $pub->sendDelayMessage($pubData, $exchange,$deadexchange,$queue,$deadQuery, $delayTime);
    $pub->destroy();
}