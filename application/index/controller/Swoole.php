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
        'daemonize'       => false,
        //'ssl_cert_file'=>,
        //'ssl_key_file'=>,
    ];

    public function __construct()
    {
        parent::__construct();
        $this->game = new Game();
    }

    public function onStart($server)
    {
        $this->game->clearFd();
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
        } else {
            $this->game->setFd($req->fd, $res['data']['user_id']);
        }
        $server->push($req->fd, json_encode($res));
    }

    public function onMessage($server, $frame)
    {
        try {
            do {
                $result = ['ret' => 1];
                if ($frame->finish !== true) {
                    $result['msg'] = '数据帧错误';
                    break;
                }
                var_dump($frame->data);
                $request = json_decode($frame->data, true);
                if (!isset($request['mode'])) {
                    $request['msg'] = '参数错误';
                    break;
                }
                switch ($request['mode']) {
                    case 'play':
                        $result = $this->game->play($request);
                        break;
                    case 'start':
                        $result = $this->game->start($request);
                        break;
                    case 'quickstart':
                        $request['ret'] = 0;
                        $result['data'] = $this->game->getRoomList();
                        break;
                    case 'selectroom':
                        $result = $this->game->selectRoom($request, $frame->fd);
                        break;
                    case 'contine':
                        //TODO
                        $result = $this->game->getRoomList();
                        break;
                    default:
                        $result['msg'] = '非法请求';
                }

            } while (false);

            $server->push($frame->fd, json_encode($result));
        } catch (\Exception $e) {
            trace('onMessageError:' . date('Y-m-d H:i:s') . ' ' . $e->getMessage());
        }
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

    public function onClose($server, $fd)
    {
        $this->game->delFd($fd);
    }
}
