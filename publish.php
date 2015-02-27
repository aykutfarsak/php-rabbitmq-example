<?php

require 'vendor/autoload.php';

// RabbitMQ init.
$queue = new RabbitMQ('localhost');

$queue
	->exchangeDeclare('exchange_name')
    ->queueDeclare('queue_name')
    ->bindQueueToExchange('routing_key');

// publish a task.
$msg = sprintf("[%s] Task message is here \n", date('Y-m-d H:i:s'));
$queue->publish($msg);

echo "OK\n";