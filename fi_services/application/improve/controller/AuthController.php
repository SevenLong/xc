<?php
/**
 * Created by xwpeng.
 * Date: 2017/11/25
 * 用户接口
 */

namespace app\improve\controller;

use app\improve\model\UserDb;
use think\Controller;
use think\Cookie;
use think\Exception;
use think\Validate;


class AuthController extends Controller
{

    function login()
    {
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'User.login');
        if (true !== $result) return $result;
        //检查是否需要验证码,如需要是否正确
//        if ($this->checkVerify($data) !== 1) return Helper::reErrorJson("account need verify code");
        //核对账号密码，成功得到user.
        $user = UserDb::login($data['account']);
        if (!is_array($user)) {
            // 失败记录时间与错误次数
            return Helper::reErrorJson($user);
        }
        // 失败记录时间与错误次数
        if ($user['pwd'] !== md5($data['pwd'] . $user['salt'])) return Helper::reErrorJson(Errors::LOGIN_ERROR);
        if ($user['status'] !== 0)  return Helper::reErrorJson(Errors::LOGIN_STATUS);
        unset($user['pwd']);
        unset($user['salt']);
        $user['s_token'] = Helper::uniqStr();
        //重置短效token。Helper::uniqStr()
        $auth = $this->resetAuth($user, $data['client']);
        Cookie('s_uid', null);
        Cookie('s_token', null);
        Cookie('s_uid', $user['uid']);
        Cookie('s_token', $user['s_token']);
        return $auth === 1 ? Helper::reSokJson($user) : Helper::reErrorJson($auth);
    }


function loginOut()
{
    $auth = Helper::auth();
    if (!is_array($auth)) return Helper::reErrorJson($auth);
    $data = Helper::getPostJson();
    $result = $this->validate($data, 'User.loginOut');
    if (true !== $result) return $result;
    $dbRes = UserDb::deleteAuth($auth['s_uid'], $data['client']);
    if (is_int($dbRes)) return $dbRes === 1 ? Helper::reSokJson() : Helper::reErrorJson(Errors::AUT_LOGIN);
    return Helper::reErrorJson($dbRes);
}
    private function resetAuth($user, $client)
    {
        $data = [
            'uid' => $user['uid'],
            's_token' => $user['s_token'],
            's_update_time' => date('Y-m-d H:i:s', time()),
//            'l_token'=>Helper::uniqStr(),
//            'l_update_time'=>date('Y-m-d H:i:s', time()),
            'client' => $client
        ];
        return UserDb::resetAuth($data);
    }

    private function checkVerify($data)
    {
        $verify = UserDb::queryVerify($data['account']);
        if (empty($verify)) return 1;
        $distance = 30 * 60 * 1000;
        $passTime = Helper::getMillisecond() - $verify['error_time'];
        if ($verify['error_count'] > 2 && $passTime < $distance) {
            //核对验证码
            if (!$this->checkVerifyCode($data, $verify)) return -1;
        }
        return 1;
    }

    private function checkVerifyCode($data, $verify)
    {
        if (!array_key_exists("code", $data)) return 0;
        if (!array_key_exists("token", $data)) return 0;
        if (empty($verify['code']) or empty($verify['token'])) return 0;
        if (empty($verify['timestamp'])) return 0;
        if ($verify['code'] !== $data['code'] or $verify['token'] !== $data['token'])
            $distance = 3 * 60 * 1000;
        $passTime = Helper::getMillisecond() - $verify['timestamp'];
        return ($passTime < $distance);
    }

}

?>