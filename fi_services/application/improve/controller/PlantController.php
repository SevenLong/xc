<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/23
 * Time: 13:51
 */

namespace app\improve\controller;

use app\improve\controller\Helper;
use app\improve\model\PlantDb;
use app\improve\model\CommonDb;
use app\improve\validate\Plant;
use think\Controller;
use think\Error;
use think\Exception;
use think\File;
use think\Validate;

class PlantController extends Controller
{

    function ls($smaple = false)
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $validate = new Validate([
            'per_page' =>'require|number|max:500|min:1',
            'current_page' =>'require|number|min:1',
            'name' =>'max:16',
            'is_localed' =>'in:-1,1',
        ]);
        if (!$validate->check($data)) return Helper::reErrorJson($validate->getError());
        $dbRes = PlantDb::ls($data, $smaple);
        if (is_array($dbRes)) return Helper::reSokJson($dbRes);
        return Helper::reErrorJson($dbRes);
    }

    function sampleLs()
    {
      return $this->ls(true);
    }


    function local()
    {
        $auth = Helper::auth([1]);
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'Plant.local');
        if (true !== $result) return Helper::reErrorJson($result);
        $dbRes = PlantDb::local($data['ids']);
        return is_array($dbRes) ? Helper::reSokJson(array_values($dbRes)) : Helper::reErrorJson($dbRes);
    }

    function query()
    {
         $auth = Helper::auth();
         if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'Plant.query');
        if (true !== $result) return Helper::reErrorJson($result);
        $dbRes = PlantDb::query($data['id']);
        return is_array($dbRes) ? Helper::reSokJson($dbRes) : Helper::reErrorJson($dbRes);
    }

    function edit()
    {
        $auth = Helper::auth([1]);
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'Plant.edit');
        if (true !== $result) return Helper::reErrorJson($result);
        $dbRes = PlantDb::edit($data);
        return $dbRes === 1 ? Helper::reSokJson() : Helper::reErrorJson($dbRes);
    }

    function saveAttach()
    {
        $auth = Helper::auth([1]);
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = $_POST;
        $result = $this->validate($data, 'Plant.query');
        if (true !== $result) return Helper::reErrorJson($result);
        //检测是否有这个id
        $plant = PlantDb::queryAttachPath($data['id']);
        if (!is_array($plant)) Helper::reErrorJson($plant);
        //附件上传
        $attach = request()->file('attach');
        $data['attach_size'] = $attach->getSize();
        if (empty($attach)) return Helper::reErrorJson(Errors::ATTACH_NOT_FIND);
        if (!$attach->checkSize(100 * 1024 * 1024)) return Errors::MAX_FILE_SIZE;
        $preName = DS . 'plant' . DS . 'attach_' . $data['id'] . DS . $attach->getInfo()['name'];
        $uploadRes = $this->upload($attach, $preName);
        if (!is_array($uploadRes)) return Helper::reErrorJson($uploadRes);
        $data['attach'] = $uploadRes[0];
        $dbRes = PlantDb::edit2($data);
        if ($dbRes !== 1) return Helper::reErrorJson($dbRes);
        $path = $plant[$data['id']];
        if (!empty($path)) Helper::deleteFile($path);
        return Helper::reSokJson($data);
    }












    /*
     * 植物信息维护保存图片
     */
    function saveImage()
    {
        $auth = Helper::auth([1]);
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = $_POST;
        $result = $this->validate($data, 'Plant.id');
        if (true !== $result) return Helper::reErrorJson($result);
        //检测是否有这个id
        $plant = PlantDb::query($data['id']);
        if (!is_array($plant)) Helper::reErrorJson($plant);
        //看数据中已经有多少张图片了，最多允许6张,最大2M
        $imageCount = PlantDb::queryImageCount($data['id']);
        if (is_string($imageCount)) return Helper::reErrorJson($imageCount);
        if ($imageCount > 5) return Helper::reErrorJson(Errors::IMAGE_COUNT_ERROR);
        $image = request()->file('image');
        if (empty($image)) return Helper::reErrorJson(Errors::IMAGE_NOT_FIND);
        if (!$image->checkImg()) return Errors::FILE_TYPE_ERROR;
        if (!$image->checkSize(2 * 1024 * 1024)) return Errors::IMAGE_FILE_SIZE_ERROR;
        //上传
        $preName = DS . 'plant' . DS . 'image_' . $data['id'] . DS .$image->getInfo()['name'];
        $uploadRes = UploadHelper::upload($image, $preName);
        if (!is_array($uploadRes)) return Helper::reErrorJson($uploadRes);
        //更新数据库
        $dbRes = PlantDb::saveImage($data['id'], $uploadRes[0]);
        return  Helper::reJson4( is_array($dbRes), is_array($dbRes) ? ['path'=>$uploadRes[0], 'id'=>$dbRes[0]] : $dbRes);
    }

    /*
     * 植物信息维护删除图片
     */
    function deleteImage()
    {
        $auth = Helper::auth([1]);
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'Plant.id');
        if (true !== $result) return Helper::reErrorJson($result);
        //检测是否有这个id
        $plant = PlantDb::query($data['id']);
        if (!is_array($plant)) Helper::reErrorJson($plant);
        $dbRes = PlantDb::deleteImage($data['id'], $data['imageId']);
        return Helper::reJson4($dbRes === 1, $dbRes);
    }

    private function upload($file, $preName)
    {
        $info = $file->move(Errors::FILE_ROOT_PATH, $preName);
        if (!$info) return Errors::SAVE_FILE_ERROR;
        $imageUrl = $info->getRealPath();
        $imageUrl = strstr($imageUrl, "file");
        $imageUrl = substr($imageUrl, 5);
        return [$imageUrl];
    }
}

?>