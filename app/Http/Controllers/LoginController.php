<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __construct(Request $request)
    {
        parent::__construct();
    }

    public function main(Request $request)
    {
        if ($request->has("id")
            && $request->has("password")
        ) {
            do {
                $stuId = $request->input("id");
                $password = $request->input("password");

                $result = $this->logInToECNU($stuId, $password);

                if (!$result) {
                    $this->response->loginServerErr();
                    break;
                }

                $data = json_decode($result);
                if ($data->ret == 0) {
                    $this->response->loginAuthFail();
                    break;
                }

                $username = $data->data->name;
                $cademy = $data->data->cls;
                $token = $this->genUserToken($stuId, $password);

                //save to database
                $result = app('db')->table('user')
                    ->select(app('db')->raw('*'))
                    ->where('stuId', '=', $stuId)
                    ->first();

                if (!$result) {
                    // 无stuId记录，第一次登陆
                    $result = app('db')->table('user')
                        ->insert([
                            "stuId" => $stuId,
                            "password" => $password,
                            "cademy" => $cademy,
                            "token" => $token,
                            "lastAlia" => $username,
                            "created_at" => \Carbon\Carbon::now()
                        ]);
                    if (!$result) {
                        $this->response->databaseErr();
                    }
                } else {
                    // 有记录
                    $result = app('db')
                        ->table('user')
                        ->where("stuId", $stuId)
                        ->update([
                            "password" => $password,
                            "token" => $token,
                            "updated_at" => \Carbon\Carbon::now()
                        ]);
                    if (!$result) {
                        $this->response->databaseErr();
                    }
                }
                
                $this->response->setData([
                    'username' => $username,
                    'cademy' => $cademy,
                    'token' => $token,
                ]);
                $this->response->success();
            } while (false);
        }

        return response()->json($this->response);
    }
    
    private function logInToECNU($id, $password)
    {
        $loginUrl = 'http://202.120.82.2:8081/ClientWeb/pro/ajax/login.aspx';
        $data = array(
            'id' => $id,
            'pwd' => $password,
            'act' => 'login',
        );

        // use key 'http' even if you send the request to https://...
        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ),
        );
        $context = stream_context_create($options);
        $result = file_get_contents($loginUrl, false, $context);
        return $result;
    }

    private function genUserToken($id, $password)
    {
        return md5(base64_encode(md5($id, base64_encode($password))));
    }
}
