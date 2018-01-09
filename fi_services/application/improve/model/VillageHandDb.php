<?php

namespace app\improve\model;

use app\improve\controller\Errors;
use app\improve\controller\Helper;
use app\improve\controller\UploadHelper;
use Exception;
use think\Db;
use think\Model;

/**
 * 病虫害防治
 * Created by xwpeng.
 */
class VillageHandDb
{

    static function add($data, $images)
    {
        try {
            Db::startTrans();
            $dbRes = Db::table('b_village_hand')->insertGetId($data);
            if ($dbRes < 1) throw new Exception(Errors::INSERT_ERROR);
            if (!empty($images)) {
                if (count($images) > 6) throw new Exception("图片数量不能超过6张");
                foreach ($images as $image) {
                    $path = UploadHelper::uplodImage($image, DS . 'village_hand' . DS . 'image_' . $dbRes);
                    if (!is_array($path)) throw new Exception($path);
                    $a = Db::table('b_village_hand_image')->insert(['village_hand_id' => $dbRes, 'path' => $path[0]]);
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


    static function ls($data, $sample = false)
    {
        try {
            $query = Db::table("b_village_hand")->alias('vh');
            if (Helper::lsWhere($data, 'hazard_type')) $query->where('vh.hazard_type', $data['hazard_type']);
            if (Helper::lsWhere($data, 'region')) $query->whereLike('vh.region', $data['region'] . '%');
            if (Helper::lsWhere($data, 'pest_id')) $query->where('vh.pest_id', $data['pest_id']);
            if (Helper::lsWhere($data, 'hand_time_min')) $query->where('vh.create_time', '>=', $data['hand_time_min']);
            if (Helper::lsWhere($data, 'hand_time_max')) $query->where('vh.create_time', '<=', $data['hand_time_max']);
            if (Helper::lsWhere($data, 'position_type')) $query->where('vh.position_type', $data['position_type']);
            if (Helper::lsWhere($data, 'adder_name')) {
                $query->view('u_user u3', 'name adder_name', "uid = vh.adder");
                $query->whereLike('u3.name', '%' . $data['adder_name'] . '%');
            }
            if ($sample) {
                $query->field('vh.id,vh.create_time, vh.hazard_type, vh.hand_method, vh.positions');
            } else {
                $query->join('b_pests p', 'p.id = vh.pest_id', 'left');
                $query->join('c_region r', 'r.id = vh.region', 'left');
                $query->join('c_region r2', 'r.parentId = r2.id', 'left');
                $query->join('u_user u', 'u.uid = vh.adder', 'left');
                $query->field('vh.id,r.name r1,r2.name r2, vh.hazard_type, p.cn_name pest_name,vh.pest_id, vh.hand_method,vh.create_time, u.name adder_name');
            }
            $query->order('vh.update_time', 'desc');
            $dataRes = $query->paginate($data['per_page'], false, ['page' => $data['current_page']])->toArray();
            return empty($dataRes) ? Errors::DATA_NOT_FIND : $dataRes;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    static function query($id)
    {
        try {
            $hand = Db::table('b_village_hand')->alias('vh')->where('vh.id', $id)
                ->join('b_pests p', 'p.id = vh.pest_id', 'left')
                ->join('c_region r', 'r.id = vh.region', 'left')
                ->join('c_region r2', 'r.parentId = r2.id', 'left')
                ->join('c_region r3', 'r2.parentId = r3.id', 'left')
                ->join('u_user u', 'u.uid = vh.adder', 'left')
                ->field('vh.*, r.name r1, r2.name r2, r3.name r3, p.cn_name pest_name, u.name adder_name')
                ->find();
            if (empty($hand)) return Errors::DATA_NOT_FIND;
            $hand['images'] = Db::table('b_village_hand_image')->where('village_hand_id', $id)->select();
            return $hand;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    static function edit($data, $images)
    {
        try {
            $paths = [];
            Db::startTrans();
            if (Helper::lsWhere($data, 'del_images')) {
                $del_images = $data['del_images'];
                $paths = Db::table('b_village_hand_image')->field('path')->where('village_hand_id', $data['id'])->whereIn('id', $del_images)->select();
                if (count($paths) !== count($del_images)) throw new Exception('删除的图片未找到');
                $delRes = Db::table('b_village_hand_image')->whereIn('id', $del_images)->delete();
                if ($delRes !== count($del_images)) throw new Exception('删除失败');
            }
            unset($data['del_images']);
            $data['update_time'] = date('Y-m-d H:i:s');
            $dbRes = Db::table('b_village_hand')
                ->field("region, hazard_type,happen_time, 
             hand_method, drug_amount, hand_cost, hand_area,
             happen_area, hand_effect, save_pest_area, positions,
             position_type, id,drug_name,update_time,pest_id")
                ->update($data);
            //图片上传
            if (!empty($images)) {
                //数量判断
                $haveCount = Db::table('b_village_hand_image')->where('village_hand_id', $data['id'])->count('*');
                if ($haveCount + count($images) > 6) throw new Exception('图片不能超过6张');
                foreach ($images as $image) {
                    $path = UploadHelper::uplodImage($image, DS . 'village_hand' . DS . 'image_' . $data['id']);
                    if (!is_array($path)) throw new Exception($path);
                    $a = Db::table('b_village_hand_image')->insert(['village_hand_id' => $data['id'], 'path' => $path[0]]);
                    if ($a < 1) throw new Exception(Errors::IMAGES_INSERT_ERROR);
                }
            }
            Db::commit();
            //物理
            if (!empty($paths)) foreach ($paths as $path) Helper::deleteFile($path['path']);
            return $dbRes === 1 ? 1 : Errors::UPDATE_ERROR;
        } catch (Exception $e) {
            Db::rollback();
            return $e->getMessage();
        }
    }

    // 删除选中
    static function deleteChecked($ids, $suid)
    {
        try {
            $ret = [];
            foreach ($ids as $id) {
                $adder = Helper::queryAdder($id, "b_village_hand");
                if (!is_array($adder)) {
                    array_push($ret, ['id' => $id, 'res' => Errors::DATA_NOT_FIND]);
                    continue;
                }
                if (true !== Helper::checkAdderOrManage($adder, $suid)) {
                    array_push($ret, ['id' => $id, 'res' => 'u are not a manager or not an adder']);
                    continue;
                }
                $res = Db::table('b_village_hand')->where('id', $id)->delete();
                array_push($ret, $res === 1 ? ['id' => $id, 'res' => 'delete success'] : Errors::DELETE_ERROR);
            }
            return $ret;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    static function getPestsType()
    {
        try {
            $dbRes = Db::table('b_village_hand')->alias('vh')
                ->join('b_pests p', 'p.id = vh.pest_id', 'left')
                ->field('DISTINCT p.cn_name,vh.pest_id')->select();
            return empty($dbRes) ? Errors::DATA_NOT_FIND : $dbRes;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    static function messageChart($data)
    {
        try {
            $query = Db::table('b_village_hand')->alias('vh')
                ->join('c_region r1', 'r1.id = vh.region', 'left')
                ->where('vh.pest_id', '=', $data['pest_id'])
                ->whereLike('vh.region', $data['region'] . '%')
                ->where("vh.create_time", '>=', $data['start_time'])
                ->where('vh.create_time', '<=',date("Y-m",strtotime($data['end_time'].'+1 month')))
                ->group('DATE_FORMAT(vh.create_time, "%Y-%m")')
                ->field("SUM(vh.happen_area) '发生面积(亩)', SUM(vh.hand_area) '防治面积(亩)', SUM(vh.save_pest_area) '挽回灾害面积(亩)',
	                        DATE_FORMAT(vh.create_time, '%Y-%m') '年月', COUNT(*) '防治次数(次)'");
            $dbRes = $query->select();
            return empty($dbRes) ? Errors::DATA_NOT_FIND : $dbRes;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }


    static function villagesListSon($data = false){
        $a = 'sum(vh.happen_area) happen_sum,sum(vh.hand_area) hand_area_sum,sum(vh.hand_cost) hand_cost_sum,sum(vh.save_pest_area) save_pest_area_sum,sum(vh.hand_cost) hand_cost_sum ,count(vh.id) sum';
        $b = 'count(distinct vh.region) region,';
        $c = 'rga.name regions,rgb.name r2,vh.region,';
        $f = 'count(distinct p.id) pest,';
        $g = 'p.cn_name pest,';
        $query = Db::table('b_village_hand')->alias('vh');
        if($data) {
            if (Helper::lsWhere($data, 'pest_id')) {
                $f = $g;
                $query->where('vh.pest_id', $data['pest_id']);
            }
            $d = $c.$f.$a;
        }else{
            $d = $b.$f.$a;
        }
        $query  ->join('b_pests p', 'p.id = vh.pest_id', 'left')
            ->join('c_region rga', 'rga.id = vh.region', 'left')
            ->join('c_region rgb', 'rga.parentId = rgb.id', 'left')
            ->field($d);
        if($data){
            $dataRes = $query   ->group('vh.region')
                ->order('vh.region', 'desc')
                ->paginate($data['per_page'], false, ['page' => $data['current_page']])
                ->toArray();
        }
        else{
            $dataRes = $query ->find();
        }
        return $dataRes;
    }

    static function villagesList($data){
        try {
            $pests = '';
            $dataResOne = VillageHandDb::villagesListSon();
            $dataResTwo = VillageHandDb::villagesListSon($data);
            $bbc=[];
            foreach ($dataResOne as $key =>$value){
                switch ($key)
                {
                    case "region":$bbc[0][$key]="防治地区数:".$value."个";
                        break;
                    case "pest":$bbc[0][$key]="病虫数(名):".$value."种";
                        break;
                    case "happen_sum":$bbc[0][$key]="发生面积总数:".$value."亩";
                        break;
                    case "hand_area_sum":$bbc[0][$key]="防治面积总数:".$value."亩";
                        break;
                    case "hand_cost_sum":$bbc[0][$key]="总防治费用:".$value."元";
                        break;
                    case "save_pest_area_sum":$bbc[0][$key]="总挽回灾害面积:".$value."亩";
                        break;
                    case "sum":$bbc[0][$key]="防治:".$value."次";
                        break;
                }
            }
            foreach ( $dataResTwo['data'] as $key => $value) {
                $dataResTwo['data'][$key]['region']= $dataResTwo['data'][$key]['r2'] .  $dataResTwo['data'][$key]['regions'];
                unset( $dataResTwo['data'][$key]['r2'], $dataResTwo['data'][$key]['regions']);
                if(!is_int( $dataResTwo['data'][$key]['pest'])){
                    $pests =  $dataResTwo['data'][$key]['pest']."病虫";
                }
            }
            $dataResTwo['data'] = array_merge_recursive($bbc,$dataResTwo['data']);
            $dataResTwo['title'] ="新宁县".$pests."防治记录统计(总共防治". $dataResTwo['data'][0]['sum']."，". $dataResTwo['data'][0]['region'].")";
            return empty($dataResTwo) ? Errors::DATA_NOT_FIND : $dataResTwo;
        } catch (Exception $e) {
            return $e->getMessage();
        }

    }

    static function pestList(){
        try{
            $query = Db::table('b_village_hand')->alias('vh');
            $query->join('b_pests p', 'p.id = vh.pest_id', 'left');
            $dataRes =  $query->field('p.cn_name label,vh.pest_id value')->group('p.cn_name,vh.pest_id')->order('vh.pest_id', 'desc')->select();
            return empty($dataRes) ? Errors::DATA_NOT_FIND : $dataRes;
        }catch (Exception $e) {
            return $e->getMessage();
        }
    }

}