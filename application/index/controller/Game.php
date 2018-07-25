<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2018/7/24 17:17
 */

namespace app\index\controller;

use think\Db;

class Game
{
    protected $indexRedis;
    protected $redis;

    public function __construct()
    {
        if (!$this->indexRedis) {
            $this->indexRedis = $this->redis_connect(1);
        }
        if (!$this->indexRedis->exists('indexlist')) {
            $mode = config('gamemode');
            $list = Db::name('list')->select();
            $this->indexRedis->set('indexlist', json_encode($list));
            $this->indexRedis->set('mode', json_encode($mode));
        }
    }

    public function indexList()
    {
        return $this->indexRedis->get('indexlist');
    }

    public function getMode()
    {
        return $this->indexRedis->get('mode');
    }

    private function redis_connect($db = 1)
    {
        if (!extension_loaded('redis')) {
            throw new \Exception('not support:redis');
        }
        $redis = new \Redis();
        $conf  = config('redis');
        $redis->connect($conf['host'], $conf['port'], $conf['timeout']);
        if (isset($conf['password']) && $conf['password'] != '')
            $redis->auth($conf['password']);
        $redis->select($db);
        return $redis;
    }
}