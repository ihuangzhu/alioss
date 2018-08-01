<?php

namespace Ihuangzhu\Alioss;


class Utils
{
    /**
     * 获取时间
     *
     * @param int $time
     * @return string
     */
    public static function gmtIso8601($time)
    {
        $dtStr = date('c', $time);
        $myDateTime = new \DateTime($dtStr);
        $expiration = $myDateTime->format(\DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration . 'Z';
    }

}