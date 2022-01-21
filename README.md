## RabbitMQ Binding for Laravel  6


### install

add to `composer.json`

```
  "require": {
    ...
    "nonfishz/laravel-rabbitmq": "3.0.0",
    ...
  }
```

Add the Service Provider to `config/app.php`

```
Nonfishz\RabbitMQ\RabbitMQServiceProvider::class,
```

Add the Facade to `config/app.php`

```
"RabbitMQ" => Nonfishz\RabbitMQ\Facades\RabbitMQ::class,
```

add `config/rabbitmq.php`

```php
<?php
return [
    "connections" => [
        "default" => [
            "host" => '127.0.0.1',
            "port" => 5672,
            "username" => 'guest',
            "password" => 'guest',
            "vhost" => '/',
            "heartbeat_interval" => 120,
        ]
    ]
];
```


### usage for laravel 

if you are not use laravel ,how to user  you can see  [test](https://github.com/nonfishz/laravel-rabbitmq/tree/master/test) 

####   Publisher


```php

//延迟消息发送
function sendDelayMQ($pubData, $exchange,$deadexchange,$queue,$deadQuery,  $delayTime = 1)
{
    $pub = RabbitMQ::createPublisher("default");
    $pub->sendDelayMessage($pubData, $exchange,$deadexchange,$queue,$deadQuery, $delayTime);
    $pub->destroy();
}

//普通消息发送
function sendMQ($pubData, $exchange, $routingKey)
{
    $pub = RabbitMQ::createPublisher("default");
    $pub->sendMessage($pubData, $exchange, $routingKey);
    $pub->destroy();
}


```

```php 
<?php

namespace App\Console\MqTest;

use Illuminate\Console\Command;

class MqPublishTest extends Command
{

    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'nonfishz:mq:publishtest';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'mq publish test';


    /**
     * Execute the console command.
     * @throws \Exception
     */
    public function handle()
    {
        //$pubData, $exchange, $routingKey 
            //普通消息发送
            sendMQ([
                "data"=>"normal message",
            ],"exchange_nonfishz_test","exchange_nonfishz_test");


            //延迟消息发送
            // function sendDelayMQ($pubData, $exchange,$deadexchange,$queue,$deadQuery, $routingKey, $delayTime = 1)
            sendDelayMQ([
                "data"=>"delay message",
            ],"exchange_nonfishz_test_delay","exchange_nonfishz_dead","queue_nonfishz_test_delay","queue_nonfishz_test_dead","queue_nonfishz_test_dead",10);



        sendDelayMQ([
            "data"=>"delay message",
        ],"exchange_nonfishz_test_delay_1","exchange_nonfishz_dead","queue_nonfishz_test_delay_1","queue_nonfishz_test_dead","queue_nonfishz_test_dead",5);
    }
}


```



####   Consumer


```php  
<?php
// 普通消息消费
namespace App\Console\MqTest;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Nonfishz\RabbitMQ\RabbitMQExchange;
use Nonfishz\RabbitMQ\RabbitMQQueue;
use RabbitMQ;

class MqConsume extends Command
{
    protected $signature = 'Nonfishz:mq:consumetest';
    /**
     * The console command description.
     * @var string
     */
    protected $description = 'mq consume test';

    public function handle()
    {

        $exchange = new RabbitMQExchange(
            'exchange_nonfishz_test',
            'topic',
            true, // durable
            false  // auto delete
        );

        $queue = new RabbitMQQueue(
            'exchange_nonfishz_test',
            true, // durable
            false, // exclusive
            false, // auto delete
            'exchange_nonfishz_test'
        );


        // 创建一个消息消费器
        $consumer = RabbitMQ::createConsumer(
            $exchange,
            $queue,
            'default',        // connection name
            true
        );
        // 启用心跳
        $consumer->setNetworkRecovery(true);
        $consumer->setTopologyRecovery(true);

        // 设置消费
        $consumer->consume(
            false,  // no_ack
            false,  // exclusive
            function ($message) {
                $this->_processMessage($message);
            }
        );

        // 开始消费，这句语句会 block 住
        // 同时消费器内部已经针对连接错误进行处理，会自动重连
        $consumer->blockingConsume();
    }

    /**
     * 业务处理
     * @param $message
     */
    private function _processMessage($message)
    {
        $payload = json_decode($message->body, true);

        //改 这块内容即可， 写自己的业务逻辑
        // ----------start
        Log::info($payload);
        print_r($payload);
        echo date("Y-m-d H:i:s" ,time());
        echo PHP_EOL;
        // ---------- end

        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);

    }
}
```

```php

<?php
//延迟消息消费
namespace App\Console\MqTest;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Nonfishz\RabbitMQ\RabbitMQExchange;
use Nonfishz\RabbitMQ\RabbitMQQueue;
use RabbitMQ;

class MqDelayConsume extends Command
{
    protected $signature = 'Nonfishz:mq:consumedelaytest';
    /**
     * The console command description.
     * @var string
     */
    protected $description = 'mq consume test';

    public function handle()
    {

        $exchange = new RabbitMQExchange(
            'exchange_nonfishz_dead',
            'topic',
            true, // durable
            false  // auto delete
        );

        $queue = new RabbitMQQueue(
            'queue_nonfishz_test_dead',
            true, // durable
            false, // exclusive
            false, // auto delete
            'queue_nonfishz_test_delay'
        );


        // 创建一个消息消费器
        $consumer = RabbitMQ::createConsumer(
            $exchange,
            $queue,
            'default',        // connection name
            true
        );
        // 启用心跳
        $consumer->setNetworkRecovery(true);
        $consumer->setTopologyRecovery(true);

        // 设置消费
        $consumer->consume(
            false,  // no_ack
            false,  // exclusive
            function ($message) {
                $this->_processMessage($message);
            }
        );

        // 开始消费，这句语句会 block 住
        // 同时消费器内部已经针对连接错误进行处理，会自动重连
        $consumer->blockingConsume();
    }

    /**
     * 业务处理
     * @param $message
     */
    private function _processMessage($message)
    {
        $payload = json_decode($message->body, true);

        //改 这块内容即可， 写自己的业务逻辑
        // ----------start
        Log::info($payload);
        print_r($payload);
        echo date("Y-m-d H:i:s" ,time());
        echo PHP_EOL;
        // ---------- end

        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);

    }
}


```

