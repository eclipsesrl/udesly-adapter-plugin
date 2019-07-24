<?php

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

/**
 * @param $name
 * @return WP_Query
 */
function udesly_get_content_query( $name )
{

    $query = \Udesly\Query\PostsQueryBuilder::get_query( $name );
    return $query->get_wp_query();
}

/***
 * Returns true if the query has one more page
 *
 * @param $name : query name
 * @param $curr_page
 * @return bool
 */
function udesly_query_has_more_pages( $name, $curr_page = 1 ) {
    $query = \Udesly\Query\PostsQueryBuilder::get_query( $name );
    $query = $query->get_wp_query();

    return $query->max_num_pages > $curr_page;
}