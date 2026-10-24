<?php

if (!function_exists('log_message')) {
    function log_message($level, $message) {}
}
if (!function_exists('show_error')) {
    function show_error($message)
    {
        throw new RuntimeException(is_array($message) ? implode('; ', $message) : $message);
    }
}
if (!function_exists('is_php')) {
    function is_php($version)
    {
        return version_compare(PHP_VERSION, $version, '>=');
    }
}
