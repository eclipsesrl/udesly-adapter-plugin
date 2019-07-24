<?php

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

function udesly_has_dynamic_box(){
    $post_id = get_the_ID();

    if(!$post_id)
        return false;

    $post_meta = get_post_meta($post_id, '_udesly_dynamic_box', true);

    if(!empty($post_meta)){
        return true;
    }

    return false;
}

function udesly_get_boxes_slider($slug){

    $post_meta = '';

    if($slug == 'dynamic-box'){
        $post_id = get_the_ID();

        if(!$post_id)
            return array();

        $post_meta = get_post_meta($post_id, '_udesly_dynamic_box', true);
    }else{
        $args = array(
            'name'        => $slug,
            'post_type'   => 'udesly_box',
            'post_status' => 'publish',
            'numberposts' => 1
        );
        $my_posts = get_posts($args);
        if( $my_posts ) {
            $post_meta = $my_posts[0]->post_content;
        }else{
            return array();
        }
    }

    $attrs = udesly_get_string_between( '[gallery', ']', $post_meta);
    if ($attrs) {
        $attr_ids = udesly_get_string_between( 'ids="', '"', $attrs);
        $images_ids = explode(',',$attr_ids);

        $images = array();

        foreach ($images_ids as $id){
            $image = wp_get_attachment_image_src($id,'full');
            if($image){
                $images[] = $image[0];
            }
        }

        return $images;
    } else {
        // guthenberg block
        $images = array();
        preg_match_all('/src="([^"]+)"/', $post_meta, $images);
       if (isset($images[1])) {
           return $images[1];
       } else {
           return array();
       }

    }

}


function __udesly_boxes_get_images( $slug ) {
    $post_meta = '';

    if($slug == 'dynamic-box'){
        $post_id = get_the_ID();

        if(!$post_id)
            return array();

        $post_meta = get_post_meta($post_id, '_udesly_dynamic_box', true);
    }else{
        $args = array(
            'name'        => $slug,
            'post_type'   => 'udesly_box',
            'post_status' => 'publish',
            'numberposts' => 1
        );
        $my_posts = get_posts($args);
        if( $my_posts ) {
            $post_meta = $my_posts[0]->post_content;
        }else{
            return array();
        }
    }

    $attrs = udesly_get_string_between( '[gallery', ']', $post_meta);
    if ($attrs) {
        $attr_ids = udesly_get_string_between( 'ids="', '"', $attrs);
        $images_ids = explode(',',$attr_ids);

        $images = array();

        foreach ($images_ids as $id){
            $image = wp_get_attachment_image_src($id,'full');
            $alt = get_post_meta( $id, '_wp_attachment_image_alt', true );
            $caption = wp_get_attachment_caption($id);
            if($image){
                $images[] = (object) array(
                    "caption" => $caption ? $caption : "",
                    "src" => $image[0],
                    "alt" => $alt
                );
            }
        }

        return $images;
    } else {
        // guthenberg block
        $images = array();
        $alts = array();
        preg_match_all('/src="([^"]+)"/', $post_meta, $images);
        preg_match_all('/alt="([^"]+)"/', $post_meta, $alts);
        if (isset($images[1])) {
            $res = array();
            foreach ($images[1] as $key => $image) {
                $alt = "";
                if (isset($alts[1]) && isset($alts[1][$key])) {
                    $alt = $alts[1][$key];
                }
                $res[] = (object) array(
                    "caption" => "",
                    "src" => $image,
                    "alt" => $alt,
                );
            }
            return $res;
        } else {
            return array();
        }

    }
}

/**
 * Used for each image in a multi lightbox environment
 * @param $image
 * @param $slug
 *
 * @return string
 */
function udesly_boxes_get_image_lightbox_json($image, $slug) {
    $result = [];
    $result[] = array(
        "caption" => $image->caption,
        "url" => $image->src,
        "type" => "image",
    );

    return json_encode(array(
        "items" => $result,
        "group" => "$slug images"
    ));
}

/**
 * Gets all images from a box
 * @param $box_slug
 *
 * @return array of Object
 */
function udesly_boxes_get_lightbox_images( $box_slug ) {
    return __udesly_boxes_get_images($box_slug);
}

/**
 * Gets all images from a box as a lightbox script
 *
 * @param $box_slug
 * @param $id
 *
 * @return string
 */
function udesly_boxes_get_images_lightbox_json( $box_slug, $id) {
    $images =  __udesly_boxes_get_images($box_slug);

    $result = [];
    foreach ($images as $image) {
        $result[] = array(
            "caption" => $image->caption,
            "url" => $image->src,
            "type" => "image",
        );


    }
    return json_encode(array(
        "items" => $result,
        "group" => "$id images"
    ));
}