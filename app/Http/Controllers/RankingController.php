<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Museum\RankingTable;

class RankingController extends Controller {
    protected $bonus;

    public function __construct() {
        parent::__construct();
        $this->bonus = [
            'UPLOAD' => 10,
            'RATE' => 1,
            'COMMENT' => 3,
            'BAD' => -20,
        ];
    }

    public function get(Request $request) {
        do {
            $userId = '';
            if (isset($request->cookie()['token'])) {
                $result = app('db')
                    ->table('user')
                    ->select('stuId')
                    ->where('token', $request->cookie()['token'])
                    ->first();
                if ($result === false) {
                    $this->response->databaseErr();
                    break;
                }
                if ($result === NULL) {
                    $this->response->invalidUser();
                    break;
                }
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
            }
            foreach ($resultContri as $row) {
                $contri[$row->fileId] = $row->stuId;
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
            }
            foreach ($resultContribute as $row) {
                $stuId = $row->stuId;
                $score = $row->{'COUNT(DISTINCT fileId)'} * $this->bonus['UPLOAD'];
                $rankingTable->addScore($stuId, 'UPLOAD', $score);
            }

            // add each file's score onto contributors
            // SELECT fileId, SUM(score) FROM fileScores GROUP BY fileId
            $resultScores = app('db')->table('score')
                ->select(app('db')->raw('key, SUM(score)'))
                ->groupBy('key')
                ->get();
            if ($resultScores === false) {
                $this->response->databaseErr();
                break;
            } else {
                foreach ($resultScores as $row) {
                    $fileId = $row->key;
                    $score = $row->{'SUM(score)'};
                    if (isset($contri[$fileId])) {
                        $stuId = $contri[$fileId];
                    } else {
                        $stuId = env('ADMIN_ID');
                    }
                    $rankingTable->addScore($stuId, 'FILE', $score);
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
                    $score = $row->{'COUNT(*)'} * $this->bonus['RATE'];
                    $rankingTable->addScore($stuId, 'RATE', $score);
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
                    $score = $row->{'COUNT(*)'} * $this->bonus['COMMENT'];
                    $rankingTable->addScore($stuId, 'RATE', $score);
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

            if ($userId !== '') {
                $rankingTable->mark($userId);
            }

            $scoresArr = $rankingTable->sort();
            $pos = 1;
            $userRanking = NULL;
            foreach ($scoresArr as $ranking) {
                if (isset($ranking['mark'])) {
                    $userRanking = $this->cleanContributor($ranking, $pos);
                    break;
                }
                $pos++;
            }

            $scoresArr = array_map(function ($cur) {
                return $this->cleanContributor($cur);
            }, array_slice($scoresArr, 0, 10));
            $data = array('ranking' => $scoresArr);
            if ($userId !== '') {
                if ($userRanking !== NULL) {
                    $data['userRanking'] = $userRanking;
                } else {
                    $data['userRanking'] = array(
                        'pos' => '-',
                        'name' => '-',
                        'total' => '-',
                        'rc' => '-',
                        'upload' => '-'
                    );
                }
            }
            $this->response->setData($data);
            $this->response->success();
        } while (false);
        return response()->json($this->response);
    }
    
    private function cleanContributor($contri, $pos = NULL) {
        $clean = array(
            'name' => $contri['name'],
            'total' => $contri['totalScore'],
            'rc' => $contri['rcScore'],
            'upload' => $contri['fileScore'] + $contri['uploadScore']
        );
        if ($pos !== NULL) $clean['pos'] = $pos;
        return $clean;
    }
}
