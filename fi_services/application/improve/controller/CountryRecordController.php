<?php
/**
 * Created by PhpStorm.
 * User: LiuTao
 * Date: 2017/12/7/007
 * Time: 10:54
 */

namespace app\improve\controller;


use app\improve\model\CountryRecordDb;
use app\improve\validate\BaseValidate;
use think\console\command\Help;
use think\Controller;
use think\Loader;
use think\Validate;

/**
 * Created by LiuTao.
 * 村踏查记录
 */
class CountryRecordController extends Controller
{
    function add()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = $_POST;
        $result = $this->validate($data, 'CountryRecord.add');
        if (true !== $result) return Helper::reErrorJson($result);
        $data['adder'] = $auth['s_uid'];
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['update_time'] = $data['create_time'];
        $images = request()->file("images");
        $result = CountryRecordDb::add($data, $images);
        return Helper::reJson4($result > 0, $result);
    }

    //条件查询
    function ls($sample = false)
    {
        $result = $this->lsDb($sample);
        return Helper::reJson4(is_array($result), $result);
    }

    private function lsDb($sample = false, $download = false)
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return $auth;
        $data = $download ? $_GET : Helper::getPostJson();
//        $data = ["per_page"=>1,"current_page"=>1];
        $validate = new BaseValidate([
            'per_page' => 'require|number|max:500|min:1',
            'current_page' => 'require|number|min:1',
            'region' => 'max:20|region',
            'pest_id' => 'number',
            'plant_id' => 'number',
            'hazard_level' => 'in:1,2,3,4',
            'survey_time_min' => 'dateFormat:Y-m-d H:i:s',
            'survey_time_max' => 'dateFormat:Y-m-d H:i:s',
            'happen_level' => 'number|in:1,2,3,4',
            'position_type' => 'number|in:-1,1,2,3',
            'adder_name' => 'max:16',
        ]);
        if (!$validate->check($data)) return $validate->getError();
        return CountryRecordDb::ls($data, $sample);
    }

    function sampleLs()
    {
        return $this->ls(true);
    }

    function query()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'CountryRecord.id');
        if (true !== $result) return Helper::reErrorJson($result);
        $result = CountryRecordDb::query($data);
        return Helper::reJson4(is_array($result), $result);
    }

    // 村踏查删除多个ID记录
    function deleteChecked()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'CountryRecord.ids');
        if (true !== $result) return Helper::reErrorJson($result);
        $dbRes = CountryRecordDb::deleteChecked($data['ids'], $auth['s_uid']);
        return is_array($dbRes) ? Helper::reSokJson(array_values($dbRes)) : Helper::reErrorJson($dbRes);
    }

    // 村踏查编辑
    function edit()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = $_POST;
        $result = $this->validate($data, 'CountryRecord.edit');
        if (true !== $result) return Helper::reErrorJson($result);
        $adder = Helper::queryAdder($data['id'], "b_survey_record");
        if (!is_array($adder)) return Helper::reErrorJson(Errors::DATA_NOT_FIND);
        $a = Helper::checkAdderOrManage($adder, $auth['s_uid']);
        if (true !== $a) return Helper::reErrorJson(Errors::LIMITED_AUTHORITY);
        $images = request()->file("images");
        $dbRes = CountryRecordDb::edit($data, $images);
        return Helper::reJson4($dbRes === 1, $dbRes);
    }

    /**
     * 乡镇统计图
     */
    function villagesChart()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $validate = new BaseValidate([
            'regions' => 'require|array|length:1,3',
            'content' => 'require|in:1,2',
            'pest_id' => 'require|number',
            'year_min' => 'require|dateFormat:Y',
            'year_max' => 'require|dateFormat:Y',
        ]);
        if (!$validate->check($data)) return Helper::reErrorJson($validate->getError());
        $result = CountryRecordDb::villagesChart($data);
        return Helper::reJson4(is_array($result), $result);
    }

    function exportExcel()
    {
        $result = $this->lsDb(false, true);
        if (!is_array($result)) return Helper::reErrorJson($result);
        $result = $this->exportDataHandle($result);
        $name = '病虫害调查记录';
        $header = ['区域', '主测对象', '世代', '寄主', '发生程度', '分布面积(亩)', '预计成灾面积（亩）', '上报时间', '上报人'];
        Loader::import('org\Upload', EXTEND_PATH);
        header('Content-type: application/xls');
        excelExport($name, $header, $result);
    }


    private function exportDataHandle($result)
    {
        $dataRes = $result['data'];
        foreach ($dataRes as $key => $value) {
            unset($dataRes[$key]['id'], $dataRes[$key]['region'], $dataRes[$key]['pest_id'], $dataRes[$key]['plant_id']);
            $a['region'] = $dataRes[$key]['r2'] . $dataRes[$key]['r1'];
            $dataRes[$key] = $a + $dataRes[$key];
            unset($dataRes[$key]['r2'], $dataRes[$key]['r1']);
            foreach ($value as $k => $v) {
                $h = [2 => "轻", 3 => "中", 4 => "重",];
                $g = ["第一代", "第二代", "第三代", "越冬代", "第四代", "第五代", "第六代", "第七代",];
                if ($k == 'happen_level') $dataRes[$key][$k] = $h[$v];
                if ($k == 'generation') $dataRes[$key][$k] = $g[$v - 1];
            }
        }
        return $dataRes;
    }

    function villagesList(){
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $validate = new BaseValidate([
            'per_page' => 'require|number|max:500|min:1|notin:0',
            'current_page' => 'require|number|min:1|notin:0',
            'region' => 'require|max:20|region',
            'pest_id' => 'number',
            'survey_time_min' => 'dateFormat:Y-m',
            'survey_time_max' => 'dateFormat:Y-m',
        ]);
        if (!$validate->check($data)) return Helper::reErrorJson($validate->getError());
        $result = CountryRecordDb::villagesList($data);
        return Helper::reJson4(is_array($result), $result);
    }

    function exportExcela()
    {
//        $auth = Helper::auth();
//        if (!is_array($auth)) return Helper::reErrorJson($auth);
 //       $data = Helper::getPostJson();
        $data = $_GET;
        $validate = new BaseValidate([
            'per_page' => 'require|number|max:500|min:1|notin:0',
            'current_page' => 'require|number|min:1|notin:0',
            'region' => 'require|max:20|region',
            'pest_id' => 'number',
            'survey_time_min' => 'dateFormat:Y-m',
            'survey_time_max' => 'dateFormat:Y-m',
        ]);
        if (!$validate->check($data)) return '';
        $result = CountryRecordDb::villagesList($data);
        $name = $result['title'];
        $header = ['区域', '病虫种类', '死树株数', '分布面积(亩)', '成灾面积(亩)'];
        excelExport($name, $header, $result['data']);
    }

    function pestList(){
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $result = CountryRecordDb::pestList();
        return Helper::reJson4(is_array($result), $result);
    }
}