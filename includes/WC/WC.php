<?php

namespace Udesly\WC;

use Udesly\Dashboard\Views\Settings;
use Udesly\Query\PostsQueryBuilder;

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

class WC
{
    public static function public_hooks()
    {
        add_action('init', array(self::class, 'remove_actions'));
        add_filter('woocommerce_enqueue_styles', array(self::class, 'remove_styles'));
        add_action('wp_ajax_udesly_wc_add_to_cart', array(self::class, 'udesly_wc_add_to_cart'));
        add_action('wp_ajax_nopriv_udesly_wc_add_to_cart', array(self::class, 'udesly_wc_add_to_cart'));
        add_filter('woocommerce_add_to_cart_fragments', array(self::class, 'add_mini_cart_fragments'));
        add_action('wp_footer', array(self::class, 'add_woocommerce_mini_cart_elements'));
        add_action('wp_ajax_udesly_wc_remove_from_cart', array(self::class, 'udesly_wc_remove_from_cart'));
        add_action('wp_ajax_nopriv_udesly_wc_remove_from_cart', array(self::class, 'udesly_wc_remove_from_cart'));
        add_action('wp_ajax_udesly_get_products', array(self::class, "udesly_get_products"));
        add_action('wp_ajax_nopriv_udesly_get_products', array(self::class, "udesly_get_products"));
        add_filter('wc_get_template', 'udesly_wc_alternative_template', 10, 5);
        add_filter('woocommerce_form_field_args', 'udesly_wc_alter_input_fields', 10, 3);
        add_action('wp_ajax_udesly_wc_get_notices', array(self::class, "udesly_wc_get_notices"));
        add_action('wp_ajax_nopriv_udesly_wc_get_notices', array(self::class, "udesly_wc_get_notices"));
        add_filter('woocommerce_gallery_image_size', array(self::class, "product_images_size"), 99);

        add_action('woocommerce_before_cart', "udesly_wc_before_cart");
        add_filter('template_include', [self::class, 'udesly_redirect_order_confirmation'], 99);
    }

    public static function udesly_redirect_order_confirmation($template)
    {
        global $wp;
        if (is_checkout() && !empty($wp->query_vars['order-received'])) {

            if (file_exists(get_template_directory() . '/wc-order-confirmation.php')) {
                global $wc_order;
                $wc_order = wc_get_order($wp->query_vars['order-received']);
                if ($wc_order  && $wc_order->get_user_id() == get_current_user_id()) {
                    return get_template_directory() . '/wc-order-confirmation.php';
                } else {
                    wp_redirect(wc_get_checkout_url());
                    exit;
                }
            }

        }
        return $template;
    }


    public static function udesly_wc_get_notices()
    {
        if (!self::verify_nonce()) {
            wp_send_json_error(array(
                "code" => 403,
                "message" => "failed security check"
            ), 403);
            wp_die();
        }


        $notices = wc_print_notices(true);

        wp_send_json_success(array(
            "notices" => $notices,
        ));
        wp_die();
    }

    public static function verify_nonce() {
        $settings = Settings::get_wc_settings();
        $check = $settings['nonce_check'];

        if ($check == "disable") {
            return true;
        }
        if ($check == "exclude_guests" && !is_user_logged_in()) {
            return true;
        };

        if ($check == "exclude_logged_in" && is_user_logged_in()) {
            return true;
        }

        if (isset($_POST['security'])) {

            return wp_verify_nonce($_POST['security'], 'udesly-ajax-action');
        }
        return false;
    }

    public static function udesly_get_products()
    {
        if (!self::verify_nonce()) {
            wp_send_json_error(array(
                "code" => 403,
                "message" => "failed security check"
            ), 403);
            wp_die();
        }
        $query_name = sanitize_title($_POST['name']);
        $query_template = sanitize_text_field($_POST['template']);

        $page = (int)sanitize_text_field($_POST['page']);

        $query = PostsQueryBuilder::get_query($query_name);

        if ("invalid_query" === $query->name) {
            wp_send_json_error(array("message" => "Invalid query name"), 400);
            wp_die();
        }

        $template_path = trailingslashit(get_template_directory()) . "template-parts/$query_template.php";

        if (!file_exists($template_path)) {
            wp_send_json_error(array("message" => "Template doesn't exists"), 400);
            wp_die();
        }

        $query->set_page($page);
        $wp_query = $query->get_wp_query();
        if ($wp_query->have_posts()) {
            ob_start();
            while ($wp_query->have_posts()) {
                $wp_query->the_post();
                global $post, $product;
                if (!empty($product) && $product->is_visible()) {
                    include $template_path;
                }

            }
            $posts = ob_get_clean();
            wp_send_json_success(array(
                "posts" => $posts
            ));
            wp_die();
        } else {
            wp_send_json_success(array(
                "posts" => ""
            ));
            wp_die();
        }


    }

    public static function product_images_size()
    {
        $wc = Settings::get_wc_settings();
        return $wc['product_images_size'];
    }

    public static function remove_styles($enqueue_styles)
    {
        $wc = Settings::get_wc_settings();
        if (!$wc['general_styles']) {
            unset($enqueue_styles['woocommerce-general']); // Remove the gloss
        }
        if (!$wc['layout_styles']) {
            unset($enqueue_styles['woocommerce-layout']);        // Remove the layout
        }
        if (!$wc['smallscreen_styles']) {
            unset($enqueue_styles['woocommerce-smallscreen']);    // Remove the smallscreen optimisation
        }

        return $enqueue_styles;
    }

    public static function add_woocommerce_mini_cart_elements()
    {
        if ( is_null(WC()->cart)) {
            return;
        }
        ?>
        <div id="udesly-wc-mini-cart-elements" style="display: none;">
            <div id="udesly-wc-mini-cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></div>
            <div id="udesly-wc-mini-cart-subtotal"><?php echo WC()->cart->get_cart_subtotal(); ?></div>
            <div id="udesly-wc-mini-cart-items"><?php echo json_encode(udesly_woocommerce_get_cart_items()); ?></div>
        </div>
        <?php
    }

    public static function add_mini_cart_fragments($fragments)
    {
        ob_start();
        ?>
        <div id="udesly-wc-mini-cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></div>
        <?php
        $fragments['#udesly-wc-mini-cart-count'] = ob_get_clean();

        ob_start();
        ?>
        <div id="udesly-wc-mini-cart-subtotal"><?php echo WC()->cart->get_cart_subtotal(); ?></div>
        <?php
        $fragments['#udesly-wc-mini-cart-subtotal'] = ob_get_clean();

        ob_start();
        ?>
        <div id="udesly-wc-mini-cart-items"><?php echo json_encode(udesly_woocommerce_get_cart_items()); ?></div>
        <?php
        $fragments['#udesly-wc-mini-cart-items'] = ob_get_clean();
        return $fragments;
    }

    public static function udesly_wc_remove_from_cart()
    {
        if (!self::verify_nonce()) {
            wp_send_json_error(array(
                "code" => 403,
                "message" => "failed security check"
            ), 403);
            wp_die();
        }
        if (!isset($_POST['key'])) {
            wp_send_json_error(array(
                "code" => 400,
                "message" => "Missing Product key"
            ), 400);
            wp_die();
        }
        $cart_item_key = sanitize_text_field($_POST['key']);
        $cart_item = WC()->cart->get_cart_item($cart_item_key);

        if ($cart_item) {
            WC()->cart->remove_cart_item($cart_item_key);
            $product = wc_get_product($cart_item['product_id']);
            /* translators: %s: Item name. */
            $item_removed_title = apply_filters('woocommerce_cart_item_removed_title', $product ? sprintf(_x('&ldquo;%s&rdquo;', 'Item name in quotes', 'woocommerce'), $product->get_name()) : __('Item', 'woocommerce'), $cart_item);
            // Don't show undo link if removed item is out of stock.
            if ($product && $product->is_in_stock() && $product->has_enough_stock($cart_item['quantity'])) {
                /* Translators: %s Product title. */
                $removed_notice = sprintf(__('%s removed.', 'woocommerce'), $item_removed_title);
                $removed_notice .= ' <a href="' . esc_url(wc_get_cart_undo_url($cart_item_key)) . '" class="restore-item">' . __('Undo?', 'woocommerce') . '</a>';
            } else {
                /* Translators: %s Product title. */
                $removed_notice = sprintf(__('%s removed.', 'woocommerce'), $item_removed_title);
            }
            wc_add_notice($removed_notice);
        }

        \WC_AJAX::get_refreshed_fragments();
    }

    public static function udesly_wc_add_to_cart()
    {
        if (!self::verify_nonce()) {
            wp_send_json_error(array(
                "code" => 403,
                "message" => "failed security check"
            ), 403);
            wp_die();
        }
        if (!isset($_POST['product_id'])) {
            wp_send_json_error(array(
                "code" => 400,
                "message" => "Missing Product ID"
            ), 400);
            wp_die();
        }

        $product_id = apply_filters('woocommerce_add_to_cart_product_id', absint($_POST['product_id']));
        $quantity = empty($_POST['quantity']) ? 1 : wc_stock_amount(absint($_POST['quantity']));

        $passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity);
        $product_status = get_post_status($product_id);

        if ($passed_validation && 'publish' === $product_status && WC()->cart->add_to_cart($product_id, $quantity)) {

            do_action('woocommerce_ajax_added_to_cart', $product_id);
            $redirect = get_option('woocommerce_cart_redirect_after_add');

            wc_add_to_cart_message(array($product_id => $quantity), true);

            \WC_AJAX::get_refreshed_fragments();
        } else {

            $data = array(
                'error' => true,
                'product_url' => apply_filters('woocommerce_cart_redirect_after_error', get_permalink($product_id), $product_id));

            wp_send_json_error($data, 400);
        }

        wp_die();

    }

    public static function remove_actions()
    {

        $wc = Settings::get_wc_settings();

        if ($wc['disable_select_woo']) {
            add_action('wp_enqueue_scripts', function () {
                wp_dequeue_style('selectWoo');
                wp_deregister_style('selectWoo');

                wp_dequeue_script('selectWoo');
                wp_deregister_script('selectWoo');
            }, 100);
        }

        // Loop Actions
        remove_action('woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10);
        remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10);
        remove_action('woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10);
        remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5);
        remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10);
        remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
        remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5);
        remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5);
        remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);

        // Shop Page Actions
        remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
        remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
        remove_action('woocommerce_before_shop_loop', 'wc_print_notices', 10);
        remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
        remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);
        remove_action('woocommerce_after_shop_loop', 'woocommerce_pagination', 10);

        // Single Product Actions
        remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end ', 10);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title', 5);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
        remove_action('woocommerce_before_single_product', 'wc_print_notices', 10);
        remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10);
        remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);
        remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);
        remove_action('woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15);
        remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);

        add_action('wp_footer', 'wc_print_notices');
    }
}