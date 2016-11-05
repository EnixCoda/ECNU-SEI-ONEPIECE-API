<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FileController extends FLController {
    public function __construct() {
        parent::__construct();
    }

    public function get(Request $request, $fileId, $section) {
        return parent::_get($request, 'file', $fileId, $section);
    }

    public function set(Request $request, $fileId, $section) {
        return parent::_set($request, 'file', $fileId, $section);
    }
}
