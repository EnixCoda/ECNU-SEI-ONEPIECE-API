<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Museum\MyResponse;

class Controller extends BaseController {
    protected $response;

    function __construct() {
        $this->response = new MyResponse();
    }
}
