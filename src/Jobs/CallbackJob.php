<?php

/*
 * This file is part of the jimchen/laravel-vod-messagecallback.
 *
 * (c) JimChen <imjimchen@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace JimChen\AliyunVodMNS\Jobs;

use Aliyun\MNS\Responses\ReceiveMessageResponse;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Support\Arr;
use JimChen\AliyunVodMNS\Adaptors\CallbackAdapter;
use JimChen\AliyunVodMNS\CallbackQueue;
use JimChen\AliyunVodMNS\Message;

class CallbackJob extends Job implements JobContract
{
    /**
     * The class name of the job.
     *
     * @var string
     */
    protected $job;

    /**
     * The queue message data.
     *
     * @var string
     */
    protected $data;

    /**
     * @var CallbackAdapter
     */
    private $adapter;

    /**
     * @var CallbackQueue
     */
    private $vodCallbackQueue;

    /**
     * @var array
     */
    private $payload;

    /**
     * Create a new job instance.
     *
     * @param \Illuminate\Container\Container              $container
     * @param CallbackAdapter                              $adapter
     * @param string                                       $queue
     * @param \Aliyun\MNS\Responses\ReceiveMessageResponse $job
     */
    public function __construct(
        Container $container,
        CallbackAdapter $adapter,
        CallbackQueue $vodCallbackQueue,
        $queue,
        ReceiveMessageResponse $job
    ) {
        $this->container = $container;
        $this->adapter = $adapter;
        $this->vodCallbackQueue = $vodCallbackQueue;
        $this->queue = $queue;
        $this->job = $job;
        $this->initJobPayload();
    }

    /**
     * Initialization payload
     */
    protected function initJobPayload()
    {
        $this->setJobPayload([
            'displayName' => __CLASS__,
            'job'         => 'Illuminate\Queue\CallQueuedHandler@call',
            'maxTries'    => null,
            'timeout'     => null,
            'timeoutAt'   => null,
            'data'        => [
                'commandName' => '',
                'command'     => '',
            ],
        ]);
    }

    /**
     * Set payload
     */
    protected function setJobPayload(array $payload)
    {
        if (is_null($this->payload)) {
            $this->payload = $payload;
        } else {
            $this->payload = array_merge($this->payload, $payload);
        }
    }

    /**
     * Fire the job.
     */
    public function fire()
    {
        $message = json_decode($raw = $this->getRawBody(), true);
        if (!is_array($message) || !Arr::has($message, 'EventType')) {
            $this->delete();
            throw new \InvalidArgumentException("Seems it's not a resolvable job. [$raw]");
        }
        if (empty($handlers = $this->getRegisteredHandlers($message['EventType']))) {
            $this->delete();
            throw new \InvalidArgumentException("Seems it can't be handled. [$raw]");
        }

        $this->container->instance(Message::class, new Message($message));

        foreach ($handlers as $handler) {
            $this->setJobPayload([
                'maxTries'  => isset($handler->tries) ? $handler->tries : null,
                'timeout'   => isset($handler->timeout) ? $handler->timeout : null,
                'timeoutAt' => $this->vodCallbackQueue->getJobExpiration($handler),
                'data'      => [
                    'commandName' => get_class($handler),
                    'command'     => serialize(clone $handler),
                ],
            ]);
            parent::fire();
        }

        $this->container->instance(Message::class, null);
    }

    /**
     * Get the raw body of the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->job->getMessageBody();
    }

    /**
     * Delete the job from the queue.
     */
    public function delete()
    {
        parent::delete();
        $receiptHandle = $this->job->getReceiptHandle();
        $this->adapter->deleteMessage($receiptHandle);
    }

    /**
     * Get the registered handlers by name
     *
     * @param string $handler
     * @return array|null
     */
    protected function getRegisteredHandlers($handler)
    {
        return $this->vodCallbackQueue->getRegisteredHandlers($handler, $this->container);
    }

    /**
     * Release the job back into the queue.
     *
     * @param int $delay
     */
    public function release($delay = 1)
    {
        parent::release($delay);

        if ($delay < 1) {
            $delay = 1;
        }

        $this->adapter->changeMessageVisibility($this->job->getReceiptHandle(), $delay);
    }

    /**
     * Get the decoded body of the job.
     *
     * @return array
     */
    public function payload()
    {
        return $this->payload;
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return (int)$this->job->getDequeueCount();
    }

    /**
     * Get the IoC container instance.
     *
     * @return \Illuminate\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->job->getMessageId();
    }
}
