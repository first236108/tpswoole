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
        $uid = $req->get['uid'];
        echo $uid.PHP_EOL;
        $res = [
            'fd'  => $req->fd,
            'req' => $req->server['remote_addr'],
            'uid' => $uid,
        ];
        var_dump($req);
        $server->push($req->fd, json_encode($res));
    }

    public function onMessage($server, $frame)
    {
       var_dump( $frame->data);
        $msg = input('msg', 'no msg');
        $res = [
            'fd'      => $frame->fd,
            'from_id' => $frame->data,
            'data'    => $frame->data,
            'action'  => 'onMessage',
            'msg'     => $msg
        ];
        $server->push($frame->fd, json_encode($res));
    }

    public function onClose($server, $fd)
    {
        echo "onClose..." . PHP_EOL;
    }

    public function onTask($server, $fd, $from_id, $data)
    {
        echo "onTask..." . PHP_EOL;
        $server->push($fd, 'onClose: ' . $data);
    }

    public function onFinish($server, $task_id, $data)
    {
        echo "onFinish..." . PHP_EOL;
    }
}
