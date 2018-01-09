<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/23
 * Time: 13:51
 */

namespace app\improve\controller;


use app\improve\model\CommonDb;
use think\Controller;

class  CommonController extends Controller
{


    public function queryRegion()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'Region.query');
        if (true !== $result) return Helper::reErrorJson($result);
        $dbRes = CommonDb::queryRegion($data['parentId']);
        if (is_array($dbRes)) return Helper::reSokJson($dbRes);
        return Helper::reErrorJson($dbRes);
    }

     function addApk(){
        $apk = request()->file('apk');
        if (empty($apk)) return Helper::reErrorJson(Errors::ATTACH_NOT_FIND);
        if (!$apk->checkSize(100 * 1024 * 1024)) return Errors::MAX_FILE_SIZE;
        $preName = DS .$apk->getInfo()['name'];
        $uploadRes = UploadHelper::upload($apk, $preName);
        return Helper::reJson4(is_array($uploadRes),is_array($uploadRes) ? $uploadRes[0] : $uploadRes);
    }

}

?>