<?php
/**
 * Created by PhpStorm.
 * User: LiuTao
 * Date: 2017/12/5/005
 * Time: 16:20
 */

namespace app\improve\controller;


use app\improve\model\CommonDb;
use think\Controller;
use think\Validate;
use think\File;

class VersionManagerController extends Controller
{

    /**
     * 版本更新
     */
    function version_update()
    {
        $data = Helper::getPostJson();
        $validate = new Validate([
            'version_code' => 'require|number',
        ]);
        if (!$validate->check($data)) return Helper::reErrorJson($validate->getError());
        $maxVersion = CommonDb::getMaxVersion();
        if (!is_array($maxVersion)) return Helper::reJson4(false, $maxVersion);
        if ($data['version_code'] >= $maxVersion[0]) return Helper::reJson4(false, Errors::NEW_VERSION_NOT_FIND);
       $versionInfo = CommonDb::getVersionInfo($maxVersion[0]);
       return Helper::reJson4(is_array($versionInfo), $versionInfo);
    }



    function addApk(){
        $auth = Helper::auth([1]);
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = $_POST;
        $validate = new Validate([
            'version_num' => 'require|max:32',
            'content' => 'require|max:255',
            'force' => 'in:0,1',
        ]);
        if (!$validate->check($data)) return Helper::reErrorJson($validate->getError());
        $apk = request()->file('apk');
        if (empty($apk)) return Helper::reErrorJson(Errors::ATTACH_NOT_FIND);
        if (!$apk->checkSize(100 * 1024 * 1024)) return Errors::MAX_FILE_SIZE;
        $preName = DS .'apk'.DS.$apk->getInfo()['name'];
        $uploadRes = UploadHelper::upload($apk, $preName);
        if (!is_array($uploadRes)) return Helper::reErrorJson($uploadRes);
        //数据库修改
        $data['update_person'] = $auth['s_uid'];
        $data['down_url'] = request()->host().DS.'file'.DS.$uploadRes[0];
        $dbRes = CommonDb::addVersion($data);
        return Helper::reJson4(is_array($dbRes), $dbRes);
    }

    function editVersioin(){
        $auth = Helper::auth([1]);
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = $_POST;
        $validate = new Validate([
            'version_code' => 'require|number',
            'version_num' => 'require|max:32',
            'content' => 'require|max:255',
            'force' => 'in:0,1',
            'down_url' => 'length:1-100',
        ]);
        if (!$validate->check($data)) return Helper::reErrorJson($validate->getError());
        //是否存在version信息
        $vf = CommonDb::getVersionInfo($data['version_code']);
        if (!is_array($vf)) return Helper::reJson4(false, $vf);
        $apk = request()->file('apk');
        if (!empty($apk)) {
            if (!$apk->checkSize(100 * 1024 * 1024)) return Errors::MAX_FILE_SIZE;
            $preName = DS .'apk'.DS.$apk->getInfo()['name'];
            $uploadRes = UploadHelper::upload($apk, $preName);
            if (!is_array($uploadRes)) return Helper::reErrorJson($uploadRes);
            //删除原文件
            Helper::deleteFile($vf['down_url']);
            $data['down_url'] = request()->host().DS.'file'.DS.$uploadRes[0];
        }
        $data['update_person'] = $auth['s_uid'];
        //更新数据库
        $dbRes = CommonDb::updateVersion($data);
        return Helper::reJson4(is_array($dbRes), $dbRes);
    }

}