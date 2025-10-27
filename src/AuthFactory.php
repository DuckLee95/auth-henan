<?php
namespace Blessing\HAuth;

class AuthFactory
{
    public static function create(string $school, string $username, string $password)
    {
        switch ($school) {
            case 'ncwu':
                return new AuthNcwu($username, $password);
            default:
                throw new \InvalidArgumentException("Unsupported school: $school");
        }
    }
}