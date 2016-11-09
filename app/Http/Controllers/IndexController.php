<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Museum\Qiniu;
use App\Museum\Dir;
use App\Museum\File;

class IndexController extends Controller {
    public function __construct() {
        parent::__construct();
    }

    public function get(Request $request) {
        if ($index = $this->getIndex($request->exists('refresh'))) {
            $this->response->setData(['index' => $index]);
            $this->response->success();
        }
        return response()->json($this->response);
    }

    private function getIndex($forcePull) {
        // get rates from database
        // note: impossible to do with join
        $rates = new \stdClass();
        $result = app('db')
            ->table('score')
            ->select(app('db')->raw('key, SUM(score)'))
            ->groupBy('key')
            ->get();
        if ($result === false) {
            $this->response->databaseErr();
            return false;
        }

        foreach ($result as $row) {
            $fileId = $row->key;
            $score = $row->{'SUM(score)'};
            $rates->{$fileId} = $score;
        }

        if (!$records = $forcePull ? $this->getIndexFromQiniu() : $this->getIndexFromLocal())
            return NULL;

        $queries = [];
        $index = new Dir('ONEPIECE');
        foreach ($records as $record) {
            $id = $record['fileId'];
            $size = $record['size'];
            $key = $record['key'];
            $path = explode('/', $key);
            $filename = array_pop($path);
            $score = isset($rates->{$id}) ? $score = $rates->{$id} : 0;

            if (count($path) > 0 && $path[0] === '_log')
                continue;
            if (count($path) === 0 && $filename === 'index.html')
                continue;

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
                $cur = $cur->content[$i];
            }
            array_push($cur->content, new File($id, $filename, $size, $score));

            if ($forcePull) {
                array_push($queries, [
                    'fileId' => $id,
                    'size' => $size,
                    'key' => $key,
                    'created_at' => Carbon::now()->setTimezone('PRC')
                ]);
            }
        }
        if ($forcePull) {
            app('db')
                ->table('file')
                ->delete();
            while ($queries) {
                $tmpSQLs = array_splice($queries, 0, 100);
                app('db')
                    ->table('file')
                    ->insert($tmpSQLs);
            }
        }

        return $index;
    }

    public function getIndexFromQiniu() {
        $records = Qiniu::getList();
        if (!$records)
            $this->response->storageErr();
        $_records = [];
        foreach ($records as $record) {
            array_push($_records, [
                'fileId' => $record['hash'],
                'size' => $record['fsize'],
                'key' => $record['key']
            ]);
        }
        return $_records;
    }

    private function getIndexFromLocal() {
        $result = app('db')
            ->table('file')
            ->first();

        if ($result === false) {
            $this->response->databaseErr();
            return NULL;
        }
        if ($result === NULL    // no record in local table
            // or if last update time is 24 hours ago
            || Carbon::createFromFormat('Y-m-d H:i:s', $result->created_at)->addDay()->lt(Carbon::now())
        ) {
            return $this->getIndexFromQiniu();
        }

        // decode & encode does not take too much time, less than 10 ms for 6000 files
        $fileListArray = json_decode(json_encode(
            app('db')
                ->table('file')
                ->get()
        ), true);

        return $fileListArray;
    }
}
