<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2018/7/7 12:14
 */

namespace app\index\controller;

use think\swoole\Server;
use app\index\logic\Login;

class Swoole extends Server
{
    protected $game;
    protected $host = 'igccc.com';
    protected $port = 9502;
    protected $serverType = 'socket';
    protected $option = [
        'worker_num'      => 4,
        'max_request'     => 1000,
        'max_conn'        => 5000,
        'task_worker_num' => 200,
        'backlog'         => 128,
        'daemonize'       => true,
        //'ssl_cert_file'=>,
        //'ssl_key_file'=>,
    ];

    public function __construct()
    {
        parent::__construct();
        $this->game=new Game();
    }

    public function onReceive($server, $fd, $from_id, $data)
    {
        echo "onReceive..." . PHP_EOL;
        //$server->send($fd, 'Swoole: ' . $data);
    }

    public function onOpen($server, $req)
    {
        $token = $req->get['token'];
        $from  = $req->get['from'];

        $res = Login::checkToken($token, $from);
        if ($res['ret'] == 1) {
            //$server->close($req->fd);
        }
        $mode=$this->game->getMode();
        $server->push($req->fd, json_encode($mode));
    }

    public function onMessage($server, $frame)
    {
        var_dump($server);
        echo '-----'.PHP_EOL;
        var_dump($frame);
        echo '-----'.PHP_EOL;
        $msg    = input('msg', 'no msg');
        $res    = [
            'fd'      => $frame->fd,
            'from_id' => $frame->data,
            'data'    => $frame->data,
            'action'  => 'onMessage',
            'msg'     => $msg
        ];
var_dump($res);
        $result = $this->game->indexList();
        $server->push($frame->fd, json_encode([$res,$result]));
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
