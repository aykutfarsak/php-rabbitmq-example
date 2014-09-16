<?php

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQ
{
    /**
     * @var AMQPConnection
     */
    protected $connection;

    /**
     * @var \PhpAmqpLib\Channel\AMQPChannel;
     */
    protected $channel;

    /**
     * @var string
     */
    protected $exchangeName = '';

    /**
     * @var string
     */
    protected $queueName = '';

    /**
     * @var string
     */
    protected $routingKey = '';

    /**
     * @var int
     */
    protected $consumerCount;

    /**
     * Constructor
     *
     * @param string $host
     * @param int $port
     * @param string $user
     * @param string $pass
     * @param string $vhost
     */
    public function __construct($host = 'localhost', $port = 5672, $user = 'guest', $pass = 'guest', $vhost = '/')
    {
        $this->connection = new AMQPConnection($host, $port, $user, $pass, $vhost);
        $this->channel = $this->connection->channel();
    }

    /**
     * Exchange Declare
     *
     * @param string $name           Exchange name
     * @param string $type           Exchnage types ( fanout, direct, topic )
     * @param bool $isPassive        Don't check is an exchange with the same name exists
     * @param bool $isDurable        The exchange will survive server restarts
     * @param bool $isAutoDeleteMode The exchange will be deleted once the channel is closed
     * @return $this
     */
    public function exchangeDeclare($name, $type = 'direct', $isPassive = false, $isDurable = true, $isAutoDeleteMode = false)
    {
        $this->exchangeName = $name;

        $this->channel->exchange_declare($name, $type, $isPassive, $isDurable, $isAutoDeleteMode);

        return $this;
    }

    /**
     * Queue Declare
     *
     * @param string $name           Queue name
     * @param bool $isPassive        Don't check is an queue with the same name exists
     * @param bool $isDurable        The queue will survive server restarts
     * @param bool $isExclusive      The queue can't be accessed in other channels
     * @param bool $isAutoDeleteMode The queue will be deleted once the channel is closed
     * @return $this
     */
    public function queueDeclare($name, $isPassive = false, $isDurable = true, $isExclusive = false, $isAutoDeleteMode = false)
    {
        $this->queueName       = $name;
        list(,,$consumerCount) = $this->channel->queue_declare($name, $isPassive, $isDurable, $isExclusive, $isAutoDeleteMode);
        $this->consumerCount   = $consumerCount;

        return $this;
    }

    /**
     * Bind queue to exchnage
     *
     * @param string $routingKey
     * @return $this
     */
    public function bindQueueToExchange($routingKey = '')
    {
        $this->routingKey = $routingKey;

        $this->channel->queue_bind($this->queueName, $this->exchangeName, $routingKey);

        return $this;
    }

    /**
     * Publish message to exchange
     *
     * @param string $body
     * @param array $options
     * @return void
     */
    public function publish($body, array $options = array())
    {
        // message
        $msg = new AMQPMessage($body, array_merge(array(
            'content_type'  => 'text/plain',
            'delivery_mode' => 2
        ), $options));

        // publish the message to exchange
        $this->channel->basic_publish($msg, $this->exchangeName, $this->routingKey);
    }

    /**
     * Consume the task
     *
     * @param callable $callback  Task callback
     * @param string $consumerTag Consumer identifier
     * @param bool $isNoLocal     Don't receive messages published by this consumer.
     * @param bool $isNoACK       Tells the server if the consumer will acknowledge the messages.
     * @param bool $isExclusive   Request exclusive consumer access, meaning only this consumer can access the queue
     * @param bool $isNoWait
     * @return void
     */
    public function consume($callback, $consumerTag = '', $isNoLocal = false, $isNoACK = true, $isExclusive = false, $isNoWait = false)
    {
        // fair dispatch (prefect only 1 task)
        $this->channel->basic_qos(null, 1, null);

        // consume
        $this->channel->basic_consume($this->queueName, $consumerTag, $isNoLocal, $isNoACK, $isExclusive, $isNoWait, $callback);
    }

    /**
     * Wait for task
     *
     * @return void
     */
    public function wait()
    {
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    /**
     * Close RabbitMQ connection
     *
     * @return void
     */
    public function closeConnection()
    {
        $this->channel->close();
        $this->connection->close();
    }

    /**
     * Current consumer count.
     *
     * @return int
     */
    public function consumerCount()
    {
        return $this->consumerCount;
    }
}
