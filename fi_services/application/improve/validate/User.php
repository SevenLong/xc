<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/27
 * Time: 15:24
 */

namespace app\improve\validate;

class User extends BaseValidate
{
    protected $rule = [
        'account' => 'require|length:3,16|alphaDash',
        'pwd' => 'require|length:6,16|different:account|alphaDash',
        'region' => 'require|max:20|region',
        'name' => 'require|max:16',
        'status'=>"in:-1,0",
        'rids'=>"require|array",
        'uid'=>"require|length:32",
        'client'=>'require|in:1,2',
        'tel'=>'require|tel'
    ];


    protected function tel($value)
    {
        return preg_match_all('/^1[34578]\d{9}$/', $value) ? true : 'tel格式不对';
    }

    protected $scene = [
        'add'  =>  ['account','pwd','region','name','rids','tel'],
        'status'  =>  ['uid','status'],
        'edit'  =>  ['account','pwd','status','region','name','rids','uid','tel'],
        'query'  =>  ['uid'],
        'login'=>['account','pwd','client'],
        'loginOut'=>['client'],
    ];

}