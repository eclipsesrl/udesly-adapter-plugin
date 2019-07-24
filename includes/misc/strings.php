<?php

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

function udesly_string_strip_subdirectory_dots($string) {
    return str_replace('../', '', $string);
}

function udesly_string_is_absolute($string) {
    return udesly_string_starts_with($string, 'http') ||  udesly_string_starts_with($string, 'mail') ||  udesly_string_starts_with($string, 'javascript');
}

function udesly_string_starts_with($haystack, $needle)
{
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}

function udesly_get_string_between( $start, $end, $string ) {
    $string = ' ' . $string;
    $ini    = strpos( $string, $start );
    if ( $ini == 0 ) {
        return '';
    }
    $ini += strlen( $start );
    $len = strpos( $string, $end, $ini ) - $ini;

    return substr( $string, $ini, $len );
}