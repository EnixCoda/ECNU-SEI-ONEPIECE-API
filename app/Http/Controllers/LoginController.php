<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class LoginController extends Controller {
    public function __construct(Request $request) {
        parent::__construct();
    }

    public function main(Request $request) {
        do {
            if (env('NO_VERIFICATION')) {
                // do nothing
            } else if ($request->has('id')
                && $request->has('password')
            ) {
                $stuId = $request->input('id');
                $password = $request->input('password');

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
                $token = $this->generateUserToken($stuId, $password);

                $result = app('db')
                    ->table('user')
                    ->where('stuId', $stuId)
                    ->first();

                if (!$result) {
                    // 无stuId记录，第一次登陆
                    $result = app('db')->table('user')
                        ->insert([
                            'stuId' => $stuId,
                            'username' => $username,
                            'password' => $password,
                            'cademy' => $cademy,
                            'token' => $token,
                            'lastAlia' => $username,
                            'created_at' => Carbon::now()->setTimezone('PRC')
                        ]);
                    if ($result === false) {
                        $this->response->databaseErr();
                        break;
                    }
                } else {
                    // 有记录
                    $result = app('db')
                        ->table('user')
                        ->where('stuId', $stuId)
                        ->update([
                            'password' => $password,
                            'token' => $token,
                            'updated_at' => Carbon::now()->setTimezone('PRC')
                        ]);
                    if ($result === false) {
                        $this->response->databaseErr();
                        break;
                    }
                }
            } else if (isset($request->cookie()['token'])) {
                $token = $request->cookie()['token'];
                $result = app('db')
                    ->table('user')
                    ->where('token', $token)
                    ->first();
                if ($result === false) {
                    $this->response->databaseErr();
                    break;
                }
                if ($result === NULL) {
                    $this->response->loginAuthFail();
                    break;
                }
                $stuId = $result->stuId;
                $result = app('db')
                    ->table('user')
                    ->where('stuId', $stuId)
                    ->update([
                        'token' => $token,
                        'updated_at' => Carbon::now()->setTimezone('PRC')
                    ]);
                if ($result === false) {
                    $this->response->databaseErr();
                    break;
                }
            } else {
                $this->response->paraErr();
                break;
            }

            if (env('NO_VERIFICATION')) {
                $user = new \stdClass();
                $user->username = 'guest';
                $user->lastAlia = 'guest';
                $user->token = 'useless_token';
            } else {
                $user = app('db')
                ->table('user')
                ->where('stuId', $stuId)
                ->first();
            }

            $this->response->setData([
                'username' => $user->username,
                'alia' => $user->lastAlia
            ]);

            setcookie('token', $user->token, 2147483647);
            $this->response->success();

            if (env('NO_VERIFICATION')) {
                $this->response->cusMsg('因校园数据库接口不可用，登陆功能已暂停使用。');
            } else {
                $this->response->cusMsg('欢迎！' . $user->lastAlia);
            }
        } while (false);

        return response()->json($this->response);
    }

    private function logInToECNU($id, $password) {
        $loginUrl = 'http://202.120.82.2:8081/ClientWeb/pro/ajax/login.aspx';
        $data = array(
            'id' => $id,
            'pwd' => $password,
            'act' => 'login',
        );

        // use key 'http' even if you send the request to https://...
        $options = array(
            'http' => array(
                'header' => 'Content-type: application/x-www-form-urlencoded\r\n',
                'method' => 'POST',
                'content' => http_build_query($data),
            ),
        );
        $context = stream_context_create($options);
        $result = file_get_contents($loginUrl, false, $context);
        return $result;
    }

    private function generateUserToken($id, $password) {
        return md5(base64_encode(md5($id, base64_encode($password))));
    }
}
