<?php

namespace App\Museum;

class Contributor {
    public function __construct() {
        $this->uploadScore = 0;  // scores of uploading file
        $this->rateScore = 0;    // scores of rating & commenting file
        $this->commentScore = 0; // scores of rating & commenting file
        $this->fileScore = 0;    // scores of his files
        $this->name = "匿名";
    }

    public function setUploadScore($score) {
        $this->uploadScore = $score;
    }

    public function setRateScore($score) {
        $this->rateScore = $score;
    }

    public function setCommentScore($score) {
        $this->commentScore = $score;
    }

    public function setFileScore($score) {
        $this->fileScore = $score;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function mark() {
        $this->mark = true;
    }

    public function get() {
        $r = new \stdClass();
        $r->totalScore = $this->uploadScore + $this->rateScore + $this->commentScore;
        $r->uploadScore = $this->uploadScore;
        $r->rcScore = $this->rateScore + $this->commentScore;
        return $r;
    }
}
