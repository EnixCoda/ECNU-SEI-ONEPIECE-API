<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Museum\MyResponse;

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

