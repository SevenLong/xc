<?php
/**
 * Created by PhpStorm.
 * User: LiuTao
 * Date: 2017/12/7/007
 * Time: 10:53
 */

namespace app\improve\model;

use app\improve\controller\Errors;
use app\improve\controller\Helper;
use app\improve\controller\UploadHelper;
use think\Db;
use think\Exception;


class CountryRecordDb
{
    //增加记录
    static function add($data, $images)
    {
        try {
            Db::startTrans();
            $result = Db::table('b_survey_record')->insertGetId($data);
            if ($result < 1) throw new Exception(Errors::INSERT_ERROR);
            if (!empty($images)) {
                if (count($images) > 6) throw new Exception("图片数量不能超过6张");
                foreach ($images as $image) {
                    $path = UploadHelper::uplodImage($image, DS . 'country_record' . DS . 'image_' . $result);
                    if (!is_array($path)) throw new Exception($path);
                    $a = Db::table('b_survey_record_image')->insert(['country_record_id' => $result, 'path' => $path[0]]);
                    if ($a < 1) throw new Exception(Errors::IMAGES_INSERT_ERROR);
                }
            }
            Db::commit();
            return $result;
        } catch (Exception $e) {
            Db::rollback();
            return $e->getMessage();
        }
    }

    //条件查询
    static function ls($data, $sample = false)
    {
        try {
            $query = Db::table('b_survey_record')->alias('sr');
            if (Helper::lsWhere($data, 'region')) $query->whereLike('sr.region', $data['region'] . '%');
            if (Helper::lsWhere($data, 'pest_id')) $query->where('sr.pest_id', $data['pest_id']);
            if (Helper::lsWhere($data, 'plant_id')) $query->where('sr.plant_id', $data['plant_id']);
            if (Helper::lsWhere($data, 'hazard_level')) $query->where('sr.hazard_level', $data['hazard_level']);
            if (Helper::lsWhere($data, 'survey_time_min')) $query->where('sr.create_time', '>=', $data['survey_time_min']);
            if (Helper::lsWhere($data, 'survey_time_max')) $query->where('sr.create_time', '<=', $data['survey_time_max']);
            if (Helper::lsWhere($data, 'happen_level')) $query->where('sr.happen_level', $data['happen_level']);
            if (Helper::lsWhere($data, 'position_type')) $query->where('sr.position_type', $data['position_type']);
            if (Helper::lsWhere($data, 'adder_name')) {
                $query->view('u_user u3', 'name adder_name', "uid = sr.adder");
                $query->whereLike('u3.name', '%' . $data['adder_name'] . '%');
            }
            $query->join('b_pests p', 'p.id = sr.pest_id', 'left');
            if ($sample) {
                $query->field('sr.id, p.cn_name pest_name,sr.hazard_type,sr.create_time, sr.positions');
            } else {
                $query->join('b_plant plant', 'plant.id = sr.plant_id', 'left');
                $query->join('c_region r', 'r.id = sr.region', 'left');
                $query->join('c_region r2', 'r.parentId = r2.id', 'left');
                $query->join('u_user u', 'u.uid = sr.adder', 'left');
                $query->field('sr.id, sr.region, r.name r1, r2.name r2, sr.pest_id, p.cn_name pest_name, sr.generation,
            sr.plant_id, plant.cn_name plant_name, sr.happen_level, sr.distribution_area, sr.damaged_area, sr.create_time, u.name adder_name');
            }
            $query->order('sr.update_time', 'desc');
            $dataRes = $query->paginate($data['per_page'], false, ['page' => $data['current_page']])->toArray();
            return empty($dataRes) ? Errors::DATA_NOT_FIND : $dataRes;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    static function upexcel($data)
    {
        try {
            $query = Db::table('b_survey_record')->alias('sr');
            $query->join('b_pests pest', 'pest.id = sr.pest_id', 'left');
            $query->join('b_plant plant', 'plant.id = sr.plant_id', 'left');
            $query->join('c_region r', 'r.id = sr.region', 'left');
            $query->join('c_region r2', 'r.parentId = r2.id', 'left');
            $query->join('u_user u', 'u.uid = sr.adder', 'left');
            $query->field(' r.name r1, r2.name r2, pest.cn_name pest_name, sr.generation, plant.cn_name plant_name, sr.happen_level, sr.distribution_area, 
            sr.damaged_area, sr.create_time, u.name adder_name');
            $query->order('sr.update_time', 'desc');
            $dataRes = $query->paginate($data['per_page'], false, ['page' => $data['current_page']])->toArray();
            $dataRes = $dataRes['data'];
            foreach ($dataRes as $key => $value) {
                $a['region'] = $dataRes[$key]['r2'] . $dataRes[$key]['r1'];
                $dataRes[$key] = $a + $dataRes[$key];
                unset($dataRes[$key]['r2'], $dataRes[$key]['r1']);
                foreach ($value as $mk => $mg) {
                    $h =[2=>"轻", 3=>"中", 4=>"重",];
                    $g =["第一代", "第二代", "第三代", "越冬代", "第四代", "第五代", "第六代", "第七代",];
                    if ($mk == 'happen_level') $dataRes[$key][$mk] = $h[$mg];
                    if ($mk == 'generation') $dataRes[$key][$mk] = $g[$mg];
                }
            }
            return empty($dataRes) ? Errors::DATA_NOT_FIND : $dataRes;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //查询一条数据
    static function query($data)
    {
        try {
            $dbRes = Db::table('b_survey_record')->alias('sr')->where('sr.id', $data['id'])
                ->join('b_pests p', 'p.id = sr.pest_id', 'left')
                ->join('b_plant plant', 'plant.id = sr.plant_id', 'left')
                ->join('c_region r', 'r.id = sr.region', 'left')
                ->join('c_region r2', 'r.parentId = r2.id', 'left')
                ->join('c_region r3', 'r2.parentId = r3.id', 'left')
                ->join('u_user u', 'u.uid = sr.adder', 'left')
                ->field('sr.*,r.name r1,r2.name r2,r3.name r3,p.cn_name pest_name,plant.cn_name plant_name, u.name adder_name')
                ->find();
            if (empty($dbRes)) return Errors::DATA_NOT_FIND;
            $dbRes['images'] = Db::table('b_survey_record_image')->where('country_record_id', $data['id'])->select();
            return $dbRes;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    // 删除选中
    static function deleteChecked($ids, $suid)
    {
        try {
            $ret = [];
            foreach ($ids as $id) {
                $adder = Helper::queryAdder($id, "b_survey_record");
                if (!is_array($adder)) {
                    array_push($ret, ['id' => $id, 'res' => Errors::DATA_NOT_FIND]);
                    continue;
                }
                if (true !== Helper::checkAdderOrManage($adder, $suid)) {
                    array_push($ret, ['id' => $id, 'res' => 'u are not a manager or not an adder']);
                    continue;
                }
                $res = Db::table('b_survey_record')->where('id', $id)->delete();
                array_push($ret, ['id' => $id, 'res' => $res === 1 ? 'delete success' : Errors::DELETE_ERROR]);
            }
            return $ret;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    // 编辑
    static function edit($data, $images)
    {
        try {
            $paths = [];
            Db::startTrans();
            if (Helper::lsWhere($data,'del_images')){
                $del_images = $data['del_images'];
                $paths = Db::table('b_survey_record_image')->field('path')->where('country_record_id', $data['id'])->whereIn('id',$del_images)->select();
                if (count($paths) !== count($del_images)) throw new Exception('删除的图片未找到');
                $delRes = Db::table('b_survey_record_image')->whereIn('id',$del_images)->delete();
                if ($delRes !== count($del_images)) throw new Exception('删除失败');
            }
            unset($data['del_images']);
            $data['update_time'] = date('Y-m-d H:i:s');
            $dbRes = Db::table('b_survey_record')->field("region,pest_id,hazard_type,plant_id,generation,happen_tense,hazard_level,
            plant_cover_degree,pests_density,dead_tree_num,is_main_pests,happen_level,distribution_area,damaged_area,positions,
            position_type,update_time")->update($data);
            //图片上传
            if (!empty($images)) {
                //数量判断
                $haveCount = Db::table('b_survey_record_image')->where('country_record_id',$data['id'])->count('*');
                if ($haveCount + count($images) > 6) throw new Exception('图片不能超过6张');
                foreach ($images as $image) {
                    $path = UploadHelper::uplodImage($image, DS . 'country_record' . DS . 'image_' .$data['id']);
                    if (!is_array($path)) throw new Exception($path);
                    $a = Db::table('b_survey_record_image')->insert(['country_record_id' => $data['id'], 'path' => $path[0]]);
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

    static function villagesChart($data)
    {
        try {
            $res = [];
            foreach ($data['regions'] as $item) {
                $sql = "select DATE_FORMAT(sr.create_time,'%Y') year,sum(sr."
                    . ($data['content'] === '1' ? "distribution_area" : "damaged_area")
                    . ") sum from b_survey_record sr where sr.region LIKE '"
                    . $item
                    . "%'"
                    . "And sr.pest_id = "
                    . $data['pest_id']
                    . " GROUP BY year  HAVING  year >= "
                    . $data['year_min'] . " AND year <= "
                    . $data['year_max'] . " ORDER BY year desc";
                $res[$item] = DB::query($sql);
            }
            return empty($res) ? Errors::DATA_NOT_FIND : $res;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    static function villagesListSon($data = false){
            $a = 'sum(sr.dead_tree_num) dead_sum,sum(sr.distribution_area) distribution_sum,sum(sr.damaged_area) distrib_sum,count(sr.id) sum';
            $b = 'count(distinct sr.region) region,';
            $c = 'rga.name regions,rgb.name r2,sr.region,';
            $f = 'count(distinct p.id) pest,';
            $g = 'p.cn_name pest,';
            $query = Db::table('b_survey_record')->alias('sr');
            if($data) {
                if (Helper::lsWhere($data, 'pest_id')) {
                    $f = $g;
                    $query->where('sr.pest_id', $data['pest_id']);
                }
                $d = $c.$f.$a;
            }else{
                $d = $b.$f.$a;
            }
            $query  ->join('b_pests p', 'p.id = sr.pest_id', 'left')
                    ->join('c_region rga', 'rga.id = sr.region', 'left')
                    ->join('c_region rgb', 'rga.parentId = rgb.id', 'left')
                    ->field($d);
            if($data){
                $dataRes = $query   ->group('sr.region')
                                    ->order('sr.region', 'desc')
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
            $dataResOne = CountryRecordDb::villagesListSon();
            $dataResTwo = CountryRecordDb::villagesListSon($data);
            $bbc=[];
            foreach ($dataResOne as $key =>$value){
                switch ($key)
                {
                    case "region":$bbc[0][$key]="受灾地区数:".$value."个";
                        break;
                    case "pest":$bbc[0][$key]="病虫数:".$value."种";
                        break;
                    case "dead_sum":$bbc[0][$key]="总死株数".$value."株";
                        break;
                    case "distribution_sum":$bbc[0][$key]="总分布面积:".$value."亩";
                        break;
                    case "distrib_sum":$bbc[0][$key]="总分受灾积:".$value."亩";
                    break;
                    case "sum":$bbc[0][$key]="总调查:".$value."次";
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
            $dataResTwo['title'] ="新宁县".$pests."调查记录统计(总共调查". $dataResTwo['data'][0]['sum']."，". $dataResTwo['data'][0]['region'].")";
            return empty($dataResTwo) ? Errors::DATA_NOT_FIND : $dataResTwo;
        } catch (Exception $e) {
            return $e->getMessage();
        }

    }

    static function pestList(){
        try{
            $query = Db::table('b_survey_record')->alias('sr');
            $query->join('b_pests p', 'p.id = sr.pest_id', 'left');
            $dataRes =  $query->field('p.cn_name label,sr.pest_id value')->group('p.cn_name,sr.pest_id')->order('sr.pest_id', 'desc')->select();
            return empty($dataRes) ? Errors::DATA_NOT_FIND : $dataRes;
        }catch (Exception $e) {
            return $e->getMessage();
        }
    }

    // 批量插入记录
    static function bulkInsert()
    {
        $count = 0;
        $error = 0;
        $regions = [
            430528104204,
            430528105204,
            430528106202,
        ];
        $pestids = [15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 34, 35, 36, 37, 38, 39, 40];
        $data = [
            'hazard_type' => 1,
            'generation' => 1,
            'happen_tense' => 3,
            'hazard_level' => 1,
            'plant_cover_degree' => '60%',
            'pests_density' => 15,
            'dead_tree_num' => 15,
            'is_main_pests' => '-1',
            'happen_level' => 2,
            'positions' => '(113.017752,28.193198);',
            'position_type' => 1,
            'adder' => 'd010ba35e1aeac68abe4cc563ae5f896',
            'create_time' => date('Y-m-d H:i:s'),
        ];
        $data['update_time'] = $data['create_time'];
        foreach ($regions as $region) {
            foreach ($pestids as $pestid) {
                for ($j = 0; $j < 11; $j++) {
                    $data['region'] = $region;
                    $data['pest_id'] = $pestid;
                    $data['plant_id'] = rand(1, 20);
                    $data['distribution_area'] = rand(1, 999);
                    $data['damaged_area'] = rand(1, $data['distribution_area']);;
                    unset($data['id']);
                    $result = Db::table('b_survey_record')->insertGetId($data);
                    $result > 0 ? $count++ : $error++;
                }
            }
        }
        return $dbRes = $count . '条记录添加成功,' . $error . '条记录添加失败';
    }
}