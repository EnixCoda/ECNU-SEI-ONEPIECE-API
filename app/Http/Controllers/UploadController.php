<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;

class UploadController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get(Request $request)
    {
        do {
            if (!$request->has("key")
                || !$request->has("token")
            ) {
                $this->response->paraErr();
                break;
            }
            $auth = new Auth(env("QINIU_AK"), env("QINIU_SK"));
            $bucketManager = new BucketManager($auth);
            $key = $request->input("key");
            $prefix = $key;
            $marker = '';
            $limit = 1000;
            list($iterms, $marker, $err) = $bucketManager->listFiles(env("QINIU_BUCKET_NAME"), $prefix, $marker, $limit);
            if ($err !== NULL) {
                break;
            } else {
                if (count($iterms) > 0) {
                    $this->response->fileExist();
                    break;
                }
            }

            $uptoken = $auth->uploadToken(
                env("QINIU_BUCKET_NAME"),
                $key,
                1200,
                array(
                    "insertOnly" => 1,
                    "returnBody" => '{"name": $(fname), "etag": $(etag), "key": $(key)}'
                )
            );

            $this->response->setData([
                "uptoken" => $uptoken
            ]);
            $this->response->success();
        } while (false);

        return response()->json($this->response);
    }

    public function set(Request $request)
    {
        do {
            if (!$request->has("token")
                || !$request->has("fileId")
                || !$request->has("filePath")
            ) {
                $this->response->paraErr();
                break;
            }

            $token = $request->input("token");
            $fileId = $request->input("fileId");
            $filePath = $request->input("filePath");
            $stuId = $this->getIdFromToken($token);
            $detail = $this->getFileDetail($filePath);
            if ($detail === false) {
                break;
            } else {
                $result = app('db')
                    ->table('contribute')
                    ->insert([
                        "fileId" => $fileId,
                        "stuId" => $stuId,
                        "created_at" => \Carbon\Carbon::now()
                    ]);
                if ($result === false) {
                    $this->response->databaseErr();
                    break;
                }
                $size = $detail["size"];
                $this->addFile($fileId, $size, $filePath);
            }
            $this->response->success();
        } while (false);

        return response()->json($this->response);
    }

    private function addFile($fileId, $size, $key)
    {
        $result = app('db')
            ->table('file')
            ->select('fileId')
            ->where('key', '=', $key)
            ->get();

        if ($result === false) {
            $this->response->databaseErr();
            return false;
        }
        if (count($result) === 0) {
            $result = app('db')
                ->table('file')
                ->insert([
                    "fileId" => $fileId,
                    "size" => $size,
                    "key" => $key,
                    "created_at" => \Carbon\Carbon::now()
                ]);
            if ($result === false) {
                $this->response->databaseErr();
                return false;
            } else {
                $this->response->fileExist();
            }
        }
        return true;
    }

    private function getFileDetail($filepath)
    {
        $auth = new Auth(env("QINIU_AK"), env("QINIU_SK"));
        $bucketMgr = new BucketManager($auth);
        list($ret, $err) = $bucketMgr->stat(env("QINIU_BUCKET_NAME"), $filepath);
        if ($err !== null) {
            $this->response->storageErr();
            return false;
        } else {
            return $ret;
        }
    }
}
