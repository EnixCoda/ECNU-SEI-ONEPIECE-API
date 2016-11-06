<?php

namespace App\Museum;

class MyResponse {

    function __construct() {
        $this->res_code = 1;
        $this->msg = "";
        return $this;
    }

    function paraErr() {
        $this->msg = "参数错误";
    }

    function invalidPath() {
        $this->msg = "非法路径";
        return $this;
    }

    function invalidUser() {
        $this->msg = "用户验证失败";
        return $this;
    }

    function databaseErr($msg = NULL) {
        $this->msg = $msg === NULL ? "数据库错误" : $msg;
        return $this;
    }

    function loginServerErr() {
        $this->msg = "登录服务器故障";
        return $this;
    }

    function loginAuthFail() {
        $this->msg = "用户验证失败";
        return $this;
    }

    function storageErr() {
        $this->msg = "文件服务器错误";
        return $this;
    }

    function usernameErr() {
        $this->msg = "无法使用该用户名";
        return $this;
    }

    function success() {
        $this->msg = "操作成功";
        $this->res_code = 0;
        return $this;
    }

    function setData($data) {
        $this->data = $data;
        return $this;
    }

    function cusMsg($msg) {
        $this->msg = $msg;
        return $this;
    }

    function appendMsg($msg) {
        $this->msg .= $msg;
        return $this;
    }

    function fileNotExist() {
        $this->msg = "文件不存在";
        return $this;
    }

    function fileExist() {
        $this->msg = "文件已存在";
        return $this;
    }
}
