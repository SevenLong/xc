<?php
/**
 * Created by sevenlong.
 * User: Administrator
 * Date: 2017/12/13 0013
 * Time: 11:35
 */

namespace app\improve\model;

use app\improve\controller\Errors;
use app\improve\controller\Helper;
use app\improve\controller\UploadHelper;
use think\Db;
use think\Exception;

/*
 * 松材线虫病调查DB层
 */
class PinePestDb extends BaseDb
{

    // 根据id查询
    static function query($data)
    {
        try {
            $dbRes = Db::table('b_pineline_pest')->alias('plp')->where('plp.id', $data['id'])
                ->join('c_region r', 'r.id = plp.region', 'left')
                ->join('c_region r2', 'r.parentId = r2.id', 'left')
                ->join('c_region r3', 'r2.parentId = r3.id', 'left')
                ->join('u_user u', 'u.uid = plp.adder', 'left')
                ->field('plp.*,r.name r1,r2.name r2,r3.name r3, u.name surveyer')
                ->find();
            $dbRes['images'] = Db::table('b_pineline_pest_image')->where('pineline_pest_id', $data['id'])->select();
            return empty($dbRes) ? Errors::DATA_NOT_FIND : $dbRes;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    // 条件查询
    static function ls($data, $simple)
    {
        try {
            $query = Db::table('b_pineline_pest')->alias('plp');
            if (Helper::lsWhere($data, 'region')) $query->whereLike('plp.region', $data['region'] . '%');
            if (Helper::lsWhere($data, 'position_type')) $query->where('plp.position_type', $data['position_type']);
            if (Helper::lsWhere($data, 'start_time')) $query->where('plp.create_time', '>=', $data['start_time']);
            if (Helper::lsWhere($data, 'end_time')) $query->where('plp.create_time', '<=', $data['end_time']);
            if (Helper::lsWhere($data, 'surveyer')) {
                $query->join('u_user u', 'u.uid = plp.adder', 'left')
                    ->whereLike('u.name', '%' . $data['surveyer'] . '%');
            }
            $query->join('u_user u1', 'u1.uid = plp.adder', 'left');
            if ($simple) {
                $query->field('plp.id, plp.survey_area, u1.name surveyer, plp.create_time ,plp.positions');
            } else {
                $query->join('c_region r', 'r.id = plp.region', 'left')
                    ->join('c_region r2', 'r.parentId = r2.id', 'left')
                    ->join('c_region r3', 'r2.parentId = r3.id', 'left')
                    ->field('plp.id, r.name r1, r2.name r2, r3.name r3, plp.pinewood_area, plp.survey_area, plp.dead_pine_num,
                 plp.pineline_pest,plp.positions, u1.name surveyer, plp.create_time');
            }
            $query->order('plp.update_time', 'desc');
            $dataRes = $query->paginate($data['per_page'], false, ['page' => $data['current_page']])->toArray();
            return empty($dataRes) ? Errors::DATA_NOT_FIND : $dataRes;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    // 添加记录
    static function add($data,$images)
    {
        try {
            unset($data['id']);
            Db::startTrans();
            $dbRes = Db::table('b_pineline_pest')->insertGetId($data);
            if ($dbRes < 1) throw new Exception(Errors::ADD_ERROR);
            if (!empty($images)) {
                if (count($images) > 6) throw new Exception("图片数量不能超过6张");
                foreach ($images as $image) {
                    $path = self::uplodImage($image, $dbRes);
                    if (!is_array($path)) throw new Exception($path);
                    $a = Db::table('b_pineline_pest_image')->insert(['pineline_pest_id'=>$dbRes, 'path'=>$path[0]]);
                    if ($a < 1) throw new Exception(Errors::IMAGES_INSERT_ERROR);
                }
            }
            Db::commit();
            return $dbRes;
        } catch (Exception $e) {
            Db::rollback();
            return $e->getMessage();
        }
    }

    static function uplodImage($image, $id){
        $a = Helper::checkImage(self::queryImageCount($id), $image);
        if (true !== $a) return $a;
        $preName = DS . 'pineline_pest' . DS . 'image_'.$id . DS . $image->getInfo()['name'];
        return UploadHelper::upload($image, $preName);
    }

    // 编辑
    static function edit($data, $images)
    {
        try {
            $paths = [];
            Db::startTrans();
            if (Helper::lsWhere($data,'del_images')){
                $del_images = $data['del_images'];
                $paths = Db::table('b_pineline_pest_image')->field('path')->where('pineline_pest_id', $data['id'])->whereIn('id',$del_images)->select();
                if (count($paths) !== count($del_images)) throw new Exception('删除的图片未找到');
                $delRes = Db::table('b_pineline_pest_image')->whereIn('id',$del_images)->delete();
                if ($delRes !== count($del_images)) throw new Exception('删除失败');
            }
            unset($data['del_images']);
            $data['update_time'] = date('Y-m-d H:i:s');
            $dbRes = Db::table('b_pineline_pest')->field('region,positions,position_type,pinewood_area,survey_area,dead_pine_num,sampling_num,
            sampling_part_up,sampling_part_center,sampling_part_down,noline_pest,quasilinear_pest,pineline_pest,otherline_pest,update_time')->update($data);
            if (!empty($images)) {
                $haveCount = Db::table('b_pineline_pest_image')->where('pineline_pest_id',$data['id'])->count('*');
                if ($haveCount + count($images) > 6) throw new Exception('图片不能超过6张');
                foreach ($images as $image) {
                    $path = UploadHelper::uplodImage($image, DS . 'pine_pest' . DS . 'image_' .$data['id']);
                    if (!is_array($path)) throw new Exception($path);
                    $a = Db::table('b_pineline_pest_image')->insert(['pineline_pest_id' => $data['id'], 'path' => $path[0]]);
                    if ($a < 1) throw new Exception(Errors::IMAGES_INSERT_ERROR);
                }
            }
            Db::commit();
            if (!empty($paths)) foreach ($paths as $path) Helper::deleteFile($path['path']);
            return $dbRes === 1 ? 1 : Errors::UPDATE_ERROR;
        } catch (Exception $e) {
            Db::rollback();
            return $e->getMessage();
        }
    }

    // 查询图片数量
    static function queryImageCount($id)
    {
        try {
            return $dbRes = Db::table('b_pineline_pest_image')
                ->where('pineline_pest_id', $id)
                ->field('path')->count('*');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    static function deleteImage($id, $imageId)
    {
        try {
            $dbRes = Db::table('b_pineline_pest_image')
                ->where('id', $imageId)
                ->where('pineline_pest_id', $id)
                ->delete();
            return $dbRes === 1 ? 1 : Errors::DELETE_ERROR;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    static function trendChart($data)
    {
        try {
            $query = Db::table('b_pineline_pest')->alias('plp')
                ->join('c_region r1', 'r1.id = plp.region', 'left')
                ->join('c_region r2', 'r1.parentId = r2.id', 'left')
                ->whereLike('plp.region', $data['region'] . '%')
                ->where('plp.create_time', '>=', $data['start_time'])
                ->where('plp.create_time', '<=',date("Y-m",strtotime($data['end_time'].'+1 month')))
                ->group('DATE_FORMAT(plp.create_time, "%Y-%m")')
                ->field("SUM(plp.survey_area) '调查面积(亩)',SUM(plp.dead_pine_num) '枯死数(株)',DATE_FORMAT(plp.create_time,'%Y-%m') '年月',
                COUNT(DISTINCT r2.name) '受灾乡镇数(个)'");
            $dbRes = $query->select();
            $query->getLastSql();
            return empty($dbRes) ? Errors::DATA_NOT_FIND : $dbRes;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}