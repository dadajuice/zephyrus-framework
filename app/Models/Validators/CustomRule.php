<?php namespace Models\Validators;

use Zephyrus\Application\Rule;

class CustomRule
{
    public static function userAvailable(string $errorMessage = "")
    {
        return new Rule(function($data) {
            /**
             * Example of custom rule that could verify user availability
             * from Database.
             */
            return !in_array($data, ['blewis', 'dtucker', 'admin', 'root']);
        }, $errorMessage);
    }
}
