<?php

/**
 *
 * @version 1.0
 * @author Paul
 */
class Config
{
    public static function get_database_host() {
         return "127.0.0.1";
    }

    public static function get_database_name() {
        return "dbname";
    }

    public static function get_database_user() {
        return "dbusername";
    }

    public static function get_database_password() {
        return 'dbpassword'; // use single quotes in case the password has a dollar sign in it.
    }

}