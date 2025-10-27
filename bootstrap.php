<?php

use Blessing\Filter;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;

use App\Services\Hook;

use Blessing\HAuth\HAuthController;

return function (Dispatcher $events, Filter $filter) {
    //
    $filter->add('oauth_providers', function (Collection $providers) {
        $providers->put(
            'henan',
            [
                'icon' => 'henan',
                'displayName' => '统一认证',
            ]
        );
        return $providers;
    });

    // 中间件


    // 路由
    Hook::addRoute(function(){
        Route::prefix('auth/login/henan')
        ->namespace('Blessing\HAuth')
        ->middleware(['web','guest'])
        ->group(function(){
            Route::get('/',[HAuthController::class,'login']);
            Route::post('/',[HAuthController::class,'handleLogin']);
        });
    });
};
