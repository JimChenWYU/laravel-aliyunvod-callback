<?php

/*
 * This file is part of the jimchen/laravel-aliyunvod-callback.
 *
 * (c) JimChen <imjimchen@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace JimChen\AliyunVodMNS;

use Illuminate\Support\ServiceProvider;
use JimChen\AliyunVodMNS\Connectors\CallbackConnector;
use JimChen\AliyunVodMNS\Console\CallbackEventMakeCommand;
use JimChen\AliyunVodMNS\Console\CallbackFlushCommand;

class VodCallbackServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    public function boot()
    {
        CallbackQueue::setEventDispatcher($this->app['events']);

        $this->registerConnector($this->app['queue']);

        $this->commands('command.queue.vodcallback.flush');
        $this->commands('command.make.vodcallback.callback');
    }

    /**
     * Register the MNS queue connector.
     *
     * @param \Illuminate\Queue\QueueManager $manager
     *
     * @return void
     */
    protected function registerConnector($manager)
    {
        $manager->addConnector('vodcallback', function () {
            return new CallbackConnector();
        });
    }

    /**
     * Add the connector to the queue drivers.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommand();
    }

    /**
     * Register the VodCallback queue command.
     */
    private function registerCommand()
    {
        $this->app->singleton('command.queue.vodcallback.flush', function () {
            return new CallbackFlushCommand();
        });

        $this->app->singleton('command.make.vodcallback.callback', function () {
            return new CallbackEventMakeCommand($this->app['files']);
        });
    }
}
