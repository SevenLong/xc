<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/22 0022
 * Time: 14:41
 */
namespace app\improve\model;
use app\improve\controller\Errors;
use app\improve\controller\Helper;
use Exception;
use think\Db;
class LawFileDb
{
    static function add($data)
    {

        try {
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['update_time'] = $data['create_time'];
            $result = Db::table('b_law_file')->insertGetId($data);
            return $result > 0 ? $result : Errors::INSERT_ERROR;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    static function ls($data){
        try{
            $query = Db::table("b_law_file")->alias('vh');
            if (Helper::lsWhere($data, 'sort')) $query->where('vh.sort', $data['sort']);
            if (Helper::lsWhere($data, 'adder')) {
                $adder = Db::table("u_user")->where('name',$data['adder'])->field('uid')->find();
                $query->where('vh.adder', $adder['uid']);
            }
            if (Helper::lsWhere($data, 'create_time_min')) $query->where('vh.create_time', '>=', $data['create_time_min']);
            if (Helper::lsWhere($data, 'create_time_max')) $query->where('vh.create_time', '<=', $data['create_time_max']);
            $query->join('u_user m', 'm.uid = vh.adder', 'left');
            $query->field('vh.id,vh.create_time,vh.title,vh.sort,m.name');
            $query->order('vh.create_time', 'desc');
            $dataRes = $query->paginate($data['per_page'], false, ['page' => $data['current_page']])->toArray();
            return empty($dataRes) ? Errors::DATA_NOT_FIND : $dataRes;
        }catch (Exception $e){
            return $e->getMessage();
        }
    }

    static function query($id){
        try{
            $query = Db::table('b_law_file')->alias('vh')->where('vh.id', $id)
                ->join('u_user p', 'p.uid = vh.adder', 'left')
                ->field('vh.*,p.name')
                ->find();
            return $query;
        }catch (Exception $e){
            return $e->getMessage();
        }
    }

    static function queryAdder($id)
    {
        try {
            $dbRes = Db::table('b_law_file')->where('id', $id)->field('adder')->find();
            if (empty($dbRes)) return Errors::DATA_NOT_FIND;
            return $dbRes;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    static function edit($data){
        try {
            unset($data['create_time'], $data['adder']);
            $data['update_time'] = date('Y-m-d H:i:s');
            $dbRes = Db::table('b_law_file')->update($data);
            return $dbRes === 1 ? 1 : Errors::UPDATE_ERROR;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    static function deleteChecked($ids){
        try{
            $ret = [];
            foreach ($ids as $id) {
                $dbRes = self::queryAdder($id);
                if (!is_array($dbRes)) {
                    array_push($ret, ['id' => $id, 'res' => Errors::DATA_NOT_FIND]);
                    continue;
                }
                $dbRes = Db::table('b_law_file')->where('id', $id)->field('file_path')->find();
                $res = Db::table('b_law_file')->where('id',$id)->delete();
                unlink(iconv('UTF-8', 'GB2312', 'file'.DS.$dbRes['file_path']));
                array_push($ret, $res === 1 ? ['id' => $id, 'res' => 'delete success','file' => $dbRes['file_path']] : Errors::DELETE_ERROR);
            }
            return $ret;
        }catch (Exception $e){
            return $e->getMessage();
        }
    }
}