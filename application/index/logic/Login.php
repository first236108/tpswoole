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
    protected $conf;
    protected $redis;
    protected static $user = [];
    protected $beforeActionList = [
        'check',
    ];

    protected function check()
    {
        if ($redis = '') {
            $this->redis = $this->redis_connect();
        }

        if (count(self::$user)) {
            return [self::$user, 200];
        }
    }

    public static function phoneLogin($data = [])
    {
        $user = Db::name('users')->where("mobile={$data['phone']}")
            ->field("user_id,password,sex,last_login,last_ip,mobile,head_pic,nickname,level,code,is_lock,lock_until,lock_reason,token")->find();
        if (!$user) {
            return [['msg' => '用户不存在'], 401];
        }
        if (!password_verify($data['password'], $user['password'])) {
            return [['msg' => '密码错误.'], 401];
        }

        if ($user['is_lock'] && $user['lock_until'] > time()) {
            return [['msg' => '您的账户因' . $user['lock_note'] . '已被锁定至' . date('Y-m-d H:i:s')], 401];
        }

        unset($user['password'], $user['is_lock'], $user['lock_until'], $user['lock_note']);

        if ((new Login)->create_token($user['user_id'], $data['from'], $user) !== true) {
            return [['msg' => '网络错误，请联系技术人员解决'], 422];
        }

        self::$user = $user;
        return [$user, 200];
    }

    public static function wxLogin($data = [])
    {

    }

    public function create_token($uid, $from, $user)
    {
        $redis = $this->redis;
        $allow = array_keys(config('from'));
        unset($allow[$from]);
        try {
            //TODO 根据设置选择是否允许多端登录
            foreach ($allow as $index => $item) {
                $redis->delete($item . '_' . $uid);
            }
            $redis->set($from . '_' . $uid, json_encode($user));
            return true;
        } catch (\Exception $e) {
            trace('创建token失败：' . $e->getMessage() . date('Y-m-d H:i:s') . PHP_EOL);
            return false;
        }
    }

    public function del_token($key)
    {
        $redis  = $this->redis_connect();
        $result = $redis->delete($key);
        return $result;
    }

    private function redis_connect()
    {
        if (!extension_loaded('redis')) {
            throw new \Exception('not support:redis');
        }
        $redis      = new \Redis();
        $this->conf = config('redis');
        $redis->connect($this->conf['host'], $this->conf['port'], $this->conf['timeout']);
        if (isset($this->conf['password']) && $this->conf['password'] != '')
            $redis->auth($this->conf['password']);
        $redis->select($this->conf['select']);
        return $redis;
    }
}