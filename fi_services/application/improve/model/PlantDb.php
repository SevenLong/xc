<?php

namespace app\improve\model;

use app\improve\controller\Errors;
use app\improve\controller\Helper;
use Exception;
use think\Db;

/**
 * 系统设置接口数据库操作
 * Created by xwpeng.
 */
class PlantDb
{

    static function ls($data, $sample = false)
    {
        try {
            $query = Db::table("b_plant");
            if (Helper::lsWhere($data,'name')) $query ->where('cn_name', $data['name']);
            if (Helper::lsWhere($data,'is_localed')) $query ->where('is_localed', $data['is_localed']);
            $query->order("update_time", 'desc');
            if ($sample)$query->field('id, cn_name');
            $dataRes = $query->paginate($data['per_page'],false,['page'=>$data['current_page']])->toArray();
            return empty($dataRes) ? Errors::DATA_NOT_FIND : $dataRes;
        } catch (\think\Exception $e) {
            return $e->getMessage();
        }
    }

    static function local($ids)
    {
        try {
            $ret = [];
            foreach ($ids as $id) {
                $res = Db::table('b_plant')->where('id', $id)->column('id');
                if (empty($res)) array_push($ret, ['id' => $id, 'res' => Errors::DATA_NOT_FIND]);
                else {
                    $res = Db::table('b_plant')->where('id', $id)->update(['is_localed' => 1,'update_time' => date('Y-m-d H:i:s', time())]);
                    array_push($ret, ['id' => $id, 'res' => $res]);
                }
            }
            return $ret;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    static function edit($data)
    {
        try {
            Db::startTrans();
            $u = [
                "id"=>$data['id'],
                "introduce"=>$data['introduce'],
                "update_time"=>date('Y-m-d H:i:s'),
            ];
            if ($data['attach'] === -1) {
                //删附件
//                Helper::deleteFile('plant/attach_'.$data['id']);
                $u['attach'] = null;
                $u['attach_size'] = null;
            }
            Db::table('b_plant')->update($u);
            Db::commit();
            return 1;
        } catch (Exception $e) {
            Db::rollback();
            return $e->getMessage();
        }
    }

    static function query($id)
    {
        try {
            $plant = Db::table('b_plant')
                ->where('id', $id)
                ->where('is_localed', 1)
                ->select()[0];
            if (empty($plant)) return Errors::DATA_NOT_FIND;
            $plant['images'] = Db::table('b_plant_image')->where('b_plant_id', $id)->select();
            return $plant;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    static function queryAttachPath($id)
    {
        try {
            $plant = Db::table('b_plant')
                ->where('id', $id)
                ->where('is_localed', 1)
                ->column('id,attach');
            if (empty($plant)) return Errors::DATA_NOT_FIND;
            return $plant;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    static function deleteImage($id, $imageId)
    {
        try {
            $dbRes = Db::table('b_plant_image')
                ->where('id', $imageId)
                ->where('b_plant_id', $id)
                ->delete();
            return $dbRes === 1 ? 1 : Errors::DELETE_ERROR;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    static function saveImage($id, $path)
    {
        try {
            $dbRes = Db::table('b_plant_image')
                ->insertGetId([
                    'b_plant_id'=>$id,
                    'path'=>$path,
                ]);
            return $dbRes > 0 ? [$dbRes] : Errors::INSERT_ERROR;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    static function queryImageCount($id)
    {
        try {
            return $dbRes = Db::table('b_plant_image')
                ->where('b_plant_id',$id)
                ->field('path')->count('*');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    static function edit2($data)
    {
        try {
            $dbRes =  Db::table("b_plant")->update($data);
            if ($dbRes === 1) return $dbRes;
            return Errors::UPDATE_ERROR;
        } catch (Exception $e) {
            Db::rollback();
            return $e->getMessage();
        }
    }

}