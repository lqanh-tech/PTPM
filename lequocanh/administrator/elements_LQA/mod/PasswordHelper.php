<?php

class PasswordHelper
{

    public static function hash($password)
    {

        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verify($password, $hash)
    {
        return password_verify($password, $hash);
    }

    public static function needsRehash($hash)
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function isPlainText($password)
    {

        return !preg_match('/^\$2[ayb]\$.{56}$/', $password);
    }
}
