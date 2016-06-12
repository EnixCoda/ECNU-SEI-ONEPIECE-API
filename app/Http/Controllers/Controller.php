<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response;

class Controller extends BaseController
{
    protected $response;
    
    function __construct()
    {
        $this->response = new MyResponse();
    }
    
    function getIdFromToken ($token)
    {
        $stuId = app('db')
            ->table('user')
            ->select('stuId')
            ->where('token', $token)
            ->first();
        if (isset($stuId->stuId)) return $stuId->stuId;
        else return NULL;
    }
}

/**
 * Custom Response
 */
class MyResponse
{

    function __construct()
    {
        $this->res_code = 1;
        $this->msg = "";
    }

    function paraErr()
    {
        $this->msg = "参数错误";
    }
    
    function invalidPath()
    {
        $this->msg = "非法路径";
    }

    function invalidUser()
    {
        $this->msg = "用户验证失败";
    }

    function databaseErr($msg = NULL)
    {
        $this->msg = $msg === NULL ? "数据库错误" : $msg;
    }

    function loginServerErr()
    {
        $this->msg = "登录服务器故障";
    }

    function loginAuthFail()
    {
        $this->msg = "用户名密码不匹配";
    }

    function storageErr()
    {
        $this->msg = "文件服务器错误";
    }

    function usernameErr()
    {
        $this->msg = "无法使用该用户名";
    }

    function success()
    {
        $this->msg = "操作成功";
        $this->res_code = 0;
    }

    function setData($data)
    {
        $this->data = $data;
    }

    function cusMsg($msg)
    {
        $this->msg = $msg;
    }

    function appendMsg($msg)
    {
        $this->msg .= $msg;
    }

    function fileNotExist()
    {
        $this->msg = "文件不存在";
    }
    
    function fileExist()
    {
        $this->msg = "文件已存在";
    }
}
