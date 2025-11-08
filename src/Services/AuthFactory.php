<?php
namespace Blessing\HAuth\Services;
class AuthFactory
{
    public static function create(string $school, string $username, string $password)
    {
        switch ($school) {
            case 'ncwu':
                return new AuthNcwu($username, $password);
            case 'haust':
                return new AuthHaust($username, $password);
            case 'zzu':
                return new AuthZzu($username, $password);
            case 'lit':
                return new AuthLit($username, $password);
            case 'zut':
                return new AuthZut($username, $password);
            case 'htu':
                return new AuthHtu($username, $password);
            default:
                throw new \InvalidArgumentException("Unsupported school: $school");
        }
    }
}