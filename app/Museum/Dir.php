<?php

namespace App\Museum;

class Dir {
    function __construct($name) {
        $this->name = $name;
        $this->content = [];
    }
}
