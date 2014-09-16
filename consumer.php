<?php

require 'vendor/autoload.php';

// RabbitMQ init.
$queue = new RabbitMQ('localhost');

$queue->exchangeDeclare('exchange_name')
    ->queueDeclare('queue_name')
    ->bindQueueToExchange('routing_key');

// consumer limit.
$maxConsumerCount     = 3;
$currentConsumerCount = $queue->consumerCount();

if ($currentConsumerCount >= $maxConsumerCount) {
    exit("There are enough consumers. Exit\n");
}

// consumer ttl.
$start = time();
$ttl   = 300; // second

echo "[*] Waiting for tasks..\n";

// consume callback.
$queue->consume(function ($msg) use ($start, $ttl)
{
    // do task.
    echo $msg->body;

    // OR do it in the background.
    $message    = base64_encode(serialize($msg->body)); // serialize and encode to send as cli argument.
    $workerFile = realpath(__DIR__) . '/worker.php';
    exec("nohup /usr/bin/php {$workerFile} {$message} > /dev/null 2>&1&");

    // stop consuming when ttl is reached.
    if (($start + $ttl) < time()) {
        exit("Need restart. Exit\n");
    }
});

// wait for task
$queue->wait();

// close connections
$queue->closeConnection();