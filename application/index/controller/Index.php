<?php

namespace app\index\controller;

use think\Controller;
use think\Db;
use app\index\logic\Login;

class Index extends Controller
{
    public function index()
    {
        return response("<h1>igccc游戏接口</h1>");
    }

    public function register()
    {
        #fixme 增加验证码或手机短信
        $data   = input('post.');
        $result = $this->validate($data, [
            'phone'    => 'require|mobile',
            'password' => 'require|length:6,32'
        ], [
            'phone.require'    => '手机号必须填写',
            'phone.mobile'     => '手机号错误',
            'password.require' => '密码必须填写',
        ]);
        if ($result !== true) {
            return json(['msg' => $result], 401);
        }

        $row = [
            'mobile'   => $data['phone'],
            'nickname' => '游客',
            'password' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 10]),
        ];

        $row['user_id'] = Db::name('users')->insertGetId($row);
        if ($row['user_id'] > 0) {
            unset($row['password']);
            return json($row, 200);
        }
        return json(['msg' => '创建新用户失败，请稍后再试'], 404);
    }

    public function login()
    {
        $type = input('post.type', 0);
        $data = input('post.');
        $str  = implode('|', array_keys(config('from')));

        switch ($type) {
            case 1:
                $result = $this->validate($data, [
                    'from'     => ['require', 'regex' => '/(' . $str . ')/'],
                    'phone'    => 'require|mobile',
                    'password' => 'require|length:6,32'
                ], [
                    'from.require'     => '登录源不能为空',
                    'from.regex'       => '登录源错误',
                    'phone.require'    => '手机号必须填写',
                    'phone.mobile'     => '手机号错误',
                    'password.require' => '密码必须填写',
                    'phone.length'     => '密码错误',
                ]);
                if ($result !== true) {
                    return json(['msg' => $result], 401);
                }
                $res = Login::phoneLogin($data);
                break;
            case 2:
                $result = $this->validate($data, [
                    'from' => ['require', 'regex' => '/(' . $str . ')/'],
                ], [
                    'from.require' => '登录源不能为空',
                    'from.regex'   => '登录源错误',
                ]);

                if ($result !== true) {
                    return json(['msg' => $result], 401);
                }

                $res = Login::wxLogin($data);
                break;
            default:
                return json(['msg' => '非法请求'], 403);
        }
        return json($res[0], $res[1]);
    }


}
