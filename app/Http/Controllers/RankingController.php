<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


class RankingTable
{
    public function __construct()
    {
        $this->table = new \stdClass();
    }

    public function addScore($stuId, $type, $score)
    {
        if (!isset($this->table->{$stuId})) $this->table->{$stuId} = new Contributor();
        switch ($type) {
            case "UPLOAD":
                $this->table->{$stuId}->setUploadScore($score);
                break;
            case "RATE":
                $this->table->{$stuId}->setRateScore($score);
                break;
            case "COMMENT":
                $this->table->{$stuId}->setCommentScore($score);
                break;
            case "FILE":
                $this->table->{$stuId}->setFileScore($score);
                break;
            default:
                return false;
        }
        return true;
    }

    public function setName($stuId, $name)
    {
//        var_dump($this->table);
        if (isset($this->table->{$stuId})) $this->table->{$stuId}->setName($name);
    }

    public function mark($stuId)
    {
        if (isset($this->table->{$stuId})) $this->table->{$stuId}->mark();
    }

    public function get($stuId)
    {
        if (isset($this->table->{$stuId})) return $this->table->{$stuId};
        return NULL;
    }

    public function sort()
    {
        $table = json_decode(json_encode($this->table), true);
        $sorted = array();
        foreach ($table as $stuId => $scores) {
            $scores["totalScore"] = $scores["uploadScore"] + $scores["rateScore"] + $scores["commentScore"] + $scores["fileScore"];
            $scores["rcScore"] = $scores["rateScore"] + $scores["commentScore"];

            $i = 0;
            while ($i < count($sorted) && $scores["totalScore"] < $sorted[$i]["totalScore"]) {
                $i++;
            }
            array_splice($sorted, $i, 0, array($stuId => $scores));
        }

        return $sorted;
    }
}

class Contributor
{
    public function __construct()
    {
        $this->uploadScore = 0;  // scores of uploading file
        $this->rateScore = 0;    // scores of rating & commenting file
        $this->commentScore = 0; // scores of rating & commenting file
        $this->fileScore = 0;    // scores of his files
        $this->name = "匿名";
    }

    public function setUploadScore($score)
    {
        $this->uploadScore = $score;
    }

    public function setRateScore($score)
    {
        $this->rateScore = $score;
    }

    public function setCommentScore($score)
    {
        $this->commentScore = $score;
    }

    public function setFileScore($score)
    {
        $this->fileScore = $score;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function mark()
    {
        $this->mark = true;
    }

    public function get()
    {
        $r = new \stdClass();
        $r->totalScore = $this->uploadScore + $this->rateScore + $this->commentScore;
        $r->uploadScore = $this->uploadScore;
        $r->rcScore = $this->rateScore + $this->commentScore;
        return $r;
    }
}

class RankingController extends Controller
{
    protected $bonus;

    public function __construct()
    {
        parent::__construct();

        $this->bonus = [
            "UPLOAD" => 10,
            "RATE" => 1,
            "COMMENT" => 3,
            "BAD" => -20,
        ];
    }

    public function get(Request $request)
    {
        app('db')->enableQueryLog();
        do {
            $userId = "";
            if ($request->has('token')) {
                $result = app('db')
                    ->table('user')
                    ->select('stuId')
                    ->where('token', $request->input('token'))
                    ->first();
                if (!$result) {
                    $this->response->invalidUser();
                    break;
                }
                \header("stuId:$result->stuId");
                $userId = $result->stuId;
            }

            // get map of contribute, fileId >> stuId
            $contri = [];
            $resultContri = app('db')
                ->table('contribute')
                ->get();
            if ($resultContri === false) {
                $this->response->databaseErr();
                break;
            } else {
                foreach ($resultContri as $row) {
                    $contri[$row->fileId] = $row->stuId;
                }
            }

            $rankingTable = new RankingTable();

            // get score of the files they contributed
            // SELECT stuId, COUNT(DISTINCT fileId) FROM contribute GROUP BY stuId
            $resultContribute = app('db')
                ->table('contribute')
                ->select(app('db')->raw('stuId, COUNT(DISTINCT fileId)'))
                ->groupBy('stuId')
                ->get();
            if ($resultContribute === false) {
                $this->response->databaseErr();
                break;
            } else {
                foreach ($resultContribute as $row) {
                    $stuId = $row->stuId;
                    $score = $row->{"COUNT(DISTINCT fileId)"} * $this->bonus["UPLOAD"];
                    $rankingTable->addScore($stuId, "UPLOAD", $score);
                }
            }

            // add each file's score onto contributors
            // SELECT fileId, SUM(score) FROM fileScores GROUP BY fileId
            $resultScores = app('db')->table('score')
                ->select(app('db')->raw('key, SUM(score)'))
                ->groupBy('key')
                ->get();
            if ($resultScores === false) {
                $this->response->databaseErr();
                $this->response->appendMsg("3");
                break;
            } else {
                foreach ($resultScores as $row) {
                    $fileId = $row->key;
                    $score = $row->{'SUM(score)'};
                    if (isset($contri[$fileId])) {
                        $stuId = $contri[$fileId];
                    } else {
                        $stuId = env("ADMIN_ID");
                    }
                    $rankingTable->addScore($stuId, "FILE", $score);
                }
            }

            // add rate score onto contributors
            // SELECT stuId, COUNT(*) FROM fileScores GROUP BY stuId
            $resultScores = app('db')->table('score')
                ->select(app('db')->raw('stuId, COUNT(*)'))
                ->groupBy('stuId')
                ->get();
            if ($resultScores === false) {
                $this->response->databaseErr();
                break;
            } else {
                foreach ($resultScores as $row) {
                    $stuId = $row->stuId;
                    $score = $row->{"COUNT(*)"} * $this->bonus["RATE"];
                    $rankingTable->addScore($stuId, "RATE", $score);
                }
            }

            // add comment score onto contributors
            // SELECT stuId, COUNT(*) FROM fileComments GROUP BY stuId
            $resultScores = app('db')->table('comment')
                ->select(app('db')->raw('stuId, COUNT(*)'))
                ->groupBy('stuId')
                ->get();
            if ($resultScores === false) {
                $this->response->databaseErr();
                break;
            } else {
                foreach ($resultScores as $row) {
                    $stuId = $row->stuId;
                    $score = $row->{'COUNT(*)'} * $this->bonus["COMMENT"];
                    $rankingTable->addScore($stuId, "RATE", $score);
                }
            }

            // turn stuId into username
            // SELECT lastAlia, stuId FROM users
            $resultNames = app('db')
                ->table('user')
                ->select('lastAlia', 'stuId')
                ->get();
            if ($resultNames === false) {
                $this->response->databaseErr();
                break;
            } else {
                foreach ($resultNames as $row) {
                    $rankingTable->setName($row->stuId, $row->lastAlia);
                }
            }

            if ($userId != "") {
                $rankingTable->mark($userId);
            }

            $scoresArr = $rankingTable->sort();
            $pos = 1;
            $userRanking = NULL;
            foreach ($scoresArr as $ranking) {
                if (isset($ranking["mark"])) {
                    $userRanking = $this->cleanContributor($ranking, $pos);
                    break;
                }
                $pos++;
            }

            $scoresArr = array_map(function ($cur) {
                return $this->cleanContributor($cur);
            }, array_slice($scoresArr, 0, 10));
            $data = array("ranking" => $scoresArr);
            if ($userId != "") {
                if ($userRanking !== NULL) {
                    $data["userRanking"] = $userRanking;
                } else {
                    $data["userRanking"] = array(
                        "pos" => "-",
                        "name" => "-",
                        "total" => "-",
                        "rc" => "-",
                        "upload" => "-"
                    );
                }
            }
            $this->response->setData($data);
            $this->response->success();
        } while (false);
        return response()->json($this->response);
    }
    
    function cleanContributor($contri, $pos = NULL)
    {
        $clean = array(
            "name" => $contri["name"],
            "total" => $contri["totalScore"],
            "rc" => $contri["rcScore"],
            "upload" => $contri["fileScore"] + $contri["uploadScore"]
        );
        if ($pos != NULL) $clean["pos"] = $pos;
        return $clean;
    }
}
