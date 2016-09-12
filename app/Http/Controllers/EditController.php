<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;

class EditController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get(Request $request)
    {
        do {
            if (!$request->has("path")) {
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
                "MOVE" => [],
                "TRASH" => 0,
                "RENAME" => []
            );
            foreach ($result as $row) {
                $type = $row->type;
                $edit = $row->edit;
                // get modification string
                if ($type == "MOVE") {
                    $edits["MOVE"][$edit] = isset($edits["MOVE"][$edit]) ? $edits["MOVE"][$edit] + 1 : 1;
                } else if ($type == "TRASH") {
                    $edits["TRASH"]++;
                } else if ($type == "RENAME") {
                    $newName = explode("/", $edit)[count(explode("/", $edit)) - 1];
                    $edits["RENAME"][$newName] = isset($edits["TRASH"][$newName]) ? $edits["TRASH"][$newName] + 1 : 1;
                }
            }

            // turn $edits["MOVE"] into array: [edit, count]
            $editsMoveArr = [];
            foreach ($edits["MOVE"] as $key => $value) {
                array_push($editsMoveArr, [$key, $value]);
            }
            $edits["MOVE"] = $editsMoveArr;

            // so as $edits["RENAME"]
            $editsRenameArr = [];
            foreach ($edits["RENAME"] as $key => $value) {
                array_push($editsRenameArr, [$key, $value]);
            }
            $edits["RENAME"] = $editsRenameArr;

            $this->response->setData(["edits" => $edits]);
            $this->response->success();
        } while (false);

        return response()->json($this->response);
    }

    public function set(Request $request)
    {
        do {
            if (!$request->has("original")
                || !$request->has("edit")
                || !$request->has("type")
                || !$request->has("token")
            ) {
                $this->response->paraErr();
                break;
            }
            $original = $request->input("original");
            $edit = $request->input("edit");
            $type = $request->input("type");
            $token = $request->input("token");
            $stuId = $this->getIdFromToken($token);

            if (!in_array($type, ["MOVE", "RENAME", "TRASH"])) {
                $this->response->paraErr();
                break;
            }

            if ($type == "TRASH" && $edit != "-") {
                $this->response->paraErr();
                break;
            }
            // expected format: ****课程/[***]+
            if (!$this->matchPathFormat($original)
            || ($type == "MOVE" && !$this->matchPathFormat($edit))) {
                $this->response->invalidPath();
                break;
            }

            // check saved operations
            $result = app('db')
                ->table('edit')
                ->where([
                    ["stuId", $stuId],
                    ["original", $original]
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
                        "stuId" => $stuId,
                        "type" => $type,
                        "original" => $original,
                        "edit" => $edit
                    ]);
            } else {
                // earlier request found, try update
                if ($result->edit != $edit) {
                    $result = app('db')
                        ->table('edit')
                        ->where([
                            ["stuId", $stuId],
                            ["original", $original]
                        ])
                        ->update([
                            "edit" => $edit,
                            "type" => $type
                        ]);
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
                    ["key", $original]
                ])
                ->first();
            if ($result === false) {
                $this->response->databaseErr();
                break;
            }
            $fileId = NULL;
            if ($result !== NULL) {
                $fileId = $result->{"fileId"};
            }

            // check if is the file's uploader
            $isUploaderEditing = false;
            if ($fileId) {
                $result = app('db')
                    ->table('contribute')
                    ->where([
                        ["stuId", $stuId],
                        ["fileId", $fileId]
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
                    ["edit", $edit],
                    ["original", $original]
                ])
                ->first();
            if ($result === false) {
                $this->response->databaseErr();
                break;
            }

            // if admin's operation or requests over limit
            if ($result->{"COUNT(stuId)"} > env("EDIT_LIMIT")
                || $isUploaderEditing
                || $stuId == env("ADMIN_ID")) {
                switch ($type) {
                    case "TRASH":
                        $oldPrefix = $original;
                        $lessonName = explode("/", $original)[1];
                        $newPrefix = str_replace("$lessonName/", "$lessonName/__ARCHIVE__/", $original);
                        break;
                    case "MOVE":
                        $oldPrefix = $original;
                        $newPrefix = $edit;
                        break;
                    case "RENAME":
                        $oldPrefix = $original;
                        $newPrefix = $this->popLastSection($original) . "/" . $edit;
                        break;
                }

                // replace files with prefix $original to $edit
                $auth = new Auth(env("QINIU_AK"), env("QINIU_SK"));
                $bucketManager = new BucketManager($auth);
                // get files with prefix $original
                $prefix = $original;
                $marker = '';
                $limit = 1000;
                list($iterms, $marker, $err) = $bucketManager->listFiles(env("QINIU_BUCKET_NAME"), $prefix, $marker, $limit);
                if ($err !== NULL) {
                    $this->response->storageErr();
                    break;
                }
                $filenames = array_map(function ($cur) {
                    return $cur["key"];
                }, $iterms);
                // rename them
                foreach ($filenames as $filename) {
                    if ($bucketManager->rename(env("QINIU_BUCKET_NAME"), $filename, str_replace($oldPrefix, $newPrefix, $filename)) !== NULL) {
                        $bucketManager->delete(env("QINIU_BUCKET_NAME"), $filename);
                    }
                }

                app('db')
                    ->table('file')
                    ->delete();
            }
            $this->response->success();
        } while (false);
        return response()->json($this->response);
    }

    function matchPathFormat($path)
    {
        $path = explode("/", $path);
        if (count($path) < 2) return false;
        if (!in_array($path[0], array("专业必修课程", "专业选修课程", "公共课程"))) return false;
        $i = 1;
        $symbols = explode(" ", "^ ? : / \\ \" < > ^ * |");
        while ($i < count($path)) {
            foreach ($symbols as $symbol) {
                if (strpos($path[$i], $symbol) !== false) return false;
            }
            $i++;
        }
        return true;
    }

    function popLastSection ($path) {
        return join("/", array_slice(explode("/", $path), 0, count(explode("/", $path)) - 1));
    }

    function shiftFirstSection ($path) {
        return join("/", array_slice(explode("/", $path), 1));
    }
}
