<?php
/**
 * Created by sevenlong.
 * User: Administrator
 * Date: 2017/12/28 0028
 * Time: 17:20
 */

namespace app\improve\controller;

use think\Controller;
use app\improve\validate\BaseValidate;
use app\improve\model\SamplePlotSurveyDb;
/*
 * 固定标准地调查Controller
 */
class SamplePlotSurveyController extends Controller
{
    // 添加
    function add()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = $_POST;
        $result = $this->validate($data, 'SamplePlotSurvey.add');
        if (true !== $result) return Helper::reErrorJson($result);
        $data['adder'] = $auth['s_uid'];
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['update_time'] = $data['create_time'];
        $images = request()->file("images");
        $result = SamplePlotSurveyDb::add($data,$images);
        return Helper::reJson4($result > 0,$result);
    }

    // 查看
    function query()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'SamplePlotSurvey.id');
        if (true !== $result) return Helper::reErrorJson($result);
        $dbRes = SamplePlotSurveyDb::query($data);
        return is_array($dbRes) ? Helper::reSokJson($dbRes) : Helper::reErrorJson($dbRes);
    }

    function queryInfo()
    {
        $data = Helper::getPostJson();
        $dbRes = SamplePlotSurveyDb::queryInfo($data);
        return is_array($dbRes) ? Helper::reSokJson($dbRes) : Helper::reErrorJson($dbRes);
    }

    //条件查询
    function ls($sample = false)
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $validate = new BaseValidate([
            'per_page' => 'require|number|max:500|min:1',
            'current_page' => 'require|number|min:1',
            'sample_plot_id' => 'number',
            'pest_id' => 'number',
            'surveyer' => 'max:16',
            'start_time' => 'dateFormat:Y-m-d H:i:s',
            'end_time' => 'dateFormat:Y-m-d H:i:s',
        ]);
        if (!$validate->check($data)) return $validate->getError();
        $result = SamplePlotSurveyDb::ls($data,$sample);
        return Helper::reJson4(is_array($result), $result);
    }

    function androidLs()
    {
        return $this->ls(true);
    }

    function sampleLs()
    {
        $result = SamplePlotSurveyDb::sampleLs();
        return Helper::reJson4(is_array($result), $result);
    }

    // 编辑
    function edit()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = $_POST;
        $result = $this->validate($data, 'SamplePlotSurvey.edit');
        if (true !== $result) return Helper::reErrorJson($result);
        $adder = SamplePlotSurveyDb::queryAdder($data['id'],"b_sample_plot_survey");
        if (!is_array($adder)) return Helper::reErrorJson(Errors::DATA_NOT_FIND);
        $a = Helper::checkAdderOrManage($adder, $auth['s_uid']);
        if (true !== $a) return Helper::reErrorJson(Errors::LIMITED_AUTHORITY);
        $images =  request()->file("images");
        $dbRes = SamplePlotSurveyDb::edit($data, $images);
        return Helper::reJson4($dbRes === 1, $dbRes);
    }

    // 删除选中
    function deleteChecked()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'SamplePlotSurvey.ids');
        if (true !== $result) return Helper::reErrorJson($result);
        $ret = [];
        foreach ($data['ids'] as $id) {
            $dbRes = SamplePlotSurveyDb::queryAdder($id,"b_sample_plot_survey");
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
            $delRes = SamplePlotSurveyDb::deleteChecked($id,"b_sample_plot_survey");
            array_push($ret, $delRes === 1 ? array_push($ret, ['id' => $id, 'res' => 'delete success']) : Helper::reErrorJson($delRes));
        }
        return is_array($ret) ? Helper::reSokJson(array_values($ret)) : Helper::reErrorJson($ret);
    }
}