<?php

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

/**
 * @param $name
 * @return WP_Term_Query
 */
function udesly_get_list_query( $name ) {

    $query = \Udesly\Query\TermsQueryBuilder::get_query( $name );
    return $query->get_wp_query();
}


function udesly_get_list_query_results($name) {
    $query = udesly_get_list_query($name);

    $results = [];

    foreach ($query->terms as $term) {
        $results[] = (object)array(
            "name" => $term->name,
            "permalink" => get_term_link($term->term_id),
            "description" => $term->description,
            "term_id" => $term->term_id,
            "count" => $term->count
        );
    }
    return $results;
}