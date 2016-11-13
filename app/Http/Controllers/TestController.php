<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Faker\Provider\cs_CZ\DateTime;
use Illuminate\Http\Request;
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;

class TestController extends Controller {
    public function __construct() {
        parent::__construct();
    }

    public function get(Request $request) {
        //        input validate with below
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

        $this->response->cusMsg($request->ips());

        return response()->json($this->response);
    }
}
