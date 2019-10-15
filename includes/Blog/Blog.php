<?php


namespace Udesly\Blog;

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

use Udesly\Dashboard\Views\Settings;

use Udesly\Query\PostsQueryBuilder;



class Blog
{
    private $settings;

    public function __construct()
    {
        $this->settings = Settings::get_blog_settings();
    }

    public function public_hooks()
    {
        add_filter('get_the_archive_title', array($this, "filter_title"));
        add_filter('excerpt_length', array($this, "excerpt"));
        add_filter('excerpt_more', array($this, "more"));
        add_action('wp_ajax_udesly_get_posts', array($this, "udesly_get_posts"));
        add_action('wp_ajax_nopriv_udesly_get_posts', array($this, "udesly_get_posts"));
        add_filter( 'get_the_archive_description', array($this, "filter_archive_description") );

        if ($this->settings['hide_password_protected'] == true) {
            add_filter( 'posts_where', array($this, "password_post_filter") );
        }

        add_filter( 'private_title_format', function( $format ) {
            return '%s';
        } );
        add_filter( 'protected_title_format', function( $format ) {
            return '%s';
        }  );

    }

    public function filter_archive_description($description) {
        if (is_home() && $this->settings['archive_description']) {
            return $this->settings['archive_description'];
        }else {
            return $description;
        }
    }

    public function udesly_get_posts()
    {
        if (!check_ajax_referer('udesly-ajax-action', 'security', false)) {
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

        $template_path = trailingslashit( get_template_directory() ) . "template-parts/$query_template.php";

        if (!file_exists($template_path)) {
            wp_send_json_error(array("message" => "Template doesn't exists"), 400);
            wp_die();
        }

        $query->set_page($page);
        $wp_query = $query->get_wp_query();
        if ($wp_query->have_posts()) {
            ob_start();
            while ( $wp_query->have_posts() ) {
                $wp_query->the_post();
                include $template_path;
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

    public function password_post_filter( $where = '' ) {
        if (!is_single() && !is_admin()) {
            $where .= " AND post_password = ''";
        }
        return $where;
    }


    public function excerpt($length)
    {

        return $this->settings['excerpt_length'];

    }

    public function more($more)
    {

        return $this->settings['excerpt_more'];

    }

    public function filter_title($title)
    {

        if (is_category()) {
            $categories_title = $this->settings['category_title'];
            $title = sprintf($categories_title, single_cat_title('', false));
        } elseif (is_tag()) {
            $tag_title = $this->settings['tag_title'];
            $title = sprintf($tag_title, single_tag_title('', false));
        } elseif (is_author()) {
            $author_title = $this->settings['author_title'];
            $title = sprintf($author_title, '<span class="vcard">' . get_the_author() . '</span>');
        } elseif (is_year()) {
            /* translators: Yearly archive title. 1: Year */
            $title = sprintf(__('Year: %s'), get_the_date(_x('Y', 'yearly archives date format')));
        } elseif (is_month()) {
            /* translators: Monthly archive title. 1: Month name and year */
            $title = sprintf(__('Month: %s'), get_the_date(_x('F Y', 'monthly archives date format')));
        } elseif (is_day()) {
            /* translators: Daily archive title. 1: Date */
            $title = sprintf(__('Day: %s'), get_the_date(_x('F j, Y', 'daily archives date format')));
        } elseif (is_tax('post_format')) {
            if (is_tax('post_format', 'post-format-aside')) {
                $title = _x('Asides', 'post format archive title');
            } elseif (is_tax('post_format', 'post-format-gallery')) {
                $title = _x('Galleries', 'post format archive title');
            } elseif (is_tax('post_format', 'post-format-image')) {
                $title = _x('Images', 'post format archive title');
            } elseif (is_tax('post_format', 'post-format-video')) {
                $title = _x('Videos', 'post format archive title');
            } elseif (is_tax('post_format', 'post-format-quote')) {
                $title = _x('Quotes', 'post format archive title');
            } elseif (is_tax('post_format', 'post-format-link')) {
                $title = _x('Links', 'post format archive title');
            } elseif (is_tax('post_format', 'post-format-status')) {
                $title = _x('Statuses', 'post format archive title');
            } elseif (is_tax('post_format', 'post-format-audio')) {
                $title = _x('Audio', 'post format archive title');
            } elseif (is_tax('post_format', 'post-format-chat')) {
                $title = _x('Chats', 'post format archive title');
            }
        } elseif (is_post_type_archive()) {
            /* translators: Post type archive title. 1: Post type name */
            $title = sprintf($this->settings['general_archive_title'], post_type_archive_title('', false));
        } elseif (is_tax()) {
            $tax = get_taxonomy(get_queried_object()->taxonomy);
            /* translators: Taxonomy term archive title. 1: Taxonomy singular name, 2: Current taxonomy term */
            $title = sprintf(__('%1$s: %2$s'), $tax->labels->singular_name, single_term_title('', false));
        } else {
            $archive_title = $this->settings['archive_title'];
            $title = $archive_title;
        }

        return $title;
    }
}