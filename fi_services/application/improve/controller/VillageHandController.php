<?php
/**
 * Created by PhpStorm.
 * User: LiuTao
 * Date: 2017/12/2/002
 * Time: 16:58
 */

namespace app\improve\controller;

use app\improve\model\PestsDb;
use app\improve\model\TaskDb;
use app\improve\model\VillageHandDb;
use app\improve\validate\BaseValidate;
use app\improve\validate\Pests;
use app\improve\validate\Task;
use app\improve\validate\VillageHand;
use Composer\Util\Git;
use think\Controller;
use think\Loader;
use think\Validate;

class VillageHandController extends Controller
{

    function add()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = $_POST;
        $result = $this->validate($data, 'VillageHand.add');
        if (true !== $result) return Helper::reErrorJson($result);
        $data['adder'] = $auth['s_uid'];
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['update_time'] = $data['create_time'];
        $images = request()->file("images");
        $dbRes = VillageHandDb::add($data, $images);
        return Helper::reJson4($dbRes > 0, $dbRes);
    }

    function ls($sample = false)
    {
        $result = $this->lsDb($sample);
        return Helper::reJson4(is_array($result), $result);
    }

    private function lsDb($sample = false, $download = false){
        $auth = Helper::auth();
        if (!is_array($auth)) return $auth;
        $data = $download ? $_GET : Helper::getPostJson();
        $data['uid']  = $auth['s_uid'];
        $validate = new BaseValidate([
            'per_page' => 'require|number|max:50|min:1',
            'current_page' => 'require|number|min:1',
            'pest_id' => 'number',
            'hazard_type' => 'in:1,2,3,4',
            'hand_time_min' => 'dateFormat:Y-m-d H:i:s',
            'hand_time_max' => 'dateFormat:Y-m-d H:i:s',
            'region' => 'max:20|region',
            'position_type' => 'number|in:-1,1,2,3',
            'adder_name' => 'max:16',
        ]);
        if (!$validate->check($data)) return $validate->getError();
        return VillageHandDb::ls($data, $sample);
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
        $result = $this->validate($data, 'VillageHand.id');
        if (true !== $result) return Helper::reErrorJson($result);
        $dbRes = VillageHandDb::query($data['id']);
       return Helper::reJson4(is_array($dbRes), $dbRes);
    }

    function edit()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = $_POST;
        $result = $this->validate($data, 'VillageHand.edit');
        if (true !== $result) return Helper::reErrorJson($result);
        $adder = Helper::queryAdder($data['id'], "b_village_hand");
        if (!is_array($adder)) return Helper::reErrorJson(Errors::DATA_NOT_FIND);
        $a = Helper::checkAdderOrManage($adder, $auth['s_uid']);
        if (true !== $a) return Helper::reErrorJson(Errors::LIMITED_AUTHORITY);
        $images = request()->file("images");
        $dbRes = VillageHandDb::edit($data, $images);
        return Helper::reJson4($dbRes === 1, $dbRes);
    }

    // 删除选中
    function deleteChecked()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'VillageHand.ids');
        if (true !== $result) return Helper::reErrorJson($result);
        $dbRes = VillageHandDb::deleteChecked($data['ids'], $auth['s_uid']);
        return is_array($dbRes) ? Helper::reSokJson(array_values($dbRes)) : Helper::reErrorJson($dbRes);
    }

    function exportExcel()
    {
        $result = $this->lsDb(false, true);
        if (!is_array($result)) return Helper::reErrorJson($result);
        $result = $this->exportDataHandle($result);
        $name = '病虫害防治记录';
        $header = ['区域', '危害类型', '病虫种类', '防治方法', '防治时间', '上报人'];
        Loader::import('org\Upload', EXTEND_PATH);
        excelExport($name, $header, $result);
    }

    private function exportDataHandle($result)
    {
        $dataRes = $result['data'];
        foreach ($dataRes as $key => $value) {
            unset($dataRes[$key]['id'], $dataRes[$key]['pest_id']);
            $a['region'] = $dataRes[$key]['r2'] . $dataRes[$key]['r1'];
            $dataRes[$key] = $a + $dataRes[$key];
            unset($dataRes[$key]['r2'], $dataRes[$key]['r1']);
            foreach ($value as $k => $v) {
                $h = [1=>"病害",2 => "虫害", 3 => "鼠害", 4 => "有害植物",];
                $m = ["生物防治", "物理防治", "化学防治","人工防治"];
                if ($k == 'hazard_type') $dataRes[$key][$k] = $h[$v];
                if ($k == 'hand_method') $dataRes[$key][$k] = $m[$v - 1];
            }
        }
        return $dataRes;
    }

    function getPestsType()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $dbRes = VillageHandDb::getPestsType();
        if (!is_array($dbRes)) return Helper::reErrorJson($dbRes);
        return Helper::reSokJson($dbRes);
    }

    function messageChart()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $validate = new BaseValidate([
            'region' => 'require|max:20|region',
            'pest_id' => 'require|number',
            'start_time' => 'require|dateFormat:Y-m',
            'end_time' => 'require|dateFormat:Y-m',
        ]);
        if (!$validate->check($data)) return Helper::reErrorJson($validate->getError());
        $result = VillageHandDb::messageChart($data);
        return Helper::reJson4(is_array($result), $result);
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
        $result = VillageHandDb::villagesList($data);
        return Helper::reJson4(is_array($result), $result);
    }

    function exportExcela()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = $_GET;
        $validate = new BaseValidate([
            'per_page' => 'require|number|max:500|min:1|notin:0',
            'current_page' => 'require|number|min:1|notin:0',
            'region' => 'require|max:20|region',
            'pest_id' => 'number',
            'survey_time_min' => 'dateFormat:Y-m',
            'survey_time_max' => 'dateFormat:Y-m',
        ]);
        if (!$validate->check($data)) return "";
        $result = VillageHandDb::villagesList($data);
        $name = $result['title'];
        $header = ['区域', '病虫种类', '发生面积(亩)', '防治面积(亩)', '防治费用(元)','挽回灾害面积(亩)','防治次数'];
        excelExport($name, $header, $result['data']);
    }

    function pestList(){
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $result = VillageHandDb::pestList();
        return Helper::reJson4(is_array($result), $result);
    }


}