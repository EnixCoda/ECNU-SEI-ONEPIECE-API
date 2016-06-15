<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \Qiniu\Auth;
use \Qiniu\Storage\BucketManager;

class Dir
{
    function __construct($name)
    {
        $this->name = $name;
        $this->isDir = true;
        $this->content = array();
    }
}

class File
{
    function __construct($id, $name, $size, $score)
    {
        $this->id = $id;
        $this->name = $name;
        $this->size = $size;
        $this->isDir = false;
        $this->score = $score;
    }
}

class IndexController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get(Request $request)
    {
        if ($request->has("refresh")) {
            if ($index = $this->genIndexFromQiniu()) {
                $this->response->setData(["index" => $index]);
                $this->response->success();
            } else {
                $this->response->storageErr();
            }
        } else {
            if ($index = $this->genIndexFromLocal()) {
                $this->response->setData(["index" => $index]);
                $this->response->success();
            } else {
                $this->response->storageErr();
            }
        }
        return response()->json($this->response);
    }

    public function genIndexFromQiniu()
    {
        // get rates from database
        $rates = new \stdClass();

        $result = app('db')->table('score')
            ->select(app('db')->raw("key, SUM(score)"))
            ->groupBy("key")
            ->get();
        foreach ($result as $row) {
            $fileId = $row->key;
            $score = $row->{'SUM(score)'};
            $rates->{$fileId} = $score;
        }

        $sqls = [];

        // get index from Qiniu
        $index = new Dir("ONEPIECE");

        $auth = new Auth(env("QINIU_AK"), env("QINIU_SK"));
        $bucketMgr = new BucketManager($auth);
        $prefix = '';
        $marker = '';
        $limit = 1000;

        do {
            list($iterms, $marker, $err) = $bucketMgr->listFiles(env("QINIU_BUCKET_NAME"), $prefix, $marker, $limit);
            if ($err !== NULL) {
                break;
            } else {
                foreach ($iterms as $iterm) {
                    $key = $iterm["key"];
                    $path = explode("/", $key);
                    $filename = array_pop($path);

                    if (strpos($key, "__ARCHIVE__") !== false) continue;
                    if (count($path) == 0 && $filename == "index.html") continue;
                    if (strpos($filename, "申请-") === 0) continue;
                    if (count($path) > 0 && $path[0] === "_log") continue;

                    $size = $iterm["fsize"];
                    $id = $iterm["hash"];
                    $score = 0;
                    if (isset($rates->{$id})) {
                        $score = $rates->{$id};
                    }

                    // put the file into its position
                    // create some folders on the way
                    $cur = $index;
                    foreach ($path as $dirName) {
                        $dirExist = false;
                        for ($i = 0; $i < count($cur->content); $i++) {
                            if ($cur->content[$i]->name == $dirName) {
                                $dirExist = true;
                                break;
                            }
                        }
                        if (!$dirExist) {
                            array_push($cur->content, new Dir($dirName));
                        }
                        $cur = $cur->content[count($cur->content) - 1];
                    }
                    array_push($cur->content, new File($id, $filename, $size, $score));

                    array_push($sqls, ["fileId" => $id, "size" => $size, "key" => $key, "created_at" => \Carbon\Carbon::now()]);
                }
            }
        } while ($marker !== NULL);

        if ($err) {
            return NULL;
        } else {
            app('db')
                ->table('file')
                ->delete();
            while ($sqls) {
                $tmpSQLs = array_splice($sqls, 0, 100);
                app('db')
                    ->table('file')
                    ->insert($tmpSQLs);
            }
            return $index;
        }
    }

    private function genIndexFromLocal()
    {

        $result = app('db')
            ->table('file')
            ->first();

        if ($result === false) return NULL;
        if ($result === NULL) {
            return $this->genIndexFromQiniu();
        }

        // get rates from database
        $rates = new \stdClass();

        $result = app('db')->table('score')
            ->select(app('db')->raw("key, SUM(score)"))
            ->groupBy("key")
            ->get();
        foreach ($result as $row) {
            $fileId = $row->key;
            $score = $row->{'SUM(score)'};
            $rates->{$fileId} = $score;
        }

        // get index from Qiniu
        $index = new Dir("ONEPIECE");

        $result = app('db')
            ->table('file')
            ->get();

        foreach ($result as $file) {
            $key = $file->key;
            $path = explode("/", $key);
            $filename = array_pop($path);

            if (strpos($key, "__ARCHIVE__") !== false) continue;
            if (count($path) == 0 && $filename == "index.html") continue;
            if (strpos($filename, "申请-") === 0) continue;
            if (count($path) > 0 && $path[0] === "_log") continue;

            $size = $file->size;
            $id = $file->fileId;
            $score = 0;
            if (isset($rates->{$id})) {
                $score = $rates->{$id};
            }

            // put the file into its position
            // create some folders on the way
            $cur = $index;
            foreach ($path as $dirName) {
                $dirExist = false;
                for ($i = 0; $i < count($cur->content); $i++) {
                    if ($cur->content[$i]->name == $dirName) {
                        $dirExist = true;
                        break;
                    }
                }
                if (!$dirExist) {
                    array_push($cur->content, new Dir($dirName));
                }
                $cur = $cur->content[count($cur->content) - 1];
            }
            array_push($cur->content, new File($id, $filename, $size, $score));
        }

        return $index;
    }
}
