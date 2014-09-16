<?php

require 'vendor/autoload.php';

// RabbitMQ init.
$queue = new RabbitMQ('localhost');

$queue->exchangeDeclare('exchange_name')
    ->queueDeclare('queue_name')
    ->bindQueueToExchange('routing_key');

// publish a task.
$queue->publish('task message is here');

echo "OK\n";