<?php

/*
 * This file is part of the jimchen/laravel-vod-messagecallback.
 *
 * (c) JimChen <imjimchen@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace JimChen\AliyunVodMNS\Contracts;

use JimChen\AliyunVodMNS\Message;

/**
 * Interface EventHandler
 */
interface EventHandler
{
    /**
     * Event Type
     *
     * @return string
     */
    public static function getEventType();

    public function handle(Message $message);
}
