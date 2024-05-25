<?php


namespace ModStart\Core\Util;


use NinjaMutex\Lock\MySqlLock;
use NinjaMutex\MutexFabric;

class LockUtil
{
    static $instance = null;

    /**
     * @return MutexFabric
     */
    private static function instance()
    {
        if (null === self::$instance) {
            $mysqlLock = new MySqlLock(
                config('env.DB_USERNAME'),
                config('env.DB_PASSWORD'),
                config('env.DB_HOST')
            );
            $mutexFabric = new MutexFabric('mysql', $mysqlLock);
            self::$instance = $mutexFabric;
        }
        return self::$instance;
    }

    /**
     * 请求一个锁
     * @param $name string 锁的名字
     * @param $timeout int 超时时间，单位秒
     * @return bool
     */
    public static function acquire($name, $timeout = 60)
    {
        if (RedisUtil::isEnable()) {
            $key = "Lock:$name";
            if (RedisUtil::setnx($key, time() + $timeout)) {
                RedisUtil::expire($key, $timeout);
                return true;
            }
            $ts = RedisUtil::get($key);
            if ($ts < time()) {
                RedisUtil::delete($key);
                return self::acquire($name, $timeout);
            }
            return false;
        } else {
            if (self::instance()->get($name)->acquireLock($timeout)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 释放一个锁
     * @param $name string 锁的名字
     * @return void
     */
    public static function release($name)
    {
        if (RedisUtil::isEnable()) {
            $key = "Lock:$name";
            RedisUtil::delete($key);
        } else {
            self::instance()->get($name)->releaseLock();
        }
    }
}
