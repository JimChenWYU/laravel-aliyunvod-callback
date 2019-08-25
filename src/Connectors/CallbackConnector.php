<?php

/*
 * This file is part of the jimchen/laravel-aliyunvod-callback.
 *
 * (c) JimChen <imjimchen@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace JimChen\AliyunVodMNS\Connectors;

use Aliyun\MNS\Client;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Support\Arr;
use JimChen\AliyunVodMNS\Adaptors\CallbackAdapter;
use JimChen\AliyunVodMNS\CallbackQueue;

class CallbackConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param array $config
     *
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        return new CallbackQueue(
            $this->getAdaptor($config),
            $config['queue'],
            Arr::get($config, 'events', []),
            Arr::get($config, 'wait_seconds')
        );
    }

    /**
     * @param array $config
     *
     * @return CallbackAdapter
     */
    protected function getAdaptor(array $config)
    {
        return new CallbackAdapter($this->getClient($config));
    }

    /**
     * @param array $config
     *
     * @return Client
     */
    protected function getClient(array $config)
    {
        return new Client($this->getEndpoint($config), $config['key'], $config['secret']);
    }

    /**
     * @param array $config
     *
     * @return mixed
     */
    protected function getEndpoint(array $config)
    {
        return str_replace('(s)', 's', $config['endpoint']);
    }
}
