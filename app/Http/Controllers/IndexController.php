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
        $reset = $forcePull || self::needReset();

        $records;
        if ($reset) $records = $this->getIndexFromQiniu();
        if (!$reset || !$records) $records = $this->getIndexFromLocal();

        if ($reset) {
            app('db')
                ->table('file')
                ->delete();
            $queries = array_map(function($record) {
                $record['created_at'] = Carbon::now()->setTimezone('PRC');
                return $record;
            }, $records);
            while ($queries) {
                $tmpSQLs = array_splice($queries, 0, 100);
                app('db')
                    ->table('file')
                    ->insert($tmpSQLs);
            }
        }

        $rates = self::getRates();
        $uploads = self::getUploads();
        $downloads = self::getDownloads();
        $index = new Dir('ONEPIECE');
        foreach ($records as $record) {
            $id = $record['fileId'];
            $key = $record['key'];
            $size = $record['size'];
            $path = explode('/', $key);
            $filename = array_pop($path);
            $score = isset($rates[$id]) ? $rates[$id] : NULL;
            $uploader = isset($uploads[$id]) ? $uploads[$id] : NULL;
            $download = isset($downloads[$id]) ? $downloads[$id] : NULL;

            // pass /_log/*
            if (count($path) > 0 && $path[0] === '_log')
                continue;
            // pass /index.html
            if (count($path) === 0 && $filename === 'index.html')
                continue;

            // put the file into its position
            // create some folders on the way
            $cur = $index;
            foreach ($path as $dirName) {
                $dirExist = false;
                for ($i = 0; $i < count($cur->content); $i++) {
                    if ($cur->content[$i]->name === $dirName) {
                        $dirExist = true;
                        break;
                    }
                }
                if (!$dirExist)
                    array_push($cur->content, new Dir($dirName));
                $cur = $cur->content[$i];
            }
            array_push($cur->content, new File($id, $filename, $size, $score, $uploader, $download));
        }

        return $index;
    }

    private function needReset() {
        $result = app('db')
            ->table('file')
            ->first();

        if ($result === false) {
            $this->response->databaseErr();
            return false;
        }

        // no record in local table
        if ($result === NULL) {
            return true;
        } else {
            // or if last update time is 24 hours ago
            if (Carbon::createFromFormat('Y-m-d H:i:s', $result->created_at)->addDay()->lt(Carbon::now())) {
                return true;
            }
        }

        return false;
    }

    private function getIndexFromQiniu() {
        $records = Qiniu::getList();
        if ($records === NULL)
            // $this->response->storageErr();
            return NULL;
        else {
            $fileListArray = array_map(function ($record) {
                return [
                    'fileId' => $record['hash'],
                    'size' => $record['fsize'],
                    'key' => $record['key']
                ];
            }, $records);
        }
        return $fileListArray;
    }

    private function getIndexFromLocal() {
        // decode & encode does not take too much time, less than 10 ms for 6000 files
        $fileListArray = json_decode(json_encode(
            app('db')
                ->table('file')
                ->get()
        ), true);

        return $fileListArray;
    }

    private function getRates() {
        // get rates from database
        // note: impossible to do with join
        $rates = [];
        $result = app('db')
            ->table('score')
            ->select(app('db')->raw('key, SUM(score) as totalScore'))
            ->groupBy('key')
            ->get();
        if ($result === false) {
            $this->response->databaseErr();
            return false;
        }

        foreach ($result as $row) {
            $fileId = $row->key;
            $score = $row->totalScore;
            $rates[$fileId] = $score;
        }

        return $rates;
    }

    private function getUploads() {
        // get upload info from database
        $result = app('db')
            ->table('contribute')
            ->join('user', 'contribute.stuId', '=', 'user.stuId')
            ->get();
        if ($result === false) {
            $this->response->databaseErr();
            return [];
        }

        $uploads = [];
        foreach ($result as $row) {
            $fileId = $row->fileId;
            $uploader = $row->lastAlia;
            $uploads[$fileId] = $uploader;
        }

        return $uploads;
    }

    private function getDownloads() {
        // get download info from database
        $result = app('db')
            ->table('log')
            ->select(app('db')->raw('action, count(*) as count'))
            ->where('action', 'like', '%file%download%')
            ->groupBy('action')
            ->get();
        if ($result === false) {
            $this->response->databaseErr();
            return [];
        }

        $downloads = [];
        foreach ($result as $row) {
            $fileId = str_replace('{"method":"GET","path":"file\\/', '', str_replace('\\/download","input":[]}', '', $row->action));
            $count = (int)$row->count;
            $downloads[$fileId] = $count;
        }

        return $downloads;
    }
}
