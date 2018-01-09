<?php
/**
 * Created by xwpeng.
 * Date: 2017/11/25
 * 用户接口
 */

namespace app\improve\controller;

use app\improve\model\UserDb;
use think\Controller;
use think\Validate;


class UserController extends Controller
{

    function add()
    {
        $auth = Helper::auth([1]);
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'User.add');
        if (true !== $result) return Helper::reErrorJson($result);
        $data['uid'] = Helper::uniqStr();
        $data['salt'] = Helper::getRandChar(6);
        $data['pwd'] = md5($data['pwd'] . $data['salt']);
        $dbRes = UserDb::add($data);
        if (is_int($dbRes)) return Helper::reSokJson();
        return Helper::reErrorJson($dbRes);
    }


    function updateStatus()
    {
        $auth = Helper::auth([1]);
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'User.status');
        if (true !== $result) return Helper::reErrorJson($result);
        $dbRes = UserDb::updateStatus($data['uid'], $data['status']);
        if (is_int($dbRes)) return $dbRes > 0 ? Helper::reSokJson() : Helper::reErrorJson("uid no exists");
        return Helper::reErrorJson($dbRes);
    }

    function edit()
    {
        $auth = Helper::auth([1]);
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'User.edit');
        if (true !== $result) return Helper::reErrorJson($result);
        $data['salt'] = Helper::getRandChar(6);
        $data['pwd'] = md5($data['pwd'] . $data['salt']);
        $dbRes = UserDb::edit($data);
        if ($dbRes === 1) {
            //修改后删除s_token
            UserDb::deleteAuth($data['uid']);
            return Helper::reSokJson();
        }
        return Helper::reErrorJson($dbRes);
    }

    function query()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'User.query');
        if (true !== $result) return Helper::reErrorJson($result);
        $dbRes = UserDb::query($data['uid']);
        if (is_array($dbRes)) return Helper::reSokJson($dbRes);
        return Helper::reErrorJson($dbRes);
    }

    function ls($sample = false)
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $validate = new Validate([
            'per_page' => 'require|number|max:500|min:1',
            'current_page' => 'require|number|min:1',
            'dept' => 'number',
            'name' => 'max:16',
        ]);
        if (!$validate->check($data)) return Helper::reErrorJson($validate->getError());
        $dbRes = UserDb::ls($data, $sample);
        return Helper::reJson4(is_array($dbRes), $dbRes);
    }

    function sampleLs()
    {
        return $this->ls(true);
    }
}

?>