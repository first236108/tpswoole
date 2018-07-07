<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2018/7/7 12:14
 */

namespace app\index\controller;

use think\swoole\Server;

class Swoole extends Server
{
    protected $host = 'igccc.com';
    protected $port = 9502;
    protected $serverType = 'socket';
    protected $option = [
        'worker_num'      => 4,
        'max_request'     => 1000,
        'max_conn'        => 5000,
        'task_worker_num' => 200,
        'backlog'         => 128,
        'daemonize'       => false,
        //'ssl_cert_file'=>,
        //'ssl_key_file'=>,
    ];

    public function onReceive($server, $fd, $from_id, $data)
    {
        echo "onReceive..." . PHP_EOL;
        $server->send($fd, 'Swoole: ' . $data);
    }

    public function onOpen($server, $req)
    {
        $uid = input('uid', 0);
        $res = [
            'fd'  => $req->fd,
            'req' => json_decode(json_encode($req), true),
            'uid' => $uid,
        ];
        $server->send($req->fd, json_encode($res));
    }

    public function onMessage($server, $frame)
    {
        $msg = input('msg', 'no msg');
        $res = [
            'fd'      => $frame->fd,
            'from_id' => json_decode(json_encode($frame), true),
            'data'    => $frame->data,
            'action'  => 'onMessage',
            'msg'     => $msg
        ];
        $server->send($frame->fd, json_encode($res));
    }

    public function onClose($server, $fd)
    {
        echo "onClose..." . PHP_EOL;
    }

    public function onTask($server, $fd, $from_id, $data)
    {
        echo "onTask..." . PHP_EOL;
        $server->send($fd, 'onClose: ' . $data);
    }

    public function onFinish($server, $task_id, $data)
    {
        echo "onFinish..." . PHP_EOL;
    }
}
