<?php
 /**Misc function for pagination*/

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

/**
 * return current page number
 *
 * @return int|mixed
 */
 function udesly_get_current_page_number() {
     $paged = (get_query_var("paged")) ? get_query_var("paged") : 1;
     return $paged;
 }

/**
 *
 * return max pages number
 *
 * @return int
 */
 function udesly_get_max_pages_number(){
     global $wp_query;
     return $wp_query -> max_num_pages;
 }

/**
 *
 * return post number
 *
 * @return int
 */
 function udesly_get_posts_number() {
     global $wp_query;
     return $wp_query -> found_posts;
 }

function udesly_next_posts_link() {
    global $paged, $wp_query;

    $max_page = $wp_query->max_num_pages;

    if ( ! $paged ) {
        $paged = 1;
    }

    $nextpage = intval( $paged ) + 1;


    if ( ! is_single() && ( $nextpage <= $max_page ) ) {

        return next_posts( $max_page, false );
    }
}

function udesly_previous_posts_link() {
    global $paged;

    if ( ! is_single() && $paged > 1 ) {
        return previous_posts( false );
    }
}


function udesly_wp_link_page( $i ) {
    global $wp_rewrite;
    $post = get_post();

    if ( 1 == $i ) {
        $url = get_permalink();
    } else {
        if ( '' == get_option('permalink_structure') || in_array($post->post_status, array('draft', 'pending')) )
            $url = add_query_arg( 'page', $i, get_permalink() );
        elseif ( 'page' == get_option('show_on_front') && get_option('page_on_front') == $post->ID )
            $url = trailingslashit(get_permalink()) . user_trailingslashit("$wp_rewrite->pagination_base/" . $i, 'single_paged');
        else
            $url = trailingslashit(get_permalink()) . user_trailingslashit($i, 'single_paged');
    }

    return esc_url( $url );
}