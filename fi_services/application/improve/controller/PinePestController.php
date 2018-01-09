<?php
/**
 * Created by sevenlong.
 * User: Administrator
 * Date: 2017/12/13 0013
 * Time: 10:50
 */

namespace app\improve\controller;

use app\improve\model\PinePestDb;
use app\improve\validate\PinePest;
use app\improve\validate\BaseValidate;
use think\Controller;
use think\Loader;
use think\Validate;

/*
 * 松材线虫病调查Controller
 */
class PinePestController extends Controller
{

    // 根据id查看
    function query()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'PinePest.id');
        if (true !== $result) return Helper::reErrorJson($result);
        $dbRes = PinePestDb::query($data);
        return is_array($dbRes) ? Helper::reSokJson($dbRes) : Helper::reErrorJson($dbRes);
    }

    // 条件查询
    function ls($requestType = true,$sample = false)
    {
        $result = $this->lsDb($requestType,$sample);
        return Helper::reJson4(is_array($result), $result);
    }

    function lsDb($requestType = true,$sample = false)
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return $auth;
        $data = $requestType ? Helper::getPostJson(): $_GET;
        $validate = new BaseValidate([
            'per_page' => 'require|number|max:50|min:1',
            'current_page' => 'require|number|min:1',
            'region' => 'max:20|region',
            'position_type' => 'number|in:-1,1,2,3',
            'surveryer' => 'max:16',
            'start_time' => 'dateFormat:Y-m-d',
            'end_time' => 'dateFormat:Y-m-d',
        ]);
        if (!$validate->check($data)) return $validate->getError();
        return $result = PinePestDb::ls($data, $sample);
    }

    function sampleLs()
    {
        return $this->ls(true,true);
    }

    // 添加
    function add()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = $_POST;
        $result = $this->validate($data, 'PinePest.add');
        if (true !== $result) return Helper::reErrorJson($result);
        $data['adder'] = $auth['s_uid'];
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['update_time'] = $data['create_time'];
        $images = request()->file("images");
        $result = PinePestDb::add($data, $images);
        return Helper::reJson4($result > 0, $result);
    }

    // 编辑
    function edit()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = $_POST;
        $result = $this->validate($data, 'PinePest.edit');
        if (true !== $result) return Helper::reErrorJson($result);
        $adder = PinePestDb::queryAdder($data['id'], "b_pineline_pest");
        if (!is_array($adder)) return Helper::reErrorJson(Errors::DATA_NOT_FIND);
        $a = Helper::checkAdderOrManage($adder, $auth['s_uid']);
        if (true !== $a) return Helper::reErrorJson(Errors::LIMITED_AUTHORITY);
        $images = request()->file("images");
        $dbRes = PinePestDb::edit($data, $images);
        return Helper::reJson4($dbRes === 1, $dbRes);
    }

    // 删除选中
    function deleteChecked()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'PinePest.ids');
        if (true !== $result) return Helper::reErrorJson($result);
        $ret = [];
        foreach ($data['ids'] as $id) {
            $dbRes = PinePestDb::queryAdder($id, "b_pineline_pest");
            if (!is_array($dbRes)) {
                array_push($ret, ['id' => $id, 'res' => Errors::DATA_NOT_FIND]);
                continue;
            }
            $isManage = is_array(Helper::auth([1]));
            if (!$isManage) {
                if ($auth['s_uid'] !== $dbRes['adder']) {
                    array_push($ret, ['id' => $id, 'res' => 'u are not a manager or not an adder']);
                    continue;
                }
            }
            $delRes = PinePestDb::deleteChecked($id, "b_pineline_pest");
            array_push($ret, $delRes === 1 ? array_push($ret, ['id' => $id, 'res' => 'delete success']) : Helper::reErrorJson($delRes));
        }
        return is_array($ret) ? Helper::reSokJson(array_values($ret)) : Helper::reErrorJson($ret);
    }

    function exportExcel()
    {
        $result = $this->lsDb($requestType = false);
        if (!is_array($result)) return Helper::reErrorJson($result);
        $result = $this->exportDataHandle($result);
        $name = '松材线虫调查记录表';
        $header = ['区域', '松林面积(亩)', '调查面积(亩)', '枯死松树数(株)', '松材线虫数(条)', '调查人', '调查时间'];
        Loader::import('org\Upload', EXTEND_PATH);
        excelExport($name, $header, $result);
    }

    private function exportDataHandle($result)
    {
        $dataRes = $result['data'];
        foreach ($dataRes as $key => $value) {
            $a['region'] = $dataRes[$key]['r3'].$dataRes[$key]['r2'] . $dataRes[$key]['r1'];
            $dataRes[$key] = $a + $dataRes[$key];
            unset($dataRes[$key]['id'],$dataRes[$key]['r3'],$dataRes[$key]['r2'], $dataRes[$key]['r1']);
        }
        return $dataRes;
    }

    function trendChart()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $validate = new BaseValidate([
            'region' => 'require|max:20|region',
            'start_time' => 'require|dateFormat:Y-m',
            'end_time' => 'require|dateFormat:Y-m',
        ]);
        if (!$validate->check($data)) return Helper::reErrorJson($validate->getError());
        $result = PinePestDb::trendChart($data);
        return Helper::reJson4(is_array($result), $result);
    }
}