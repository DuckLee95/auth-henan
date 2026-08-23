<?php

use Blessing\Filter;

use Illuminate\Contracts\Events\Dispatcher;

use App\Services\Hook;

use Blessing\HAuth\HAuthController;

return function (Dispatcher $events, Filter $filter) {
    $appendEntry = static function (array $rows, string $entry) {
        $rows = array_values(array_filter($rows, static function ($row) use ($entry) {
            return $row !== $entry;
        }));
        $rows[] = $entry;

        return $rows;
    };

    $filter->add('auth_page_rows:login', static function (array $rows) use ($appendEntry) {
        return $appendEntry($rows, 'Blessing\\HAuth::auth.entry');
    }, 100);
    $filter->add('auth_page_rows:register', static function (array $rows) use ($appendEntry) {
        return $appendEntry($rows, 'Blessing\\HAuth::auth.register-entry');
    }, 100);

    Hook::addRoute(function () {
        Route::prefix('auth')
            ->namespace('Blessing\HAuth')
            ->middleware(['web', 'guest'])
            ->group(function () {
                Route::get('login/henan', [HAuthController::class, 'login']);
                Route::post('login/henan', [HAuthController::class, 'handleLogin']);
                Route::get('register/henan', [HAuthController::class, 'register']);
                Route::post('register/henan', [HAuthController::class, 'handleRegister']);
            });
    });
};
