<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2018/7/24 17:17
 */

namespace app\index\controller;

use think\Controller;
use think\Db;

class Game extends Controller
{
    protected $indexRedis;
    protected $redis;

    /**
     * Game constructor.
     * redis db0 用户token
     * redis db1 场次信息
     * redis db2 fd->user_id
     * redis db3~dbn 房间信息
     */
    public function __construct()
    {
        parent::__construct();
        if (!$this->indexRedis) {
            $this->indexRedis = $this->redis_connect(1);
        }

        if (!$this->indexRedis->exists('roomlist')) {
            $list = Db::name('list')->select();
            $this->indexRedis->set('roomlist', json_encode($list));
        }
        $count = 3 + count(json_decode($this->getRoomList()));
        if (!$this->redis) {
            for ($i = 2; $i < $count; $i++)
                $this->redis[$i] = $this->redis_connect($i);
        }
    }

    public function getRoomList()
    {
        return $this->indexRedis->get('roomlist');
    }

    public function setList($data)
    {
        return $this->indexRedis->set('roomlist', json_encode($data));
    }

    public function getMode()
    {
        return $this->indexRedis->get('mode');
    }

    public function play($request)
    {

        $result = [];
        return $result;
    }

    public function selectRoom($request)
    {
        $indexList = json_decode($this->indexList(), true);
        $max       = max(array_column($indexList, 'id'));
        if (!isset($request['room']) || !isset($request['points']) || !is_int($request['room']) || $request['room'] <= 0 || $request['room'] > $max) {
            return ['ret' => 1, 'msg' => '请求参数错误'];
        }

        $result['ret'] = 1;
        foreach ($indexList as $index => $item) {
            if ($item['id'] == $request['room']) {
                if ($request['points'] < $item['lft']) {
                    $result['ret'] = 1;
                    $result['msg'] = '你的' . config('pointname') . '不足哦';
                    return $result;
                }
                if ($item['rgt'] != 0 && $item['rgt'] < $request['points']) {
                    $result['ret'] = 1;
                    $result['msg'] = '你的' . config('pointname') . '太多了，就别欺负新手啦';
                    return $result;
                }
                $indexList[$index]['count'] += 1;
                $this->setIndexList($indexList);
                $result['ret'] = 0;
                break;
            }
        }
        if ($result['ret'] > 0)
            $result['msg'] = '选择场次失败';

        return $result;
    }

    public function start($request)
    {

        $result = [];
        return $result;
    }

    public function setFd($fd, $user_id)
    {
        $this->redis[2]->set($fd, $user_id);
    }

    public function getFd($fd)
    {
        return $this->redis[2]->get($fd);
    }

    public function delFd($fd)
    {
        $this->redis[2]->delete($fd);
    }

    /**
     * 清除fd
     * @return bool
     */
    public function clearFd()
    {
        $redis = $this->redis_connect(2);
        return $redis->flushDB();
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