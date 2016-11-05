<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Museum\Qiniu;

class UploadController extends Controller {
    public function __construct() {
        parent::__construct();
    }

    public function get(Request $request) {
        do {
            $validate = app('validator')
                ->make($request->all(), [
                    'key' => 'required'
                    ]);
            if ($validate->fails()) {
                $this->response->paraErr();
                break;
            }
            $key = $request->input('key');

            $iterms = Qiniu::getList($key);
            if (!$iterms) {
                $this->response->storageErr();
                break;
            } else {
                if (count($iterms) > 0) {
                    $this->response->fileExist();
                    break;
                }
            }

            $uptoken = Qiniu::getUploadToken($key);
            $this->response->setData([
                'uptoken' => $uptoken
            ]);
            $this->response->success();
        } while (false);

        return response()->json($this->response);
    }

    public function set(Request $request) {
        do {
            $validate = app('validator')
                ->make($request->all(), [
                    'fileId' => 'required',
                    'filePath' => 'required'
                    ]);
            if ($validate->fails()) {
                $this->response->paraErr();
                break;
            }

            $fileId = $request->input('fileId');
            $filePath = $request->input('filePath');

            $stuId = $request->user()->{'stuId'};

            $detail = Qiniu::getList($filePath);
            if ($detail === false) {
                $this->response->storageErr();
                break;
            }
            $detail = $detail[0];
            if ($detail['hash'] !== $fileId) {
                $this->response->cusMsg('文件路径与ID不匹配');
                break;
            }

            if ($this->addFileToTableFile($detail, $filePath) === false) {
                break;
            }

            if ($this->addFileToTableContribute($detail, $stuId) === false) {
                break;
            }

            $this->response->success();
        } while (false);

        return response()->json($this->response);
    }

    private function addFileToTableContribute($detail, $stuId) {
        $result = app('db')
            ->table('contribute')
            ->insert([
                'fileId' => $detail['hash'],
                'stuId' => $stuId,
                'created_at' => Carbon::now()
            ]);
        if ($result === false) {
            $this->response->databaseErr();
            return false;
        }
        return true;
    }

    private function addFileToTableFile($detail, $key) {
        $result = app('db')
            ->table('file')
            ->where('key', $key)
            ->get();
        if ($result === false) {
            $this->response->databaseErr();
            return false;
        }

        if (count($result) > 0) {
            $this->response->fileExist();
            return false;
        }

        $result = app('db')
            ->table('file')
            ->insert([
                'fileId' => $detail['hash'],
                'size' => $detail['fsize'],
                'key' => $key,
                'created_at' => Carbon::now()
            ]);
        if ($result === false) {
            $this->response->databaseErr();
            return false;
        }

        return true;
    }
}
