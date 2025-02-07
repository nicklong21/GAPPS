<?php
namespace ElevenFingersCore\GAPPS\Accounts;

class UserRegistry{
    private array $registry = [
        'User'=>[
            'userclass'=>User::class,
            'access'=>0,
        ],
        'Administrator'=>[
            'userclass'=>User::class,
            'access'=>7,
            'zgroup'=>'admin'
        ]
    ];

    public function getDependencies(string $usertype):array{
        $a_dependencies = self::$registry[$usertype] ?? self::$registry['User'];
        $dependencies = array_merge(self::$registry['User'],$a_dependencies);
        return $dependencies;
    }
}