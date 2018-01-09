<?php
/**
 * Created by xwpeng.
 * Date: 2017/11/25
 * 用户接口
 */

namespace app\improve\controller;

use app\improve\model\UserDb;
use think\Controller;
use think\Db;


class DeptController extends Controller
{

    function ls(){
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $dbRes = UserDb::queryDepts();
        return is_array($dbRes) ? Helper::reSokJson($dbRes) : Helper::reErrorJson($dbRes);
    }

}

?>