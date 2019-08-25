<?php

/*
 * This file is part of the jimchen/laravel-aliyunvod-callback.
 *
 * (c) JimChen <imjimchen@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace JimChen\AliyunVodMNS\Concerns;

use Illuminate\Contracts\Container\Container;

trait CallbackHandlerStore
{
    use HasDispatcher;

    /**
     * User exposed observable events.
     *
     * These are extra user-defined events observers may subscribe to.
     *
     * @var array
     */
    protected static $extraHandlers = [];

    public function registerHandlers(array $handlers)
    {
        $registerableHandlers = $this->getRegisterableHandlers();

        foreach ($handlers as $handler) {
            if (class_exists($handler)) {
                $key = array_search($handler::getEventType(), $registerableHandlers);
                if (false !== $key) {
                    static::registerHandler($registerableHandlers[$key], function ($container) use ($handler) {
                        return $container->make($handler);
                    });
                }
            }
        }
    }

    /**
     * Get the callback handler names.
     *
     * @return array
     */
    public function getRegisterableHandlers()
    {
        return array_merge(
            [
                'FileUploadComplete',
                'ImageUploadComplete',
                'StreamTranscodeComplete',
                'TranscodeComplete',
                'SnapshotComplete',
                'AddLiveRecordVideoComplete',
                'LiveRecordVideoComposeStart',
                'UploadByURLComplete',
                'CreateAuditComplete',
                'AIMediaAuditComplete',
                'VideoAnalysisComplete',
                'AIMediaDNAComplete',
                'AIVideoTagComplete',
                'AttachedMediaUploadComplete',
                'DeleteMediaComplete',
                'ProduceMediaComplete',
                'MediaBaseChangeComplete',
            ],
            static::$extraHandlers
        );
    }

    /**
     * Register a callback handler
     *
     * @param string          $event
     * @param \Closure|string $callback
     * @return void
     */
    private function registerHandler($handler, $callback)
    {
        if (isset(static::$dispatcher)) {
            static::$dispatcher->listen($this->getStoreKey($handler), $callback);
        }
    }

    private function getStoreKey($handler)
    {
        return 'vod.callback.' . $handler;
    }

    /**
     * Get registered handlers
     *
     * @param string    $handler
     * @param Container $container
     * @return array|null
     */
    public function getRegisteredHandlers($handler, Container $container)
    {
        if (isset(static::$dispatcher)) {
            if (method_exists(static::$dispatcher, 'dispatch')) {
                return static::$dispatcher->dispatch($this->getStoreKey($handler), $container);
            }

            return static::$dispatcher->fire($this->getStoreKey($handler), $container);
        }

        return null;
    }
}
