<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LessonController extends FLController {
    public function __construct() {
        parent::__construct();
    }

    public function get(Request $request, $lessonName, $section) {
        return parent::_get($request, 'lesson', $lessonName, $section);
    }

    public function set(Request $request, $lessonName, $section) {
        return parent::_set($request, 'lesson', $lessonName, $section);
    }
}