<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/22 0022
 * Time: 11:53
 */
namespace app\improve\validate;
use think\Validate;
class LawFile extends Validate{
     protected $rule= [
         'sort' => 'require|in:1,2',
         'title' => 'require|max:10',
         'content' => 'require|max:255',
         'create_time_min' => 'require|dateFormat:Y-m-d H:i:s',
         'create_time_max' => 'require|dateFormat:Y-m-d H:i:s',
         'adder' => 'require|max:32',
         'per_page' => 'require|number',
         'current_page' => 'require|number',
         'id' => 'require|number',
         'ids' => 'require|array',

     ];
     protected $scene = [
         'add' => ['sort','title','content'],
         'ls' => ['create_time_max','create_time_min','sort','adder','per_page','current_page'],
         'edit' => ['id','sort','title','content','adder'],
         'id' => ['id'],
         'ids' => ['ids'],

     ];
}