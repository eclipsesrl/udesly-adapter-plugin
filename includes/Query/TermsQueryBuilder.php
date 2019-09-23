<?php

namespace Udesly\Query;

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

class TermsQueryBuilder
{

    public static function get_query($name)
    {
        $query_post = udesly_get_post_by_slug($name, OBJECT, Taxonomies::TYPE_NAME);
        if (!$query_post) {
            return new TermsQueryBuilder("invalid_query", array("number" => 1));
        } else {
            $args = unserialize($query_post->post_content);
            $args = self::clean_query_post_content($args);
            return new TermsQueryBuilder($name, $args);
        }
    }

    private static function clean_query_post_content($data)
    {

        if (empty($data['taxonomy'])) {
            unset($data['taxonomy']);
        }
        if (empty($data['name__like'])) {
            unset($data['name__like']);
        }

        if (isset($data['top_level']) && $data['top_level'] == true) {
            unset($data['top_level']);
            $data['parent'] = 0;
        }

        return $data;
    }

    private $name;

    /**
     * The query arguments collected by the query builder.
     *
     * @var array
     */
    private $query_arguments;

    /**
     * Constructor.
     *
     * @param $name string
     * @param array $query_arguments
     */
    public function __construct($name, array $query_arguments = array())
    {
        $this->name = $name;
        $this->query_arguments = array_merge(array(
            'no_found_rows' => true,
            'ignore_sticky_posts' => 1,
        ), $query_arguments);
    }


    public function get_wp_query()
    {
        $query_arguments = apply_filters("udesly_terms_query_$this->name", $this->query_arguments);
        $query = new \WP_Term_Query($query_arguments);

        return $query;
    }

    public function get_results()
    {
        $query_arguments = apply_filters("udesly_terms_query_$this->name", $this->query_arguments);
        $query = new \WP_Term_Query($query_arguments);

        return $query->terms;
    }
}