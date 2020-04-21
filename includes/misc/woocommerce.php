<?php

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

function udesly_woocommerce_featured_image_url($size = 'full')
{
    global $product;
    $main_image_url = wp_get_attachment_image_url($product->get_image_id(), $size);
    $main_image_url = $main_image_url ? $main_image_url : esc_url(wc_placeholder_img_src());

    return $main_image_url;
}

function udesly_wc_cart_cross_sells()
{
    $cross_sells = array_filter(array_map('wc_get_product', WC()->cart->get_cross_sells()), 'wc_products_array_filter_visible');

    $wc_settings = \Udesly\Dashboard\Views\Settings::get_wc_settings();

    $limit = $wc_settings['cart_cross_sells_limit'];

    wc_set_loop_prop('name', 'cross-sells');
    wc_set_loop_prop('columns', apply_filters('woocommerce_cross_sells_columns', '2'));
    // Handle orderby and limit results.
    $orderby = apply_filters('woocommerce_cross_sells_orderby', 'rand');
    $order = apply_filters('woocommerce_cross_sells_order', 'desc');
    $cross_sells = wc_products_array_orderby($cross_sells, $orderby, 'desc');
    $limit = apply_filters('woocommerce_cross_sells_total', $limit);
    $cross_sells = $limit > 0 ? array_slice($cross_sells, 0, $limit) : $cross_sells;

    return $cross_sells;
}

function udesly_wc_get_upsells($columns = 4, $orderby = 'rand', $order = 'desc')
{
    global $product;

    if (!$product) {
        return;
    }

    $wc_settings = \Udesly\Dashboard\Views\Settings::get_wc_settings();

    $limit = $wc_settings['upsells_limit'];

    // Handle the legacy filter which controlled posts per page etc.
    $args = apply_filters('woocommerce_upsell_display_args', array(
        'posts_per_page' => $limit,
        'orderby' => $orderby,
        'columns' => $columns,
    ));

    $orderby = apply_filters('woocommerce_upsells_orderby', isset($args['orderby']) ? $args['orderby'] : $orderby);
    $limit = apply_filters('woocommerce_upsells_total', isset($args['posts_per_page']) ? $args['posts_per_page'] : $limit);

    // Get visible upsells then sort them at random, then limit result set.
    $upsells = wc_products_array_orderby(array_filter(array_map('wc_get_product', $product->get_upsell_ids()), 'wc_products_array_filter_visible'), $orderby, $order);
    $upsells = $limit > 0 ? array_slice($upsells, 0, $limit) : $upsells;

    return $upsells;
}


function udesly_wc_get_related($args = array())
{
    global $product;

    if (!$product) {
        return;
    }

    $wc_settings = \Udesly\Dashboard\Views\Settings::get_wc_settings();

    $limit = $wc_settings['related_limit'];

    $defaults = array(
        'posts_per_page' => $limit,
        'columns' => 2,
        'orderby' => 'rand', // @codingStandardsIgnoreLine.
        'order' => 'desc',
    );

    $args = wp_parse_args($args, $defaults);

    // Get visible related products then sort them at random.
    $args['related_products'] = array_filter(array_map('wc_get_product', wc_get_related_products($product->get_id(), $args['posts_per_page'], $product->get_upsell_ids())), 'wc_products_array_filter_visible');

    // Handle orderby.
    $args['related_products'] = wc_products_array_orderby($args['related_products'], $args['orderby'], $args['order']);

    return $args['related_products'];

}

function udesly_woocommerce_should_products_be_visible()
{
    return woocommerce_products_will_display();
}

function udesly_wc_get_categories($limit = 0, $post_id = null)
{
    if (!$post_id) {
        global $post;
        $post_id = $post->ID; // loop post
    }
    $categories_ids = wp_get_post_terms($post_id, "product_cat", array(
        'number' => $limit
    ));

    // Return categories
    $cats = array();

    foreach ($categories_ids as $c) {
        $cat = get_term($c);
        $cats[] = (object)array('name' => $cat->name, 'link' => get_term_link($cat));
    }

    return $cats;
}

/**
 * Gets all tags for current post
 * @param integer $limit number of tags
 * @param int $post_id the post
 * @return object[]
 */
function udesly_wc_get_tags($limit = 0, $post_id = null)
{
    if (!$post_id) {
        global $post;
        $post_id = $post->ID; // loop post
    }
    $categories_ids = wp_get_post_terms($post_id, "product_tag", array(
        'number' => $limit
    ));

    // Return categories
    $cats = array();

    foreach ($categories_ids as $c) {
        $cat = get_term($c);
        $cats[] = (object)array('name' => $cat->name, 'link' => get_term_link($cat));
    }

    return $cats;
}

function udesly_woocommerce_result_count()
{

    if (!wc_get_loop_prop('is_paginated') || !woocommerce_products_will_display()) {
        return;
    }

    $total = wc_get_loop_prop('total');
    $per_page = wc_get_loop_prop('per_page');
    $current = wc_get_loop_prop('current_page');


    if ($total <= $per_page || -1 === $per_page) {
        /* translators: %d: total results */
        printf(_n('Showing the single result', 'Showing all %d results', $total, 'woocommerce'), $total);
    } else {
        $first = ($per_page * $current) - $per_page + 1;
        $last = min($total, $per_page * $current);
        /* translators: 1: first result 2: last result 3: total results */
        printf(_nx('Showing the single result', 'Showing %1$d&ndash;%2$d of %3$d results', $total, 'with first and last result', 'woocommerce'), $first, $last, $total);
    }
}

function udesly_woocommerce_orderby_options()
{
    if (!wc_get_loop_prop('is_paginated') || !woocommerce_products_will_display()) {
        return;
    }
    $show_default_orderby = 'menu_order' === apply_filters('woocommerce_default_catalog_orderby', get_option('woocommerce_default_catalog_orderby'));
    $catalog_orderby_options = apply_filters('woocommerce_catalog_orderby', array(
        'menu_order' => __('Default sorting', 'woocommerce'),
        'popularity' => __('Sort by popularity', 'woocommerce'),
        'rating' => __('Sort by average rating', 'woocommerce'),
        'date' => __('Sort by newness', 'woocommerce'),
        'price' => __('Sort by price: low to high', 'woocommerce'),
        'price-desc' => __('Sort by price: high to low', 'woocommerce'),
    ));
    $default_orderby = wc_get_loop_prop('is_search') ? 'relevance' : apply_filters('woocommerce_default_catalog_orderby', get_option('woocommerce_default_catalog_orderby', ''));
    $orderby = isset($_GET['orderby']) ? wc_clean(wp_unslash($_GET['orderby'])) : $default_orderby; // WPCS: sanitization ok, input var ok, CSRF ok.
    if (wc_get_loop_prop('is_search')) {
        $catalog_orderby_options = array_merge(array('relevance' => __('Relevance', 'woocommerce')), $catalog_orderby_options);
        unset($catalog_orderby_options['menu_order']);
    }
    if (!$show_default_orderby) {
        unset($catalog_orderby_options['menu_order']);
    }
    if ('no' === get_option('woocommerce_enable_review_rating')) {
        unset($catalog_orderby_options['rating']);
    }
    if (!array_key_exists($orderby, $catalog_orderby_options)) {
        $orderby = current(array_keys($catalog_orderby_options));
    }
    foreach ($catalog_orderby_options as $id => $name) : ?>
        <option value="<?php echo esc_attr($id); ?>" <?php selected($orderby, $id); ?>><?php echo esc_html($name); ?></option>
    <?php endforeach;

}

function udesly_woocommerce_get_cart_items()
{

    $cart_items = array();

    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
        if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key)) {
            $current_product = array();
            $current_product['title'] = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key);

            $main_image_url = wp_get_attachment_image_url($_product->get_image_id(), 'full');
            $main_image_url = $main_image_url ? $main_image_url : esc_url(wc_placeholder_img_src());
            $current_product['image'] = $main_image_url;

            $product_price = apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key);
            $current_product['permalink'] = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
            $current_product['remove'] = wc_get_cart_remove_url($cart_item_key);
            $current_product['quantity'] = $cart_item['quantity'];
            $current_product['class'] = '';
            $current_product['price'] = $product_price;
            $current_product['total'] = wc_price($cart_item['line_total'] + $cart_item['line_tax']);
            $current_product['key'] = $cart_item_key;
            $cart_items[] = (object)$current_product;
        }
    }

    return $cart_items;
}

/**
 * For multi pages with different IDs
 *
 * @param $template_id
 */
function udesly_wc_single_product_add_to_cart($template_id)
{
    global $product;

    $type = $product->get_type();

    switch ($type) {
        case "simple":
        case "variable":
        case "grouped":
        case "external":
            $template_path = trailingslashit(get_template_directory()) . "template-parts/add-to-cart-$type-$template_id.php";
            if (!file_exists($template_path)) {
                do_action("woocommerce_$type" . '_add_to_cart');
            } else {
                include $template_path;
            }
            break;
        default:
            do_action("woocommerce_$type" . '_add_to_cart');
    }

}

function udesly_wc_get_variations($attributes)
{
    global $product;
    $results = array();

    $placeholder = wc_placeholder_img_src();

    foreach ($attributes as $attribute_name => $options) {

        $values = (object)array(
            "for" => esc_attr(sanitize_title($attribute_name)),
            "label" => wc_attribute_label($attribute_name),
            "options" => array()
        );

        $name = 'attribute_' . sanitize_title($attribute_name);

        $selected_key = 'attribute_' . sanitize_title($attribute_name);
        $selected = isset($_REQUEST[$selected_key]) ? wc_clean(wp_unslash($_REQUEST[$selected_key])) : $product->get_variation_default_attribute($attribute_name);
        ?>
        <div style="display: none;">
            <?php wc_dropdown_variation_attribute_options(array(
                'options' => $options,
                'attribute' => $attribute_name,
                'product' => $product,
            ));
            ?>
        </div>
        <?php


        if (!empty($options)) {
            if ($product && taxonomy_exists($attribute_name)) {

                $terms = wc_get_product_terms($product->get_id(), $attribute_name, array(
                    'fields' => 'all',
                ));

                foreach ($terms as $term) {
                    if (in_array($term->slug, $options, true)) {

                        $image = udesly_get_term_featured_image($term->term_id, 'full', true);

                        if ($image != $placeholder) {
                            $image = "background-image: url($image);";
                        } else {
                            $image = "";
                        }

                        $values->options[] = (object)array(
                            "name" => esc_attr($name),
                            "value" => esc_attr($term->slug),
                            "checked" => sanitize_title($selected) === $term->slug ? "checked" : "",
                            "for" => strtolower($term->slug),
                            "label" => esc_html(apply_filters('woocommerce_variation_option_name', $term->name)),
                            "id" => strtolower($term->slug),
                            "image" => $image,
                        );
                        // echo '<input type="radio" name="'.esc_attr($name).'" value="'.esc_attr($term->slug).'" '.checked(sanitize_title($selected), $term->slug, false).'><label for="'.esc_attr($term->slug).'">'.esc_html(apply_filters('woocommerce_variation_option_name', $term->name)).'</label>';
                    }
                }
            } else {
                foreach ($options as $option) {
                    $values->options[] = (object)array(
                        "name" => esc_attr($name),
                        "value" => esc_attr($option),
                        "checked" => sanitize_title($selected) === $selected ? checked($selected, sanitize_title($option), false) : checked($selected, $option, false),
                        "for" => strtolower($option),
                        "label" => esc_html(apply_filters('woocommerce_variation_option_name', $option)),
                        "id" => strtolower($option),
                        "image" => ""
                    );

                }
            }
        }

        $results[$attribute_name] = $values;
    }
    return $results;
}

function udesly_wc_get_single_product_images()
{
    global $product;

    if ($cache = wp_cache_get("wc_product_images")) {
        return $cache;
    }

    $attachment_ids = $product->get_gallery_image_ids();
    $images = [];
    if ($product->get_image_id()) {
        $images[$product->get_image_id()] = udesly_wc_get_single_product_image($product->get_image_id());

    }

    if ($attachment_ids) {
        foreach ($attachment_ids as $attachment_id) {
            $images[$attachment_id] = udesly_wc_get_single_product_image($attachment_id);
        }
    }

    if ($product->get_type() === 'variable') {

    $variations = $product->get_available_variations();
    foreach ($variations as $variation) {
        $id = $variation['image_id'];
        if (!isset($images[$id])) {
            $images[$id] = udesly_wc_get_single_product_image($id);
        }
    }
 }

    wp_cache_set("wc_product_images", $images);
    return $images;
}

function udesly_quantity_input_cart_item($_product, $cart_item_key, $cart_item, $classes) {
    if ( $_product->is_sold_individually() ) {
        $product_quantity = sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key );
    } else {
        $product_quantity = woocommerce_quantity_input(
            array(
                'input_name'   => "cart[{$cart_item_key}][qty]",
                'input_value'  => $cart_item['quantity'],
                'max_value'    => $_product->get_max_purchase_quantity(),
                'min_value'    => '0',
                'product_name' => $_product->get_name(),
                'classes' => $classes
            ),
            $_product,
            false
        );
    }

    echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item ); // PHPCS: XSS ok.
}

function udesly_wc_get_single_product_image_lightbox_json($attachment, $product_id)
{
    if (is_numeric($attachment)) {
        $image = udesly_wc_get_single_product_image($attachment);
    } else {
        $image = $attachment;
    }
    $result = [];
    $result[] = array(
        "caption" => $image->caption,
        "url" => $image->full_src,
        "type" => "image",
    );

    return json_encode(array(
        "items" => $result,
        "group" => "Product $product_id images"
    ));
}

function udesly_wc_get_single_product_images_lightbox_json()
{
    $images = udesly_wc_get_single_product_images();
    $result = [];

    foreach ($images as $image) {
        $result[] = array(
            "caption" => $image->caption,
            "url" => $image->full_src,
            "type" => "image",
        );
    }

    return json_encode(array(
        "items" => $result
    ));
}

function udesly_wc_get_featured_image_lightbox_json()
{
    $result = [];

    $image = get_the_post_thumbnail_url('full');
    $result[] = array(
        "caption" => get_the_post_thumbnail_caption(),
        "url" => $image ? $image : wc_placeholder_img_src('full'),
        "type" => "image",
    );

    return json_encode(array(
        "items" => $result
    ));
}

function udesly_wc_get_single_product_image($attachment_id, $main_image = false)
{

    $flexslider = (bool)apply_filters('woocommerce_single_product_flexslider_enabled', get_theme_support('wc-product-gallery-slider'));

    $gallery_thumbnail = wc_get_image_size('gallery_thumbnail');
    $thumbnail_size = apply_filters('woocommerce_gallery_thumbnail_size', array($gallery_thumbnail['width'], $gallery_thumbnail['height']));
    $image_size = apply_filters('woocommerce_gallery_image_size', $flexslider || $main_image ? 'woocommerce_single' : $thumbnail_size);
    $full_size = apply_filters('woocommerce_gallery_full_size', apply_filters('woocommerce_product_thumbnails_large_size', 'full'));
    $metadata = wp_get_attachment_metadata($attachment_id);
    $caption = $metadata['image_meta']['caption'];

    return (object)array(
        "src" => wp_get_attachment_image_src(
            $attachment_id,
            $image_size,
            false
        )[0],
        "sizes" => wp_get_attachment_image_sizes($attachment_id, $image_size),
        "srcset" => wp_get_attachment_image_srcset($attachment_id, $image_size),
        "thumb_src" => wp_get_attachment_image_src($attachment_id, $thumbnail_size)[0],
        "full_src" => wp_get_attachment_image_src($attachment_id, $full_size)[0],
        "alt" => trim(wp_strip_all_tags(get_post_meta($attachment_id, '_wp_attachment_image_alt', true))),
        "caption" => $caption
    );
}

function udesly_wc_breadcrumb($args = array())
{
    $args = wp_parse_args($args, apply_filters('woocommerce_breadcrumb_defaults', array(
        'delimiter' => '&nbsp;&#47;&nbsp;',
        'wrap_before' => '<nav class="woocommerce-breadcrumb">',
        'wrap_after' => '</nav>',
        'before' => '',
        'after' => '',
        'home' => _x('Shop', 'breadcrumb', 'woocommerce'),
    )));
    $breadcrumbs = new WC_Breadcrumb();
    if (!empty($args['home']) && !is_shop()) {
        $breadcrumbs->add_crumb($args['home'], get_permalink(wc_get_page_id('shop')));
    }
    $breadcrumb = $breadcrumbs->generate();
    $result = array();
    do_action('woocommerce_breadcrumb', $breadcrumbs, $args);

    if (!empty($breadcrumb)) {
        foreach ($breadcrumb as $key => $crumb) {

            if (!empty($crumb[1]) && sizeof($breadcrumb) !== $key + 1) {
                array_push($result, (object)array(
                    'name' => esc_html($crumb[0]),
                    'href' => esc_url($crumb[1]),
                    'type' => 'category'
                ));
            } else {
                $result[] = (object)array('name' => $crumb[0], 'type' => 'current', 'href' => '#');
            }

            if (sizeof($breadcrumb) !== $key + 1) {
                array_push($result, (object)array(
                    'type' => 'separator'
                ));
            }
        }

    }

    return $result;
}

function udesly_wc_single_product_images_script()
{
    echo "window.udeslyWcImages = " . json_encode(udesly_wc_get_single_product_images());
}

function udesly_wc_open_single_tab($key)
{
    ?>
    <div class="woocommerce-Tabs-panel woocommerce-Tabs-panel--<?php echo esc_attr($key); ?> panel entry-content wc-tab" id="tab-<?php echo esc_attr($key); ?>" role="tabpanel" aria-labelledby="tab-title-<?php echo esc_attr($key); ?>">
    <?php
}

function udesly_wc_close_single_tab()
{
    ?>
    </div>
    <?php
}


function udesly_wc_get_cart_items()
{
    $items = [];
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $item = [];
        $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
        $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
        if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) {
            $item['permalink'] = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
        } else {
            $item['permalink'] = "#";
        }
        $item['remove'] = esc_url(wc_get_cart_remove_url($cart_item_key));
        $item['id'] = esc_attr($product_id);
        $item['sku'] = esc_attr($_product->get_sku());
        $main_image_url = wp_get_attachment_image_url($_product->get_image_id(), apply_filters('udesly_cart_image_size', 'medium'));
        $main_image_url = $main_image_url ? $main_image_url : esc_url(wc_placeholder_img_src());
        $item['image'] = $main_image_url;
        $item['title'] = $_product->get_name();
        $item['price'] = apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key);
        $max_value = $_product->get_max_purchase_quantity();
        if ($max_value < 0) {
            $max_value = "";
        }
        $item['quantity'] = (object)array(
            'sold_individually' => $_product->is_sold_individually(),
            'input_name' => "cart[{$cart_item_key}][qty]",
            'input_value' => $cart_item['quantity'],
            'max_value' => $max_value,
            'min_value' => '0'
        );
        $item['subtotal'] = apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); // PHPCS: XSS ok.
        $item['variation'] = wc_get_formatted_cart_item_data($cart_item, true);

        $items[] = (object)$item;
    }
    return $items;
}

function udesly_wc_webflow_checkout($classes = '{}')
{
    define('UDESLY_WOO_WEBFLOW_CHECKOUT', true);

    $classes = wp_parse_args(json_decode($classes), array(
        'h' => '',
        'i' => '',
        'w' => '',
        'c' => '',
        'c_w' => '',
        'l' => '',
        'o' => '',
        'm' => '',
        's' => '',
        'b' => '',
        'header' => 'h4',
        'header_c' => '',
        'l_i' => '',
    ));

    global $udesly_checkout_classes;
    $udesly_checkout_classes = $classes;

    WC()->session->set('udesly_checkout_classes', $classes);

    echo do_shortcode('[woocommerce_checkout]');

}


function udesly_wc_alternative_template($located, $template_name, $args, $template_path, $default_path)
{

    global $udesly_checkout_classes;

    if (!isset($udesly_checkout_classes) && WC()->session != null) {
        $udesly_checkout_classes = WC()->session->get('udesly_checkout_classes');
    }

    if (is_ajax() && udesly_string_starts_with($template_name, 'checkout/')) {
        if (file_exists(UDESLY_ADAPTER_PLUGIN_DIRECTORY_PATH . 'templates/woocommerce/' . str_replace('/', '/new-', $template_name))) {
            return UDESLY_ADAPTER_PLUGIN_DIRECTORY_PATH . 'templates/woocommerce/' . str_replace('/', '/new-', $template_name);
        }
    }

    if (defined('UDESLY_WOO_WEBFLOW_CHECKOUT') && UDESLY_WOO_WEBFLOW_CHECKOUT === true) {

        if (file_exists(UDESLY_ADAPTER_PLUGIN_DIRECTORY_PATH . 'templates/woocommerce/' . str_replace('/', '/new-', $template_name))) {
            return UDESLY_ADAPTER_PLUGIN_DIRECTORY_PATH . 'templates/woocommerce/' . str_replace('/', '/new-', $template_name);
        }
    }

    return $located;
}

function udesly_wc_alter_input_fields($args, $key, $value)
{
    global $udesly_checkout_classes;
    if ($udesly_checkout_classes) {
        $args['label_class'] = explode(' ', $udesly_checkout_classes['l']);
        $args['input_class'] = $args['type'] === 'country' ? explode(' ', $udesly_checkout_classes['o']) : explode(' ', $udesly_checkout_classes['i']);
        return $args;
    } else {
        return $args;
    }
}


function udesly_wc_get_product_variations()
{
    global $product;

    if ($product->is_type('variable')) {
        $attributes = $product->get_variation_attributes();
        if ($attributes) {
            return udesly_wc_get_variations($attributes);
        }
    }

    return [];

}