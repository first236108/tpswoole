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

        if (!$this->indexRedis->exists('roomlist')) {
            $list = Db::name('list')->select();
            $this->indexRedis->set('roomlist', json_encode($list));
        }

        if (!$this->redis) {
            $count = 3 + count(json_decode($this->getRoomList()));
            for ($i = 2; $i < $count; $i++)
                $this->redis[$i] = $this->redis_connect($i);
        }

        if (!$this->game) {
            if (!isset($count))
                $count = 3 + count(json_decode($this->getRoomList()));
            $this->indexRedis = $this->redis_connect($count);
        }
    }

    public function getRoomList()
    {
        return $this->indexRedis->get('roomlist');
    }

    public function setRoomList($data)
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

    public function selectRoom($request, $fd)
    {
        $indexList = json_decode($this->getRoomList(), true);
        $max       = max(array_column($indexList, 'id'));
        if (!isset($request['room']) || !is_numeric($request['room']) || $request['room'] <= 0 || $request['room'] > $max) {
            return ['ret' => 1, 'msg' => '请求参数错误'];
        }

        $user_id           = $this->getFd($fd);
        $request['points'] = Db::name('users')->where('user_id', $user_id)->value('points');
        $result['ret']     = 1;
        foreach ($indexList as $index => $item) {
            if ($item['id'] == $request['room']) {
                if ($request['points'] < $item['lft']) {
                    $result['ret'] = 1;
                    $result['msg'] = '你的' . config('pointname') . '不足哦';
                    return $result;
                }
                if ($item['rgt'] != 0 && $item['rgt'] < $request['points']) {
                    $result['ret'] = 1;
                    $result['msg'] = '你的' . config('pointname') . '太多了';
                    return $result;
                }
                $indexList[$index]['count'] += 1;
                Db::name('users')->where('user_id', $this->getFd($fd))->setField('select_room', $request['room']);
                $this->setRoomList($indexList);
                $this->setFd($fd, $user_id, $item['id']);
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
        $ret = $this->redis[2]->hGetAll($fd)['user_id'];
        if ($room)
            return $ret['room'];
        return $ret['user_id'];
    }

    public function delFd($fd)
    {
        $data = json_decode($this->getRoomList(), true);
        $room = $this->getFd($fd, true);
        foreach ($data as $k => $v) {
            if ($v['id'] == $room) {
                $data[$k]['count'] -= 1;
                $this->setRoomList($data);
                break;
            }
        }
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
        $this->indexRedis->set('roomlist', json_encode($list));
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