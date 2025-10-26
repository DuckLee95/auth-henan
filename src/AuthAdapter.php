<?php


use App\Rules;
use Blessing\Filter;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;

interface AuthAdapter
{
    public function login(
        Filter $filter,
         string $msg
        );
    public function handleLogin(
        Request $request,
        Rules\Captcha $captcha,
        Dispatcher $dispatcher,
        Filter $filter
    );
    public function handleRegister(
        Request $request,
        Rules\Captcha $captcha,
        Dispatcher $dispatcher,
        Filter $filter
    );
}