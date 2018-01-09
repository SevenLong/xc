<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/22 0022
 * Time: 10:04
 */
namespace app\improve\controller;
use app\improve\model\LawFileDb;
use app\improve\validate\Pests;
use think\Controller;
use think\Validate;
use app\improve\validate\BaseValidate;
class LawFileController extends Controller
{
   public function add(){
       $auth = Helper::auth([1]);
       if (!is_array($auth)) return Helper::reErrorJson($auth);
       $data = $_POST;
       unset($data['submit']);
       $result = $this->validate($data, 'LawFile.add');
       if (true !== $result) return Helper::reErrorJson($result);
       $data['adder'] = $auth['s_uid'];
       $attach = request()->file('attach');
       if (empty($attach)) return Helper::reErrorJson(Errors::ATTACH_NOT_FIND);
       if (!$attach->checkSize(100 * 1024 * 1024)) return Errors::MAX_FILE_SIZE;
       $name=$attach->getInfo()['name'];
       $preName = DS . 'law' . DS . 'attach_' . $data['adder'] . DS . $name;
       $result = UploadHelper::upload($attach, $preName);
       if (!is_array($result)) return Helper::reErrorJson($result);
       $data['file_path'] = $result[0];
       $data['file_name'] =  $name;
       $result[1] = LawFileDb::add($data);
       if (!$result>0) {
           unlink(iconv('UTF-8', 'GB2312', 'file'.DS.$data['file_path']));
           return Errors::UPDATE_ERROR;
       }
       return Helper::reJson4($result > 0,$result);
       }

       public function ls(){
           $auth = Helper::auth();
           if (!is_array($auth)) return Helper::reErrorJson($auth);
           $data = Helper::getPostJson();
           $validate = new BaseValidate([
               'create_time_min' => 'dateFormat:Y-m-d H:i:s',
               'create_time_max' => 'dateFormat:Y-m-d H:i:s',
               'per_page' => 'require|number',
               'current_page' => 'require|number',
               'adder' => 'max:32',
               'sort' => 'in:1,2',
           ]);
           if (!$validate->check($data)) return Helper::reErrorJson($validate->getError());
           $dbRes = LawFileDb::ls($data);
           if (is_array($dbRes)) return Helper::reSokJson($dbRes);
           return Helper::reErrorJson($dbRes);
       }

       public function edit() {
           $auth = Helper::auth([1]);
           if (!is_array($auth)) return Helper::reErrorJson($auth);
           $data = $_POST;
           unset($data['submit']);
           $result = $this->validate($data, 'LawFile.edit');
           if (true !== $result) return Helper::reErrorJson($result);
           $attach = request()->file('attach');
           if (!empty($attach)) {
               $name=$attach->getInfo()['name'];
               if (!$attach->checkSize(100 * 1024 * 1024)) return Errors::MAX_FILE_SIZE;
               $preName = DS . 'law' . DS . 'attach_' . $data['adder'].DS.$name;
               $uploadRes = UploadHelper::upload($attach, $preName);
               if (is_array($uploadRes)) {
                       unlink(iconv('UTF-8', 'GB2312', 'file'.DS.$data['file_path']));
               }else{
                   return Helper::reErrorJson($uploadRes);
               }

               $data['file_path'] = $uploadRes[0];
               $data['file_name'] = $name;
           }
           $dbRes = LawFileDb::edit($data);
           return $dbRes === 1 ? Helper::reSokJson() : Helper::reErrorJson($dbRes);

       }

       public function query(){
           $auth = Helper::auth();
           if (!is_array($auth)) return Helper::reErrorJson($auth);
           $data = Helper::getPostJson();
           $result = $this->validate($data, 'LawFile.id');
           if (true !== $result) return Helper::reErrorJson($result);
           $dbRes = LawFileDb::query($data['id']);
           $dbRes['file_size']  =  Helper::sizecount(filesize ( iconv('UTF-8', 'GB2312', 'file'.DS.$dbRes['file_path'])));

           if (!is_array($dbRes)) return Helper::reErrorJson($dbRes);
           return Helper::reSokJson($dbRes);
       }



       public function delete(){
           $auth = Helper::auth([1]);
           if (!is_array($auth)) return Helper::reErrorJson($auth);
           $data = Helper::getPostJson();
           $result = $this->validate($data, 'LawFile.ids');
           if (true !== $result) return Helper::reErrorJson($result);
           $dbRes = LawFileDb::deleteChecked($data['ids']);
           return is_array($dbRes) ? Helper::reSokJson(array_values($dbRes)) : Helper::reErrorJson($dbRes);
       }
}