<?php

/*
 * This file is part of the jimchen/laravel-aliyunvod-callback.
 *
 * (c) JimChen <imjimchen@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace JimChen\AliyunVodMNS;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Class Message
 *
 * @method string getEventType() 事件类型
 * @method string getEventTime() 事件产生时间, 为UTC时间：yyyy-MM-ddTHH:mm:ssZ
 */
class Message extends Collection
{
    public function __call($method, $parameters)
    {
        if (Str::startsWith($method, 'get')) {
            $messageParameter = Str::replaceFirst('get', '', $method);

            return $this->get(ucfirst($messageParameter));
        }

        return parent::__call($method, $parameters);
    }

    /**
     * Get the extend to array
     *
     * @return array
     */
    public function getExtendArray()
    {
        return json_decode($this->getExtend(), true);
    }

    /**
     * Get the extend to Message
     *
     * @return Message
     */
    public function getExtendMessage()
    {
        return new static($this->getExtendArray());
    }
}
