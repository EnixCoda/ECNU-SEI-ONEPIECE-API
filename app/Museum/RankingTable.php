<?php

namespace App\Museum;

class RankingTable {
    public function __construct() {
        $this->table = new \stdClass();
    }

    public function addScore($stuId, $type, $score) {
        if (!isset($this->table->{$stuId}))
            $this->table->{$stuId} = new Contributor();
        switch ($type) {
            case 'UPLOAD':
                $this->table->{$stuId}->setUploadScore($score);
                break;
            case 'RATE':
                $this->table->{$stuId}->setRateScore($score);
                break;
            case 'COMMENT':
                $this->table->{$stuId}->setCommentScore($score);
                break;
            case 'FILE':
                $this->table->{$stuId}->setFileScore($score);
                break;
            default:
                return false;
        }
        return true;
    }

    public function setName($stuId, $name) {
        //        var_dump($this->table);
        if (isset($this->table->{$stuId}))
            $this->table->{$stuId}->setName($name);
    }

    public function mark($stuId) {
        if (isset($this->table->{$stuId}))
            $this->table->{$stuId}->mark();
    }

    public function get($stuId) {
        if (isset($this->table->{$stuId}))
            return $this->table->{$stuId};
        return NULL;
    }

    public function sort() {
        $table = json_decode(json_encode($this->table), true);
        $sorted = array();
        foreach ($table as $stuId => $scores) {
            $scores['totalScore'] = $scores['uploadScore'] + $scores['rateScore'] + $scores['commentScore'] + $scores['fileScore'];
            $scores['rcScore'] = $scores['rateScore'] + $scores['commentScore'];

            $i = 0;
            while ($i < count($sorted) && $scores['totalScore'] < $sorted[$i]['totalScore']) {
                $i++;
            }
            array_splice($sorted, $i, 0, array($stuId => $scores));
        }

        return $sorted;
    }
}
