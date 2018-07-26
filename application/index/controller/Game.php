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
    protected $game;

    /**
     * Game constructor.
     * redis db0 用户token
     * redis db1 场次信息
     * redis db2 fd->user_id & room
     * redis db3~dbn 房间信息
     * redis db+1 游戏桌子
     */
    public function __construct()
    {
        parent::__construct();
        if (!$this->indexRedis) {
            $this->indexRedis = $this->redis_connect(1);
        }

        if (!$this->redis) {
            $count = 3 + $this->indexRedis->dbSize();
            for ($i = 2; $i < $count; $i++)
                $this->redis[$i] = $this->redis_connect($i);
        }

        if (!$this->game) {
            if (!isset($count))
                $count = 3 + $this->indexRedis->dbSize();
            $this->game = $this->redis_connect($count);
        }
    }

    public function getRoomList($roomKey = 0)
    {
        $result = [];
        if ($roomKey) {
            $result = array_combine($this->indexRedis->hkeys($roomKey), $this->indexRedis->hVals($roomKey));
        } else {
            $keys = $this->indexRedis->keys('*');
            foreach ($keys as $index => $key) {
                $result[] = $this->indexRedis->hGetAll($key);
                $result[] = array_combine($this->indexRedis->hkeys($key), $this->indexRedis->hVals($key));
            }
        }
        return $result;
    }

    public function setRoomList($rooms, $key = '', $field = '', $value = '')
    {
        if ($key) {
            $this->indexRedis->hSet($key, $field, $value);
        } else {
            foreach ($rooms as $index => $room) {
                $this->indexRedis->hMSet($room['id'], $room);
            }
        }
    }

    public function setRoomCount($room, $num)
    {
        $this->indexRedis->hIncrBy($room, 'room', $num);
    }

    //public function getMode()
    //{
    //    return $this->indexRedis->get('mode');
    //}

    public function play($request)
    {

        $result = [];
        return $result;
    }

    public function selectRoom($request, $fd)
    {
        if (!isset($request['room']) || !is_numeric($request['room']) || $request['room'] <= 0) {
            return ['ret' => 1, 'msg' => '请求参数错误'];
        }

        $room          = $this->getRoomList($request['room']);
        $result['ret'] = 1;

        if ($room['id'] == $request['room']) {
            $user_id           = $this->getFd($fd);
            $request['points'] = Db::name('users')->where('user_id', $user_id)->value('points');

            if ($request['points'] < $room['lft']) {
                $result['ret'] = 1;
                $result['msg'] = '你的' . config('pointname') . '不足哦';
                return $result;
            }
            if ($room['rgt'] != 0 && $room['rgt'] < $request['points']) {
                $result['ret'] = 1;
                $result['msg'] = '你的' . config('pointname') . '太多了';
                return $result;
            }
            $room['count'] += 1;
            Db::name('users')->where('user_id', $this->getFd($fd))->setField('select_room', $request['room']);
            $this->setRoomList($room, $room['id'], 'count', $room['count'] + 1);
            $this->setFd($fd, $user_id, $room['id']);
            $result['ret'] = 0;
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

    public function setFd($fd, $user_id, $room = 0)
    {
        if (!$room) {
            $this->redis[2]->hSet($fd, 'user_id', $user_id);
            $room = Db::name('users')->where('user_id', $user_id)->value('select_room');
        }
        $this->redis[2]->hSet($fd, 'room', $room);
    }

    public function getFd($fd, $room = 0)
    {
        $ret = $this->redis[2]->hGetAll($fd);
        if ($room)
            return $ret['room'];
        return $ret['user_id'];
    }

    public function delFd($fd)
    {
        $room = $this->getFd($fd, true);
        $this->setRoomCount($room, -1);
        $this->redis[2]->del($fd);
    }

    /**
     * clear roomList and fd
     * @return bool
     */
    public function clearFd()
    {
        $this->indexRedis->flushDB();
        $list = Db::name('list')->select();
        $this->setRoomList($list);
        return $this->redis[2]->flushDB();
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