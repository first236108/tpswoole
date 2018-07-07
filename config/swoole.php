<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\facade\Env;

// +----------------------------------------------------------------------
// | Swoole设置 php think swoole命令行下有效
// +----------------------------------------------------------------------
return [
    // 扩展自身配置
    'host'            => 'igccc.com', // 监听地址
    'port'            => 9503, // 监听端口
    'work_num'        => 4,
    'app_path'        => '/www/tpswoole', // 应用地址 如果开启了 'daemonize'=>true 必须设置（使用绝对路径）

    // 可以支持swoole的所有配置参数
    'pid_file'        => Env::get('runtime_path') . 'swoole.pid',
    'log_file'        => Env::get('runtime_path') . 'swoole.log',
    'daemonize'       => true,
];
