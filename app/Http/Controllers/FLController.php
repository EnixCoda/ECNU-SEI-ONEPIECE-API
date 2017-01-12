<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class FLController extends Controller {
    public function __construct() {
        parent::__construct();
    }

    public function _get(Request $request, $type, $key, $section) {
        $validate = app('validator')
            ->make($request->all(), [
                'type' => 'required | in: file, lesson'
            ]);
        if ($validate->fails()) {
            $this->response->paraErr();
        }
        switch ($section) {
            case "score":
                $tableName = "score";
                $result = app('db')
                    ->table($tableName)
                    ->select(app('db')->raw('SUM(score)'))
                    ->where([
                        ['key', $key]
                    ])
                    ->first();
                if ($result === false) {
                    $this->response->databaseErr();
                } else {
                    $score = $result->{'SUM(score)'} === NULL ? 0 : $result->{'SUM(score)'};
                    $this->response->setData(["total_score" => $score]);
                    $this->response->success();
                }
                break;
            case "comment":
                $tableName = "comment";
                $result = app('db')
                    ->table($tableName)
                    ->select('username', 'comment', 'created_at as time')
                    ->where([
                        ['key', $key]
                    ])
                    ->get();
                if ($result === false) {
                    $this->response->databaseErr();
                } else {
                    // yyyy-mm-dd hh:mm:ii >> yyyy-mm-dd
                    foreach ($result as &$comment) {
                        $comment->time = explode(" ", $comment->time)[0];
                    }
                    $this->response->setData(["comments" => $result]);
                    $this->response->success();
                }
                break;
            case "download":
                switch ($type) {
                    case "file":
                        $user = $request->user();
                        if ($user === NULL) {
                            $this->response->invalidUser();
                            break;
                        }
                        $fileId = $key;
                        $result = app('db')
                            ->table('file')
                            ->where('fileId', $fileId)
                            ->first();
                        if ($result === false) {
                            $this->response->databaseErr();
                            break;
                        }
                        if ($result === NULL) {
                            $this->response->fileNotExist();
                            break;
                        }
                        $key = $result->{"key"};
                        $filename = array_slice(explode("/", $key), -1)[0];
                        $this->response->setData(["downloadLink" => env("QINIU_SPACE_DOMAIN") . rawurlencode($key) . "?attname=$filename"]);
                        $this->response->success();
                        break;
                    case "lesson":
                        $user = $request->user();
                        if ($user === NULL) {
                            $this->response->invalidUser();
                            break;
                        }
                        $lessonName = rawurldecode($key);
                        if (!file_exists(env("ARCHIVE_ROOT") . $lessonName . ".zip")) {
                            $this->response->fileNotExist();
                            break;
                        }
                        if ($request->has("confirmed")) {
                            return response()->download(env("ARCHIVE_ROOT") . $lessonName . ".zip",
                                $lessonName . ".zip",
                                [
                                    "Content-type" => "application/octet-stream;"
                                ]);
                        }
                        $query = http_build_query([
                            "confirmed" => "1"
                        ]);
                        $this->response->setData(["link" => $request->url() . "?" . $query]);
                        $this->response->success();
                        break;
                    default:
                        $this->response->invalidPath();
                        break;
                }
                break;
            case "preview":
                $user = $request->user();
                if ($user === NULL) {
                    $this->response->invalidUser();
                    break;
                }
                switch ($type) {
                    case "file":
                        $fileId = $key;
                        $result = app('db')
                            ->table('file')
                            ->where('fileId', $fileId)
                            ->first();
                        if ($result === false) {
                            $this->response->databaseErr();
                            break;
                        }
                        if ($result === NULL) {
                            $this->response->fileNotExist();
                            break;
                        }
                        $key = $result->{'key'};
                        $filename = array_slice(explode('/', $key), -1)[0];
                        $explodedFilename = explode('.', $filename);
                        $fileExtensionName = array_pop($explodedFilename);
                        $imagePreview = in_array(strtolower($fileExtensionName), ['jpg', 'jpeg', 'bmp', 'png']);
                        $docPreview = in_array(strtolower($fileExtensionName), ['pdf', 'doc', 'docx', 'ppt', 'pptx']);
                        if (!$imagePreview && !$docPreview) {
                            $this->response->cusMsg('不可预览的文件类型');
                            break;
                        }
                        if ($imagePreview) {
                            $this->response->setData([
                                'previewLink' => env('QINIU_SPACE_DOMAIN') . rawurlencode($key) . '?imageView2/2/w/600/interlace/1'
                            ]);
                        } else {
                            $this->response->setData([
                                'previewLink' => env('QINIU_SPACE_DOMAIN') . rawurlencode($key) . '?yifangyun_preview/v2/action=get_preview/format=jpg/page_number=1',
                                'multiPage' => true
                            ]);
                        }
                        $this->response->success();
                        break;
                    default:
                        $this->response->invalidPath();
                        break;
                }
                break;

            default:
                $this->response->invalidPath();
        }
        return response()->json($this->response);
    }

    public function _set(Request $request, $type, $key, $section) {
        $stuId = $request->user()->stuId;
        switch ($section) {
            case "score":
                if (in_array($type, ["file"]) === false) {
                    $this->response->invalidPath();
                    break;
                }
                $validate = app('validator')
                    ->make($request->all(), [
                        'score' => 'required'
                    ]);
                if ($validate->fails()) {
                    $this->response->paraErr();
                    break;
                }

                $score = $request->input("score") < 0 ? -2 : 1;
                $tableName = "score";
                $result = app('db')
                    ->table($tableName)
                    ->select('score')
                    ->where([
                        ['key', $key],
                        ['stuId', $stuId]
                    ])
                    ->first();
                if ($result === false) {
                    $this->response->databaseErr();
                    break;
                } else {
                    if ($result === NULL) {
                        $result = app('db')
                            ->table('score')
                            ->insert([
                                "stuId" => $stuId,
                                "type" => $type,
                                "key" => $key,
                                "score" => $score,
                                "created_at" => Carbon::now()->setTimezone('PRC')
                            ]);
                        if ($result === false) {
                            $this->response->databaseErr();
                        } else {
                            $this->response->success();
                        }
                    } else {
                        $result = app('db')
                            ->table('score')
                            ->where([
                                ['stuId', $stuId],
                                ['key', $key]
                            ])
                            ->update([
                                "score" => $score,
                                "updated_at" => Carbon::now()->setTimezone('PRC')
                            ]);
                        if ($result === false) {
                            $this->response->databaseErr();
                        } else {
                            $this->response->success();
                        }
                    }
                }
                break;

            case "comment":
                if (in_array($type, ["file", "lesson"]) === false) {
                    $this->response->invalidPath();
                    break;
                }
                $validate = app('validator')
                    ->make($request->all(), [
                        'comment' => 'required',
                        'username' => 'required'
                    ]);
                if ($validate->fails()) {
                    $this->response->paraErr();
                    break;
                }

                $comment = $request->input("comment");
                $username = $request->input("username");
                if ($this->lengthOverflow($comment, env("MAX_COMMENT_LENGTH"))
                    || $this->lengthOverflow($username, env("MAX_USERNAME_LENGTH"))
                ) {
                    $this->response->paraErr();
                    $this->response->appendMsg("length");
                    break;
                }
                $tableName = "comment";
                $result = app('db')
                    ->table($tableName)
                    ->insert([
                        "stuId" => $stuId,
                        "comment" => $comment,
                        "username" => $username,
                        "type" => $type,
                        "key" => $key,
                        "created_at" => Carbon::now()->setTimezone('PRC')
                    ]);
                if ($result === false) {
                    $this->response->databaseErr();
                    break;
                }
                $result = app('db')
                    ->table('user')
                    ->where('stuId', $stuId)
                    ->update([
                        "lastAlia" => $username
                    ]);
                if ($result === false) {
                    $this->response->databaseErr();
                    break;
                }
                $this->response->success();
                break;

            default:
                $this->response->invalidPath();
        }
        return response()->json($this->response);
    }

    private function lengthOverflow($content, $limit) {
        if (is_string($content))
            return $this->utf8StrLen($content) > $limit;
        if (is_array($content)) {
            foreach ($content as $text) {
                if (!is_string($text) || $this->utf8StrLen($text) > $limit)
                    return true;
            }
            return false;
        }
        return true;
    }

    private function utf8StrLen($string = NULL) {
        preg_match_all("/./us", $string, $match);
        return count($match[0]);
    }
}
