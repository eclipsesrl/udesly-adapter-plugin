<?php

namespace Udesly\Search;

use Udesly\Dashboard\Views\Settings;
if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

class Search
{
    private $settings;

    public function __construct()
    {
        $this->settings = Settings::get_search_settings();
    }

    public function public_hooks()
    {
        add_action('pre_get_posts', array($this, 'search_results_number'), 20, 1);
        add_filter('template_include', array($this, 'search_redirect_page'), 21, 1);
        add_filter('excerpt_length', array($this, 'apply_search_excerpt'), 100);
        add_filter('excerpt_more', array($this, 'apply_search_excerpt_more'), 100);
    }

    public function apply_search_excerpt($excerpt_lenght)
    {

        if (!is_search())
            return $excerpt_lenght;


        if (isset($this->settings['excerpt_length']))
            return $this->settings['excerpt_length'];

        return $excerpt_lenght;
    }

    public function apply_search_excerpt_more($excerpt_more)
    {

        if (!is_search())
            return $excerpt_more;

        if (isset($this->settings['excerpt_more']))
            return $this->settings['excerpt_more'];

        return $excerpt_more;
    }


    public function search_results_number($query)
    {

        if (!is_search())
            return $query;


        if (!isset($this->settings['posts_per_page']))
            return $query;

        if ($query->is_main_query() && !is_admin() && $query->is_search()) {
            if (isset($_GET['post_type']) && $_GET['post_type'] == 'page') {
                $query->set('post_type', array('page'));
            }

            $query->set('posts_per_page', intval($this->settings['posts_per_page']));

        }
        return $query;
    }

    public function search_redirect_page($template)
    {

        if (!is_search())
            return $template;

        $post_type = get_query_var('post_type');
        if (file_exists(get_template_directory() . '/search-' . $post_type . '.php')) {

            $new_template = locate_template(array('search-' . $post_type . '.php'));
            if (!empty($new_template)) {
                $template = $new_template;
            }
        }

        if (!isset($this->settings['one_match_redirect']))
            return $template;

        if ($this->settings['one_match_redirect'] === false) {
            return $template;
        }

        if ($this->settings['one_match_redirect'] === true) {
            global $wp_query;
            if ($wp_query->post_count == 1) {
                wp_redirect(get_permalink($wp_query->posts['0']->ID));
                exit;
            }
        }
        return $template;
    }
}