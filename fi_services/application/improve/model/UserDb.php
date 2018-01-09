<?php

namespace app\improve\model;

use app\improve\controller\Errors;
use app\improve\controller\Helper;
use Exception;
use think\Db;

/**
 * 用户数据库操作
 * Created by xwpeng.
 */
class UserDb
{
    static function addcontrast($account){
        try{
            $contrast = Db::table("u_user")->where("account",$account)->find();
            if (!empty($contrast)) return 1;
        }catch (\think\Exception $e) {
            return $e->getMessage();
        }
    }
    static function add($data)
    {
        try {
            Db::startTrans();
            $data['create_time'] = date('Y-m-d H:i:s', time());
            $data['update_time'] = $data['create_time'];
            $rids = $data['rids'];
            unset($data['rids']);
            Db::table('u_user')->insertGetId($data);
            foreach ($rids as $rid) {
                Db::name('u_user_role')->insert(["uid" => $data['uid'], "rid" => $rid]);
            }
            Db::commit();
            return 1;
        } catch (Exception  $e) {
            try {
                Db::rollback();
            } catch (Exception $e) {
                return $e->getMessage();
            }
            return $e->getMessage();
        }
    }

    static function updateStatus($uid, $status)
    {
        try {
            if ($uid === '9adf8e29ec35844515c5a43938577ac8') throw new \think\Exception('system admin cannot be update status');
            return Db::table('u_user')->where(["uid" => $uid])->update(['status' => $status]);
        } catch (\think\Exception $e) {
            return $e->getMessage();
        }
    }

    static function edit($data)
    {
        try {
            Db::startTrans();
            if ($data['uid'] !== '9adf8e29ec35844515c5a43938577ac8') {
                //delete
                Db::table('u_user_role')->where("uid", $data['uid'])->delete();
                //insert
                if (isset($data['rids'])) {
                    foreach ($data['rids'] as $rid) {
                        Db::name('u_user_role')->insert(["uid" => $data['uid'], "rid" => $rid]);
                    }
                }
            }
            if ($data['uid'] === '9adf8e29ec35844515c5a43938577ac8') $data['status'] = 1;
            //update
            unset($data['rids']);
            $data['update_time'] = date('Y-m-d H:i:s', time());
            $dbRes = Db::table('u_user')->update($data);
            if ($dbRes > 0) {
                Db::commit();
                return 1;
            }
            throw new \think\Exception("update fail,uid not find?");
        } catch (\think\Exception $e) {
            try {
                Db::rollback();
            } catch (\think\Exception $e) {
                return $e->getMessage();
            }
            return $e->getMessage();
        }
    }

    static function query($uid)
    {
        try {
            $user = Db::table("u_user")->alias('nb')
                ->where("nb.uid", $uid)
                ->join('c_region q', 'q.id = nb.region')
                ->join('c_region q2', 'q2.id = q.parentId')
                ->column('nb.uid,nb.account,nb.region,nb.name,nb.dept,nb.status,nb.tel,q.name r1,q2.name r2');
            if (empty($user)) throw new \think\Exception("user not find ");
            else $user = array_values($user)[0];
            $roles = Db::table("u_user_role")->alias('ur')
                ->where('ur.uid', $uid)
                ->join('u_role r', 'r.rid = ur.rid')
                ->field('r.rid,r.name')
                ->select();
            $user["roles"] = $roles;
            return $user;
        } catch (\think\Exception $e) {
            return $e->getMessage();
        }
    }



    static function ls($data, $sample = false)
    {
        try {
            $query = DB::table("u_user")->alias('u');
            if (Helper::lsWhere($data, 'name')) $query = $query->whereLike("u.name", '%'.$data['name'].'%');
//            if (Helper::lsWhere($data, 'dept')) $query = $query->where("u.dept", $data['dept']);
            $query->join('c_region r', ' r.id = u.region', 'left');
            $query->join('c_region r2', "r.parentId = r2.id", 'left');
//            $query->join('c_region r3', "r2.parentId = r3.id", 'left');
            if ($sample) $query->field('u.uid,u.name');
            else $query->field('u.uid,u.account,u.region,u.status,u.dept,u.name,r.name r1,r2.name r2,u.tel');
            $query->order('u.update_time', 'desc');
            return $query->paginate($data['per_page'], false, ['page' => $data['current_page']])->toArray();
        } catch (\think\Exception $e) {
            return $e->getMessage();
        }
    }

    static function queryVerify($account)
    {
        try {
            return Db::table("u_verify")
                ->where("account", $account)
                ->find();
        } catch (\think\Exception $e) {
            return $e->getMessage();
        }
    }

    static function login($account)
    {
        try {
            $user = Db::table("u_user")
                ->where("account", $account)
                ->column('uid,account,pwd,salt,region,name,status');
            if (empty($user)) throw new \think\Exception(Errors::LOGIN_ERROR);
            else $user = array_values($user)[0];
            $roles = Db::table("u_user_role")->alias('ur')
                ->where('ur.uid', $user['uid'])
                ->join('u_role r', 'r.rid = ur.rid')
                ->field('r.rid,r.name')
                ->select();
            $user["roles"] = $roles;
//            $pids = Db::table("u_user_role")->alias('ur')
//                ->where("ur.uid", $user['uid'])
//                ->join('u_role_premission rp', 'rp.rid = ur.rid')
//                ->field('rp.pid')
//                ->select();
//            $user['pids'] = $pids;
            return $user;
        } catch (\think\Exception $e) {
            return $e->getMessage();
        }
    }

    static function resetAuth($data)
    {
        try {
            $auth = Db::table('u_auth')->where('uid', $data['uid'])
                ->where('client', $data['client'])->column('uid');
            if (empty($auth)) {
                return Db::table('u_auth')->insert($data);
            }
            return Db::table('u_auth')->update($data);
        } catch (Exception  $e) {
            return $e->getMessage();
        }
    }

    static function deleteAuth($uid, $client = null)
    {
        try {
            $query = Db::table('u_auth')->where('uid', $uid);
            if (!empty($client)) $query = $query->where('client', $client);
            return $query->delete();
        } catch (\think\Exception $e) {
            return $e->getMessage();
        }
    }

    static function queryAuth($uid, $s_token)
    {
        try {
            return Db::table('u_auth')->where('uid', $uid)
                ->where('s_token', $s_token)->column('s_update_time');
        } catch (Exception  $e) {
            return $e->getMessage();
        }
    }

    static function queryPids($uid)
    {
        try {
            $user = Db::table("u_user_role")->alias('ur')
                ->where("ur.uid", $uid)
                ->join('u_role_premission rp', 'rp.rid = ur.rid')
                ->field('rp.pid')
                ->select();
            return $user;
        } catch (\think\Exception $e) {
            return $e->getMessage();
        }
    }

    static function queryDepts()
    {
        try {
            return Db::table("u_dept")->select();
        } catch (\think\Exception $e) {
            return $e->getMessage();
        }
    }

}