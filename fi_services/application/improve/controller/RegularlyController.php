<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/28 0028
 * Time: 11:32
 */
namespace app\improve\controller;
use think\Controller;
use app\improve\model\RegularlyDb;
use app\improve\validate\BaseValidate;

class RegularlyController extends Controller{
    public function add(){
        $auth = Helper::auth([1]);
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'Regularly.add');
        if (true !== $result) return Helper::reErrorJson($result);
        $result = RegularlyDb::add($data);
        return Helper::reJson4($result > 0,$result);
    }

    function ls()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $validate = new BaseValidate([
            'per_page' =>'require|number|max:50|min:1',
            'current_page' =>'require|number|min:1',
            'region' => 'max:20|region',
            'pests' => 'number',
            'plant' => 'number',
        ]);
        if (!$validate->check($data)) return Helper::reErrorJson($validate->getError());
        $result = RegularlyDb::ls($data);
        return Helper::reJson4(is_array($result), $result);
    }

    function query()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'Regularly.id');
        if (true !== $result) return Helper::reErrorJson($result);
        $dbRes = RegularlyDb::query($data);
        return is_array($dbRes) ? Helper::reSokJson($dbRes) : Helper::reErrorJson($dbRes);
    }

    function edit()
    {
        $auth = Helper::auth([1]);
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'Regularly.edit');
        if (true !== $result) return Helper::reErrorJson($result);
        $dbRes = RegularlyDb::query($data);
        if (!is_array($dbRes)) return Helper::reErrorJson(Errors::DATA_NOT_FIND);
        $dbRes = RegularlyDb::edit($data);
        return $dbRes === 1 ? Helper::reSokJson() : Helper::reErrorJson($dbRes);
    }

    function deleteChecked()
    {
        $auth = Helper::auth([1]);
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'Regularly.ids');
        if (true !== $result) return Helper::reErrorJson($result);
        $ret = [];
        foreach ($data['ids'] as $id) {
            $sum['id'] = $id;
            $dbRes = RegularlyDb::query($sum);
            if (!is_array($dbRes)) {
                array_push($ret, ['id' => $id, 'res' => Errors::DATA_NOT_FIND]);
                continue;
            }
            $bas['state'] = 2;
            $bas['id'] = $id;
            $delRes = RegularlyDb::editB( $bas);
            array_push($ret, $delRes === 1 ? array_push($ret, ['id' => $id, 'res' => 'delete success']) : Helper::reErrorJson($delRes));
        }
        return is_array($ret) ? Helper::reSokJson(array_values($ret)) : Helper::reErrorJson($ret);
    }

    function cartogram(){
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $validate = new BaseValidate([
            'region' => 'require|region',
            'pests' => 'require|number',
            'year' => 'require|array',
        ]);
        if (!$validate->check($data)) return Helper::reErrorJson($validate->getError());
        $result = RegularlyDb::cartogram($data);
        return Helper::reJson4(is_array($result), $result);
    }

}