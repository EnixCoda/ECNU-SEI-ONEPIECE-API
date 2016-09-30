<?php

namespace App\Museum;

class File
{
    function __construct($id, $name, $size, $score)
    {
        $this->id = $id;
        $this->name = $name;
        $this->size = $size;
        $this->isDir = false;
        $this->score = $score;
    }
}
