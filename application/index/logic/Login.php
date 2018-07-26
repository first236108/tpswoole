<?php
/**
 * Created by PhpStorm.
 * User: 85210755@qq.com
 * NickName: 柏宇娜
 * Date: 2018/7/23 15:13
 */

namespace app\index\logic;

use think\Db;

class Login
{
    protected $conf;
    protected $redis;
    protected static $user = [];

    protected function __construct()
    {
        if (!$this->redis) {
            $this->redis = $this->redis_connect();
        }

        if (count(self::$user)) {
            return [self::$user, 200];
        }
    }

    public static function phoneLogin($data = [])
    {
        $user = Db::name('users')->where("mobile={$data['phone']}")
            ->field("user_id,password,sex,last_login,last_ip,mobile,head_pic,nickname,level,code,is_lock,lock_until,lock_reason,token,points,select_room")->find();
        if (!$user) {
            return [['msg' => '用户不存在'], 401];
        }
        if (!password_verify($data['password'], $user['password'])) {
            return [['msg' => '密码错误.'], 401];
        }

        if ($user['is_lock'] && $user['lock_until'] > time()) {
            return [['msg' => '您的账户因' . $user['lock_note'] . '已被锁定至' . date('Y-m-d H:i:s')], 401];
        }

        unset($user['password'], $user['is_lock'], $user['lock_until'], $user['lock_note'], $user['lock_reason']);
        $user['login_time'] = time();
        $user['token']      = md5($user['user_id']);
        if ((new Login)->create_token($user, $data['from']) !== true) {
            return [['msg' => '网络错误，请联系技术人员解决'], 422];
        }

        self::$user = $user;
        return [$user, 200];
    }

    public static function wxLogin($data = [])
    {
        //TODO third login logic
        return true;
    }

    public function create_token($user, $from)
    {
        $redis = $this->redis;
        $allow = array_keys(config('from'));
        unset($allow[$from]);
        try {
            foreach ($allow as $index => $item) {
                $redis->delete($item . '_' . $user['token']);
            }

            $redis->set($from . '_' . $user['token'], json_encode($user));
            return true;
        } catch (\Exception $e) {
            trace('创建token失败：' . $e->getMessage() . date('Y-m-d H:i:s') . PHP_EOL);
            return false;
        }
    }

    public static function checkToken($token, $from)
    {
        $redis = (new Login)->redis;
        if (!$redis->exists($from . '_' . $token)) {
            return ['ret' => 1, 'msg' => '请重新登录'];
        }

        //TODO 增加活动
        $user = json_decode($redis->get($from . '_' . $token), true);
        //if (isset($user['ingame']) && $user['ingame'] > 0) {
        //    return ['ret' => 0, 'data' => $user];
        //}
        return ['ret' => 0, 'data' => $user];
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