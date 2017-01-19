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
        if ($request->user()) {
            $stuId = $request->user()->stuId;
        } else {
            $stuId = join(', ', $request->ips());
        }
        $action = $this->parseAction($request);
        app('db')
            ->table('log')
            ->insert([
                'stuId' => $stuId,
                'action' => $action,
                'created_at' => Carbon::now()->setTimezone('PRC')
            ]);
        return $next($request);
    }

    private function parseAction(Request $request) {
        return json_encode([
            'method' => $request->method(),
            'path' => $request->path(),
            'input' => $request->all()
        ]);
    }
}
