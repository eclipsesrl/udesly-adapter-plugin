<?php
/*
 *
 * Social Related functions
 */

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

/**
 *
 * Gets social share url
 *
 * @param $social
 *
 * @return string
 */
function udesly_get_social_share_url($social) {
    global $post;
    $permalink = get_permalink();

    switch ( $social ) {
        case 'facebook':
            return add_query_arg( 'u', $permalink, 'https://www.facebook.com/sharer.php' );
            break;
        case 'twitter':
            return add_query_arg( array(
                'url'  => $permalink,
                'text' => $post->post_title
            ), 'https://twitter.com/intent/tweet' );
            break;
        case 'linkedin':
            return add_query_arg( array(
                'mini'    => true,
                'url'     => $permalink,
                'title'   => substr( $post->post_title, 0, 200 ),
                'summary' => esc_html(substr( $post->post_excerpt, 0, 256 )),
            ), 'https://www.linkedin.com/shareArticle' );
            break;
        case 'reddit':
            return add_query_arg( array(
                'url'  => $permalink,
                'text' => $post->post_title
            ), 'https://reddit.com/submit' );
            break;
        case 'pinterest':
            return add_query_arg( 'url', $permalink, 'http://pinterest.com/pin/create/link/' );
            break;
        case 'pocket':
            return add_query_arg( 'url', $permalink, 'https://getpocket.com/edit' );
            break;
        case 'vk':
            return add_query_arg( array(
                'url'   => $permalink,
                'title' => $post->post_title
            ), 'http://vk.com/share.php' );
            break;
        case 'skype':
            return add_query_arg( array(
                'url'  => $permalink,
                'text' => $post->post_title
            ), 'https://web.skype.com/share' );
            break;
        case 'telegram':
            return add_query_arg( array(
                'url'  => $permalink,
                'text' => $post->post_title
            ), 'https://t.me/share/url' );
            break;
        case 'email':
            return add_query_arg( array(
                'body'  =>  esc_html(substr( $post->post_excerpt, 0, 256 ) . '\n'. __('Read more on:') . ' ' . $permalink),
                'subject' => $post->post_title
            ), 'mailto:' );
            break;
        case 'weibo':
            return add_query_arg( array(
                'url'   => $permalink,
                'title' => $post->post_title
            ), 'http://service.weibo.com/share/share.php' );
            break;
        default:
            return '';
    }
}