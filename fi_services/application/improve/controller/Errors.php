<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/27
 * Time: 10:52
 */
namespace app\improve\controller;
class Errors
{
    const PARAMS_ERROR = "params_error";
    const DB_ERROR = "db_error";
    const LOGIN_ERROR = "account or pwd error";
    const FILE_ROOT_PATH = ROOT_PATH . DS . 'public' . DS . 'file';
    const DATA_NOT_FIND = "data not find";
    const LOGIN_STATUS = "user status error";
    const AUT_LOGIN = "auth delete failed";
    const USER_ADD = "account already exists";
    const MAX_FILE_SIZE = "max fileSize 100M";
    const SAVE_FILE_ERROR = "save file error";
    const IMAGE_COUNT_ERROR = "image count max 6";
    const FILE_TYPE_ERROR = "file type error";
    const IMAGE_FILE_SIZE_ERROR = "max fileSize 2M";
    const UPDATE_ERROR = "update error";
    const DELETE_ERROR = "delete error";
    const DEADLINE_ERROR = "deadline must > create_time";
    const ADD_ERROR = "add error";
    const IS_NOT_I = "u are not task founder";
    const NO_INCIDENT = "u are not task receiver";
    const INSERT_ERROR = "insert error";
    const ASSIGN_ERROR = "u are not be assign";
    const TASK_STATUS_ERROR_ONE = "task status is not 0";
    const TASK_STATUS_ERROR_TWO = "task status is not 1";
    const TASK_STATUS_ERROR_THREE = "task status is not 2";
    const TASK_EXPIRED = "task expired";
    const VERSION_CODE_IS_NULL = "version_code is null";
    const LIMITED_AUTHORITY = "u are not a manager or not an adder";
    const IMAGE_NOT_FIND = "image not find";
    const ATTACH_NOT_FIND = "attach not find";
    const NEW_VERSION_NOT_FIND = "new version not find";
    const IMAGES_INSERT_ERROR = "image insert error";
}
?>