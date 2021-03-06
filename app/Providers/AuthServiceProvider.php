<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app['auth']
            ->viaRequest('api', function (Request $request) {
                if (env('NO_VERIFICATION')) {
                    $guest = new \stdClass();
                    $guest->id = "0";
                    $guest->stuId = "0";
                    $guest->username = "guest";
                    $guest->password = "guest";
                    $guest->cademy = "";
                    $guest->token = "useless_token";
                    $guest->lastAlia = "guest";
                    $guest->created_at = "";
                    $guest->updated_at = "";
                    return $guest;
                }
                if (isset($request->cookie()['token'])) {
                    return app('db')
                        ->table('user')
                        ->where('token', $request->cookie()['token'])
                        ->first();
                }
                return NULL;
            });
    }
}
