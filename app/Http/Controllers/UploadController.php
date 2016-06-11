<?php

namespace App\Http\Controllers;

use DB;
use Qiniu\Auth;

class UploadController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get()
    {
        $auth = new Auth(env("QINIU_AK"), env("QINIU_SK"));

        $uptoken = $auth->uploadToken(
            env("QINIU_BUCKET_NAME"),
            NULL,
            1200,
            array(
                "insertOnly" => 1,
                "returnBody" => '{"name": $(fname), "etag": $(etag), "fsize": $(fsize), "key": $(key)}'
            )
        );

        return response()->json([
            "uptoken" => $uptoken
        ]);
    }

    public function set()
    {

    }
}
