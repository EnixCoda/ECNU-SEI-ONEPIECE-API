<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LookController extends Controller {
    public function __construct() {
        parent::__construct();
    }

    public function get(Request $request) {
        if ($request->user()->{'stuId'} === env('ADMIN_ID')
            && $table = $request->input('table')) {
            $limit = $request->input('limit') ? $request->input('limit') : 10;
            $result = app('db')
                ->table($table)
                ->orderBy('id', 'desc')
                ->take($limit)
                ->get();
            return response()->json($result);
        }
    }

    public function set() {

    }
}
