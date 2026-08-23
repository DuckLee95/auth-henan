<?php

use Blessing\Filter;

use Illuminate\Contracts\Events\Dispatcher;

use App\Services\Hook;

use Blessing\HAuth\HAuthController;

return function (Dispatcher $events, Filter $filter) {
    // Keep the school authentication entry independent from OAuthCore.
    // A high priority makes the entry stay at the bottom of both auth pages.
    $appendEntry = static function (array $rows) {
        $entry = 'Blessing\\HAuth::auth.entry';
        $rows = array_values(array_filter($rows, static function ($row) use ($entry) {
            return $row !== $entry;
        }));
        $rows[] = $entry;

        return $rows;
    };

    $filter->add('auth_page_rows:login', $appendEntry, 100);
    $filter->add('auth_page_rows:register', $appendEntry, 100);

    Hook::addRoute(function () {
        Route::prefix('auth/login/henan')
            ->namespace('Blessing\HAuth')
            ->middleware(['web', 'guest'])
            ->group(function () {
                Route::get('/', [HAuthController::class, 'login']);
                Route::post('/', [HAuthController::class, 'handleLogin']);
            });
    });
};
