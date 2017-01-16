<?php

namespace App\Museum;

class File {
    function __construct($id, $name, $size, $score, $uploader, $download) {
        $this->id = $id;
        $this->name = $name;
        $this->size = $size;
        if ($score !== NULL)
            $this->score = $score;
        if ($uploader !== NULL)
            $this->uploader = $uploader;
        if ($download !== NULL)
            $this->download = $download;
    }
}
