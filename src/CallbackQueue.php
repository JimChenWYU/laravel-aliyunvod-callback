<?php

/*
 * This file is part of the jimchen/laravel-aliyunvod-callback.
 *
 * (c) JimChen <imjimchen@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace JimChen\AliyunVodMNS;

use Aliyun\MNS\Exception\MessageNotExistException;
use Aliyun\MNS\Requests\SendMessageRequest;
use Exception;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;
use JimChen\AliyunVodMNS\Adaptors\CallbackAdapter;
use JimChen\AliyunVodMNS\Concerns\CallbackHandlerStore;
use JimChen\AliyunVodMNS\Jobs\CallbackJob;

class CallbackQueue extends Queue implements QueueContract
{
    use CallbackHandlerStore;

    /**
     * @var bool
     */
    private static $bootRegisterEvents = false;

    /**
     * @var CallbackAdapter
     */
    protected $adapter;

    /**
     * The name of default queue.
     *
     * @var string
     */
    protected $default;

    /**
     * @var null
     */
    private $waitSeconds;

    public function __construct(CallbackAdapter $adapter, $queue, array $events, $waitSeconds = null)
    {
        $this->adapter = $adapter;
        $this->default = $queue;
        $this->waitSeconds = $waitSeconds;
        $this->registerHandlers($events);
    }

    /**
     * Get the size of the queue.
     *
     * @param string $queue
     * @return int
     */
    public function size($queue = null)
    {
        throw new Exception('The size method is not supported in non-debug mode');
    }

    /**
     * Push a new job onto the queue.
     *
     * @param string|object $job
     * @param mixed         $data
     * @param string        $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        $payload = $this->createPayload($job, $data);

        return $this->pushRaw($payload, $queue);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param string $payload
     * @param string $queue
     * @param array  $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $message = new SendMessageRequest($payload);
        $response = $this->adapter->useQueue($this->getQueue($queue))->sendMessage($message);

        return $response->getMessageId();
    }

    /**
     * Get the queue or return the default.
     *
     * @param string|null $queue
     *
     * @return string
     */
    public function getQueue($queue)
    {
        return $queue ?: $this->default;
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param \DateTimeInterface|\DateInterval|int $delay
     * @param string|object                        $job
     * @param mixed                                $data
     * @param string                               $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        if (method_exists($this, 'getSeconds')) {
            $seconds = $this->getSeconds($delay);
        } else {
            $seconds = $this->secondsUntil($delay);
        }

        $payload = $this->createPayload($job, $data);
        $message = new SendMessageRequest($payload, $seconds);
        $response = $this->adapter->useQueue($this->getQueue($queue))->sendMessage($message);

        return $response->getMessageId();
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $queue = $this->getDefaultIfNull($queue);
        try {
            $response = $this->adapter->useQueue($this->getQueue($queue))->receiveMessage($this->waitSeconds);
        } catch (MessageNotExistException $e) {
            $response = null;
        }
        if ($response) {
            return new CallbackJob($this->container, $this->adapter, $this, $queue, $response);
        }

        return null;
    }

    /**
     * 获取默认队列名（如果当前队列名为 null）。
     *
     * @param string|null $wanted
     *
     * @return string
     */
    public function getDefaultIfNull($wanted)
    {
        return $wanted ? $wanted : $this->default;
    }
}
