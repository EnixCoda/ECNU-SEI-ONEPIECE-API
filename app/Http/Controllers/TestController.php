<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;

class TestController extends Controller {
    public function __construct() {
        parent::__construct();
    }

    public function get(Request $request) {
        // TODO: input validate with below
        //        $this->validate($request, [
        //            'aaa' => 'required|exists:user,stuId',
        //            'a' => 'exists:user,stuId',
        //            'b' => 'required',
        //            'c' => 'present',
        //            'd' => 'required|regex:[^a$]'
        //        ]);

        //        $validator = Validator::make($request->all(), [
        //            'a' => 'exists:user,stuId',
        //            'b' => 'required',
        //        ]);
        //
        //        if ($validator->fails()) {
        //            return redirect('aaaaaaa')
        //                ->withErrors($validator);
        //        }
        //        $validator = app('validator')
        //            ->make($request->all(), [
        //                'aaa' => 'required|exists:user,stuId',
        //                'a' => 'exists:user,stuId',
        //                'b' => 'required',
        //                'c' => 'present',
        //                'd' => 'required|regex:[^a$]'
        //            ]);
        //        if ($validator->fails()) {
        //            return 'fail';
        //        }

        $result = app('db')
            ->table('log')
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        $this->response->setData($result);
        return response()->json($this->response);
    }
}
