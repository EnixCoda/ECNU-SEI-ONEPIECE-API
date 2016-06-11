<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Request;

class LogMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next, $info)
    {
        $stuId = "-";
        $action = $this->parseAction($request, $info);
        $result = app('db')->table('log')
            ->insert([
                "id" => NULL,
                "stuId" => $stuId,
                "action" => $action,
                "created_at" => \Carbon\Carbon::now()
            ]);
        return $next($request);
    }

    function parseAction ($request, $info)
    {
        $uri = $request->path();
        return $info;
        switch ($info) {
            case "index":
                return "index";
            case "login":
                if ($id = $request->input("id")
                    && $password = $request->input("password")){
                    return "login $id $password";
                }
                return "login";
            default:
                return "";
        }
    }
}
