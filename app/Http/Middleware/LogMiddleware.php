<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Closure;

class LogMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next) {
        $stuId = "-";
        if (isset($request->cookie()['token'])) {
            $token = $request->cookie()['token'];
            $stu = app('db')
                ->table('user')
                ->where([
                    ['token', $token]
                ])
                ->first();
            $stuId = $stu->stuId;
        }
        $action = $this->parseAction($request);
        app('db')
            ->table('log')
            ->insert([
                "stuId" => $stuId,
                "action" => $action,
                "created_at" => Carbon::now()
            ]);
        return $next($request);
    }

    private function parseAction(Request $request) {
        return json_encode([
            "method" => $request->method(),
            "path" => $request->path(),
            "input" => $request->all()
        ]);
    }
}
