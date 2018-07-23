<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2018/7/23 15:13
 */

namespace app\index\logic;

use think\Db;

class Login extends \think\Controller
{
    public static function phoneLogin($data = [])
    {
        $user = Db::name('users')->where("mobile={$data['phone']}")->find();
        if (!$user) {
            return [['msg' => '用户不存在'], 401];
        }
        if (!password_verify($data['password'], $user['password'])) {
            return [['msg' => '密码错误.'], 401];
        }
        return [$user, 200];
    }

    public static function wxLogin($data = [])
    {

    }
}