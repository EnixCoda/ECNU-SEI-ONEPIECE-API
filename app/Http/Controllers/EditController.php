<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Museum\Qiniu;

class EditController extends Controller {
    public function __construct() {
        parent::__construct();
    }

    public function get(Request $request) {
        do {
            $validate = app('validator')
                ->make($request->all(), [
                    'path' => 'required'
                ]);
            if ($validate->fails()) {
                $this->response->paraErr();
                break;
            }
            $path = $request->input("path");

            // check saved operations
            $result = app('db')
                ->table('edit')
                ->where('original', $path)
                ->get();
            if ($result === false) {
                $this->response->databaseErr();
                break;
            }

            $edits = array(
                'MOVE' => [],
                'TRASH' => 0,
                'RENAME' => []
            );
            foreach ($result as $row) {
                $type = $row->type;
                $edit = $row->edit;
                // get modification string
                if ($type == 'MOVE') {
                    $edits['MOVE'][$edit] = isset($edits['MOVE'][$edit]) ? $edits['MOVE'][$edit] + 1 : 1;
                } else if ($type == 'TRASH') {
                    $edits['TRASH']++;
                } else if ($type == 'RENAME') {
                    // cannot use array_pop() or negative index
                    $newName = explode('/', $edit)[count(explode('/', $edit)) - 1];
                    $edits['RENAME'][$newName] = isset($edits['TRASH'][$newName]) ? $edits['TRASH'][$newName] + 1 : 1;
                }
            }

            // turn $edits['MOVE'] into array: [edit, count]
            $editsMoveArr = [];
            foreach ($edits['MOVE'] as $edit => $count) {
                array_push($editsMoveArr, [$edit, $count]);
            }
            $edits['MOVE'] = $editsMoveArr;

            // so as $edits['RENAME']
            $editsRenameArr = [];
            foreach ($edits['RENAME'] as $edit => $count) {
                array_push($editsRenameArr, [$edit, $count]);
            }
            $edits['RENAME'] = $editsRenameArr;
            $edits['LIMIT'] = env('EDIT_LIMIT');

            $this->response->setData(["edits" => $edits]);
            $this->response->success();
        } while (false);

        return response()->json($this->response);
    }

    public function set(Request $request) {
        do {
            $validate = app('validator')
                ->make($request->all(), [
                    'original' => 'required',
                    'edit' => 'required',
                    'type' => 'required'
                ]);
            if ($validate->fails()) {
                $this->response->paraErr();
                $this->response->appendMsg('validator');
                break;
            }
            $original = $request->input('original');
            $edit = $request->input('edit');
            $type = $request->input('type');
            $stuId = $request->user()->{'stuId'};

            if ($type === 'TRASH' && $edit !== '-') {
                $this->response->paraErr();
                break;
            }
            // expected format: ****课程/[***]+
            if (!$this->matchPathFormat($original)
                || ($type === 'MOVE' && !$this->matchPathFormat($edit))) {
                $this->response->invalidPath();
                break;
            }

            // check saved operations
            $result = app('db')
                ->table('edit')
                ->where([
                    ['stuId', $stuId],
                    ['original', $original]
                ])
                ->first();
            if ($result === false) {
                $this->response->databaseErr();
                break;
            }
            if ($result === NULL) {
                // no earlier record, just insert this one
                $result = app('db')
                    ->table('edit')
                    ->insert([
                        'stuId' => $stuId,
                        'type' => $type,
                        'original' => $original,
                        'edit' => $edit
                    ]);
            } else {
                // earlier record found, try update
                if ($result->edit !== $edit) {
                    $result = app('db')
                        ->table('edit')
                        ->where([
                            ['stuId', $stuId],
                            ['original', $original]
                        ])
                        ->update([
                            'edit' => $edit,
                            'type' => $type
                        ]);
                } else {
                    $this->response->success();
                    break;
                }
            }
            if ($result === false) {
                $this->response->databaseErr();
                break;
            }

            // try get fileId
            $result = app('db')
                ->table('file')
                ->where([
                    ['key', $original]
                ])
                ->first();
            if ($result === false) {
                $this->response->databaseErr();
                break;
            }
            $fileId = NULL;
            if ($result !== NULL) {
                $fileId = $result->{'fileId'};
            }

            // check if is the file's uploader
            $isUploaderEditing = false;
            if ($fileId) {
                $result = app('db')
                    ->table('contribute')
                    ->where([
                        ['stuId', $stuId],
                        ['fileId', $fileId]
                    ])
                    ->first();
                if ($result === false) {
                    $this->response->databaseErr();
                    break;
                }
                if ($result !== NULL) {
                    $isUploaderEditing = true;
                }
            }

            $result = app('db')
                ->table('edit')
                ->select(app('db')->raw('COUNT(stuId)'))
                ->where([
                    ['edit', $edit],
                    ['original', $original]
                ])
                ->first();
            if ($result === false) {
                $this->response->databaseErr();
                break;
            }

            // if admin's operation or requests over limit
            if ($result->{'COUNT(stuId)'} > env('EDIT_LIMIT')
                || $isUploaderEditing
                || $stuId === env('ADMIN_ID')
            ) {
                $oldPrefix = '';
                $newPrefix = '';
                switch ($type) {
                    case 'TRASH':
                        $oldPrefix = $original;
                        $lessonName = explode('/', $original)[1];
                        $newPrefix = str_replace("$lessonName/", "$lessonName/__ARCHIVE__/", $original);
                        break;
                    case 'MOVE':
                        $oldPrefix = $original;
                        $newPrefix = $edit;
                        break;
                    case 'RENAME':
                        $oldPrefix = $original;
                        $newPrefix = $this->popLastSection($original) . '/' . $edit;
                        break;
                }

                // replace files with prefix $original to $edit
                // get files with prefix $original
                $iterms = Qiniu::getList($original);
                if (!$iterms) {
                    $this->response->storageErr();
                    break;
                }
                $filenames = array_map(function ($cur) {
                    return $cur['key'];
                }, $iterms);
                // rename them
                foreach ($filenames as $filename) {
                    if (self::moveFile($filename, str_replace($oldPrefix, $newPrefix, $filename)) === false) {
                        self::deleteFile($filename);
                    }
                }

                // TODO: remove this edit
                // TODO: move rest edits
                // TODO: edit specific records, not resetting the whole table

                app('db')
                    ->table('file')
                    ->delete();
                $this->response->setData(['executed' => true]);
            }
            $this->response->success();
        } while (false);
        return response()->json($this->response);
    }

    private function matchPathFormat($path) {
        $path = explode('/', $path);
        if (count($path) < 2) return false;
        if (!in_array($path[0], array('专业必修课程', '专业选修课程', '公共课程'))) return false;
        $i = 1;
        $symbols = explode(' ', '^ ? : / \\ \' < > ^ * |');
        while ($i < count($path)) {
            foreach ($symbols as $symbol) {
                if (strpos($path[$i], $symbol) !== false) return false;
            }
            $i++;
        }
        return true;
    }

    private function popLastSection($path) {
        return join('/', array_slice(explode('/', $path), 0, count(explode('/', $path)) - 1));
    }

    private function shiftFirstSection($path) {
        return join('/', array_slice(explode('/', $path), 1));
    }

    private function deleteFile($path) {
        // TODO: move to archive bucket
        if (Qiniu::archive($path)) {
            return true;
        }
        $this->response->storageErr();
        return false;
    }

    private function moveFile($from, $to) {
        if (Qiniu::move($from, $to)) {
            return true;
        }
        $this->response->storageErr();
        return false;
    }
}
