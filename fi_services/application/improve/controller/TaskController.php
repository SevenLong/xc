<?php
/**
 * Created by PhpStorm.
 * User: LiuTao
 * Date: 2017/12/2/002
 * Time: 16:58
 */

namespace app\improve\controller;

use app\improve\validate\BaseValidate;
use app\improve\model\PestsDb;
use app\improve\model\TaskDb;
use app\improve\validate\Pests;
use app\improve\validate\Task;
use think\Controller;
use think\Validate;

class TaskController extends Controller
{

    function add()
    {
        $auth = Helper::auth([1]);
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = $_POST;
        $result = $this->validate($data, 'Task.add');
        if (true !== $result) return Helper::reErrorJson($result);
        $data['founder'] = $auth['s_uid'];
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['update_time'] = $data['create_time'];
        if (strtotime($data['deadline']) < time()) return Errors::DEADLINE_ERROR;
        $images =  request()->file("images");
        $dbRes = TaskDb::add($data, $images);
        return Helper::reJson4($dbRes > 0, $dbRes);
    }

    /**
     * 所有人都可以看详情
     */
    function query()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'Task.id');
        if (true !== $result) return Helper::reErrorJson($result);
        $dbRes = TaskDb::query($data['id']);//? Helper::reSokJson($dbRes) :
        return Helper::reJson4(is_array($dbRes), $dbRes);
    }

    function receive()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'Task.id');
        if (true !== $result) return Helper::reErrorJson($result);
        $dbRes = TaskDb::query($data['id']);//? Helper::reSokJson($dbRes) :
        if (!is_array($dbRes)) return Helper::reErrorJson($dbRes);
        //任务是否你是被指派人
        $assigners = $dbRes['assigner'];
        $flag = false;
        foreach ($assigners as $a) if ($auth['s_uid'] === $a['uid']) {
            $flag = true;
            break;
        }
        if (!$flag) return Helper::reErrorJson(Errors::ASSIGN_ERROR);
        //任务是否已发布与过期
        if ($dbRes['status'] !== 0) return Helper::reErrorJson(Errors::TASK_STATUS_ERROR_ONE);
        if (strtotime($dbRes['deadline']) < time()) return Helper::reErrorJson(Errors::TASK_EXPIRED);
        //数据库修改
        $task = [
            'id' => $data['id'],
            'status' => 1,
            'recevier' => $auth['s_uid'],
        ];
        $dbRes = TaskDb::edit($task);
        if ($dbRes === 1) return Helper::reSokJson();
        return Helper::reSokJson($dbRes);
    }

    // 删除选中
    function deleteChecked()
    {
        $auth = Helper::auth([1]);
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'Task.ids');
        if (true !== $result) return Helper::reErrorJson($result);
        $dbRes = TaskDb::deleteChecked($data['ids']);
        return is_array($dbRes) ? Helper::reSokJson(array_values($dbRes)) : Helper::reErrorJson($dbRes);
    }

    function finish()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = $_POST;
        $validate = new Validate([
            'id' => 'require|number',
            'result' => 'max:255'
        ]);
        if (!$validate->check($data)) return Helper::reErrorJson($validate->getError());
        $images =  request()->file("images");
        if(empty($images)) return Helper::reErrorJson("无反馈图片");
        if(count($images) > 6) return Helper::reErrorJson("反馈图片最多6张");
        $dbRes = TaskDb::query($data['id']);
        if (!is_array($dbRes)) return Helper::reErrorJson($dbRes);
        //任务是否你是接受人
        if ($dbRes['recevier'] !== $auth['s_uid']) return Helper::reErrorJson(Errors::NO_INCIDENT);
        //任务状态是否正在执行
        if ($dbRes['status'] !== 1) return Helper::reErrorJson(Errors::TASK_STATUS_ERROR_TWO);
        //任务是否过期
        if (strtotime($dbRes['deadline']) < time()) return Helper::reErrorJson(Errors::TASK_EXPIRED);
        $data['status'] = 2;
        $data['finish_time'] = date('Y-m-d H:i:s');
        $dbRes = TaskDb::edit($data, $images);
        return Helper::reJson4($dbRes === 1, $dbRes);
    }


    function ls()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $validate = new Validate([
            'per_page' => 'require|number|max:50|min:1',
            'current_page' => 'require|number|min:1',
            'name' => 'max:32',
            'type' => 'in:1,2',
            'status' => 'in:0,1,2,-2,-3',
            'founder_name' => 'max:16',
        ]);
        if (!$validate->check($data)) return Helper::reErrorJson($validate->getError());
        $dbRes = TaskDb::ls($data, $auth['s_uid']);
        if (is_array($dbRes)) return Helper::reSokJson($dbRes);
        return Helper::reErrorJson($dbRes);
    }

    function deleteImage()
    {
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $result = $this->validate($data, 'Task.deleteImage');
        if (true !== $result) return Helper::reErrorJson($result);
        //检测是否有这个id
        $task = TaskDb::queryPeople($data['id']);
        if (!is_array($task)) Helper::reErrorJson($task);
        $uid = $auth['s_uid'];
        switch ($data['image_use']) {
            case 1:
                if ($uid !== $task['founder']) return Helper::reErrorJson(Errors::IS_NOT_I);
                break;
            case 2:
                if ($uid !== $task['recevier']) return Helper::reErrorJson(Errors::NO_INCIDENT);
                break;
        }
        $dbRes = TaskDb::deleteImage($data['id'], $data['image_use'], $data['image_id']);
        return $dbRes === 1 ? Helper::reSokJson() : Helper::reErrorJson($dbRes);
    }

    function taskOverview(){
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $data = Helper::getPostJson();
        $validate = new BaseValidate([
            'app' => 'require|in:1,2',
            'type' => 'in:1,2',
            'position_type' => 'in:-1,1,2,-2,-3',
            'founder_name' => 'max:16',
            'create_time_min' => 'dateFormat:Y-m-d',
            'create_time_max' => 'dateFormat:Y-m-d',
        ]);
        if (!$validate->check($data)) return Helper::reErrorJson($validate->getError());
        $dbRes = TaskDb::taskOverview($data, $auth['s_uid']);
        if (is_array($dbRes)) return Helper::reSokJson($dbRes);
        return Helper::reErrorJson($dbRes);
    }

    function listPort(){
        $auth = Helper::auth();
        if (!is_array($auth)) return Helper::reErrorJson($auth);
        $result = TaskDb::listPort();
        return Helper::reJson4(is_array($result), $result);
    }
}