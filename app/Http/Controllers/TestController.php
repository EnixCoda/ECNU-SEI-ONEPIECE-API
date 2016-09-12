<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;

class TestController extends Controller
{
    public function __construct()
    {
        //
    }

    public function get(Request $request)
    {
        $filePath = '专业必修课程/C++语言程序设计/C+语言程序设计/课件-鲍钰/C++程序设计（1）.ppt';
        $detail = $this->getFileDetail($filePath);
        if ($detail == false) {
            return response();
        } else {
//            var_dump($detail);
            echo $size = $detail["fsize"] . "\n";
            var_dump($detail);
            echo date("Y-m-d h:i:s", $detail["putTime"]);

//           if( $this->addFile("sltest",$size,$filePath)){echo  "success";};
//            $result = app('db')
//                ->table('file')
//                ->select('key')
//                ->where('fileId', '=', 'sltest')
//                ->get();
//            var_dump($result);

        }

        return response('');
    }

    public function addFile($fileId, $size, $key)
    {
        $result = app('db')
            ->table('file')
            ->insert([
                "fileId" => $fileId,
                "size" => $size,
                "key" => $key
            ]);

        if ($result === false) {
            $this->response->databaseErr();
            return false;
        }
        return true;
    }

    public function getFileDetail($filepath)
    {
        $auth = new Auth(env("QINIU_AK"), env("QINIU_SK"));
        $bucketMgr = new BucketManager($auth);
//        $prefix = '专业必修课程/C++语言程序设计/C++语言程序设计/课件-鲍钰/C++程序设计（1）.ppt';

        list($ret, $err) = $bucketMgr->stat(env("QINIU_BUCKET_NAME"), $filepath);
        var_dump($err);
        if ($err !== null) {
            return false;
        } else {
            return $ret;
        }
    }
}
