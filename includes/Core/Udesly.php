<?php

namespace Udesly\Core;

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

use Udesly\Assets\Scripts;
use Udesly\Blog\Blog;
use Udesly\Boxes\Box;
use Udesly\CPT\CustomPostTypes;
use Udesly\Dashboard\Menu;
use Udesly\Dashboard\Views\Settings;
use Udesly\FrontendEditor\FrontendEditorType;
use Udesly\Query\Posts;
use Udesly\Query\Taxonomies;
use Udesly\Rules\Rule;
use Udesly\Search\Search;
use Udesly\Terms\Terms;
use Udesly\Theme\DataManager;
use Udesly\User\User;
use Udesly\WC\WC;

/**
 * Class Udesly
 * @package Udesly\Core
 *
 * This class handles the loading of text domains, and hooks
 */
class Udesly
{

    public $version;

    public $plugin_name;

    public function __construct()
    {
        if (defined('UDESLY_ADAPTER_VERSION')) {
            $this->version = UDESLY_ADAPTER_VERSION;
        } else {
            $this->version = '2.0.0';
        }
        $this->plugin_name = 'udesly-adapter';
    }

    public function run()
    {

        $this->include_dependencies();

        $this->set_locale();
        if (is_admin()) {
            $this->add_admin_hooks();
        }
        $this->add_public_hooks();

    }

    public static function is_wc_active() {
        $res = false;
        if ( class_exists( 'woocommerce' ) || in_array(
                'woocommerce/woocommerce.php',
                apply_filters('active_plugins', get_option('active_plugins'))
            ) ) {
            $res = true;
        }

        return apply_filters('udesly_wc_is_active', $res);
    }


    /**
     * Loads text locale
     */
    public function set_locale()
    {
        add_action('plugins_loaded', array($this, 'load_locale'));
    }

    public function include_dependencies()
    {

        // Misc functions
        require_once UDESLY_ADAPTER_PLUGIN_MISC_PATH . 'general.php';
        require_once UDESLY_ADAPTER_PLUGIN_MISC_PATH . 'blog.php';
        require_once UDESLY_ADAPTER_PLUGIN_MISC_PATH . 'terms.php';
        require_once UDESLY_ADAPTER_PLUGIN_MISC_PATH . 'strings.php';
        require_once UDESLY_ADAPTER_PLUGIN_MISC_PATH . 'frontend-editor.php';
        require_once UDESLY_ADAPTER_PLUGIN_MISC_PATH . 'posts-query.php';
        require_once UDESLY_ADAPTER_PLUGIN_MISC_PATH . 'boxes.php';
        require_once UDESLY_ADAPTER_PLUGIN_MISC_PATH . 'terms-query.php';
        require_once UDESLY_ADAPTER_PLUGIN_MISC_PATH . 'rules.php';
        require_once UDESLY_ADAPTER_PLUGIN_MISC_PATH . 'social.php';
        require_once UDESLY_ADAPTER_PLUGIN_MISC_PATH . 'comments.php';

        //Pagination function integration
        require_once UDESLY_ADAPTER_PLUGIN_MISC_PATH . 'pagination.php';

        // Checks if ACF is active
        if (function_exists('get_field') || is_multisite()) {
            require_once UDESLY_ADAPTER_PLUGIN_MISC_PATH . 'acf.php';
        }

        if (
          $this->is_wc_active()
        ) {
            require_once UDESLY_ADAPTER_PLUGIN_MISC_PATH . 'woocommerce.php';
        }

        if (
            class_exists('TRP_Translate_Press')
        ) {
            require_once UDESLY_ADAPTER_PLUGIN_MISC_PATH . 'translate-press.php';
        }

        add_filter('template_include', function ($template) {
            if (post_password_required() && !is_post_type_archive()) {
                $path = trailingslashit( get_template_directory() ) . '401.php';
                if (file_exists($path)) {
                    return $path;
                }
            }

            return $template;
        });
    }

    public function add_admin_hooks()
    {

        Menu::admin_hooks();
        Posts::admin_hooks();
        Settings::admin_hooks();
        Scripts::admin_hooks();
        DataManager::admin_hooks();
        Taxonomies::admin_hooks();
        Rule::admin_hooks();
        Terms::admin_hooks();
        CustomPostTypes::admin_hooks();

    }

    public function add_public_hooks()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_library'));
        Posts::public_hooks();
        Taxonomies::public_hooks();
        Scripts::public_hooks();
        DataManager::public_hooks();
        FrontendEditorType::public_hooks();
        Box::public_hooks();
        Rule::public_hooks();
        User::public_hooks();
        $search = new Search();
        $search->public_hooks();
        CustomPostTypes::public_hooks();
        $blog = new Blog();
        $blog->public_hooks();
        if ( $this->is_wc_active() ) {
            WC::public_hooks();
            add_action('wp_enqueue_scripts', array($this, 'enqueue_wc_library'));
        }

        add_action('wp_ajax_udesly_wp_refresh_nonce', array(self::class, 'udesly_wp_refresh_nonce'));
        add_action('wp_ajax_nopriv_udesly_wp_refresh_nonce', array(self::class, 'udesly_wp_refresh_nonce'));
        add_action('wp_loaded', array($this, 'enable_temporary_mode'));
    }

    public function udesly_wp_refresh_nonce() {
        wp_send_json_success(
            array(
                'nonce' => wp_create_nonce("udesly-ajax-action"),
            )
        );

        wp_die();
    }

    public function enable_temporary_mode() {
        $tools = Settings::get_tools_settings();
        if ($tools['temporary_mode_enabled'] == true) {
           $mode = $tools['temporary_mode_type'];
           global $pagenow;

			if ( $pagenow !== 'wp-login.php' && strpos($_SERVER['REQUEST_URI'], 'wp-admin') === false && ! current_user_can( 'administrator' ) ) {
                if ( file_exists( get_template_directory() . '/temporary.php' ) ) {

                    if(isset($settings['temp_mode_type']) && $mode == 'maintenance'){
                        header( 'HTTP/1.1 503 Service Temporarily Unavailable' );
                        header( 'Content-Type: text/html; charset=utf-8' );
                    }else{
                        //coming soon
                        header( 'HTTP/1.1 307 Temporarily Redirect' );
                        header( 'Content-Type: text/html; charset=utf-8' );
                    }
                    require_once( get_template_directory() . '/temporary.php' );
                }else{
                    header( 'HTTP/1.1 503 Service Temporarily Unavailable' );
                    header( 'Content-Type: text/html; charset=utf-8' );

                    if ( file_exists( get_template_directory() . '/404.php' ))

                        require_once( get_template_directory() . '/404.php' );
                }
                die();
            }
        }
    }

    public function enqueue_library() {
        wp_enqueue_script('udesly-wp-wf', UDESLY_ADAPTER_PLUGIN_DIRECTORY_URL . 'assets/js/bundle/udesly-wf-wp.bundle.min.js', array(), $this->version, true);
        wp_localize_script('udesly-wp-wf', "udeslyAjax", array(
            "ajaxUrl" => admin_url('admin-ajax.php'),
            "nonce" => wp_create_nonce("udesly-ajax-action"),
            "config" => Settings::get_js_settings(),
        ));

        wp_enqueue_style('udesly-ajax-loading', UDESLY_ADAPTER_PLUGIN_DIRECTORY_URL . 'assets/css/ajax-loading.css', array(), $this->version);

    }

    public function enqueue_wc_library() {
        wp_enqueue_script('udesly-wp-wc', UDESLY_ADAPTER_PLUGIN_DIRECTORY_URL . 'assets/js/bundle/udesly-wf-wc.bundle.min.js', array(), $this->version, true);
        wp_localize_script(
            'udesly-wp-wc',
            'udesly_price_params',
            array(
                'currency_format_num_decimals' => wc_get_price_decimals(),
                'currency_format_symbol'       => get_woocommerce_currency_symbol(),
                'currency_format_decimal_sep'  => esc_attr( wc_get_price_decimal_separator() ),
                'currency_format_thousand_sep' => esc_attr( wc_get_price_thousand_separator() ),
                'currency_format'              => esc_attr( str_replace( array( '%1$s', '%2$s' ), array( '%s', '%v' ), get_woocommerce_price_format() ) ),
            )
        );
    }

    /**
     * Loads plugin text domain
     * @see 'plugins_loaded'
     */
    public function load_locale()
    {
        load_plugin_textdomain(
            UDESLY_TEXT_DOMAIN,
            false,
            UDESLY_ADAPTER_PLUGIN_DIRECTORY_PATH . '/languages/'
        );
    }
}