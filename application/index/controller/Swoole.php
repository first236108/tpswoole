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
    ];

    public function onReceive($server, $fd, $from_id, $data)
    {
        echo "onReceive...".PHP_EOL;
        $server->send($fd, 'Swoole: ' . $data);
    }

    public function onOpen($server, $fd, $from_id, $data)
    {
        echo "onOpen...".PHP_EOL;
        $server->send($fd, 'Swoole: ' . $data);
    }

    public function onMessage($server, $fd, $from_id, $data)
    {
        echo "onMessage...".PHP_EOL;
        $server->send($fd, 'Swoole: ' . $data);
    }

    public function onClose($server, $fd, $from_id, $data)
    {
        echo "onClose...".PHP_EOL;
        $server->send($fd, 'Swoole: ' . $data);
    }

    public function onTask($server, $fd, $from_id, $data)
    {
        echo "onTask...".PHP_EOL;
        $server->send($fd, 'onClose: ' . $data);
    }

    public function onFinish($server, $fd, $from_id, $data)
    {
        echo "onFinish...".PHP_EOL;
    }
}
