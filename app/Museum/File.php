<?php

namespace App\Museum;

class File {
    function __construct($id, $name, $size, $score, $uploader) {
        $this->id = $id;
        $this->name = $name;
        $this->size = $size;
        if ($score !== NULL)
            $this->score = $score;
        if ($uploader !== NULL)
            $this->uploader = $uploader;
    }
}
