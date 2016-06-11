<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TestController extends Controller
{
    public function __construct()
    {
        //
    }

    public function get(Request $request) {
        return "get";
    }
    
    public function set(Request $request) {
        return "post";
    }
}
