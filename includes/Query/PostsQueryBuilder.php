<?php

namespace Udesly\Query;

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

class PostsQueryBuilder
{

    public static function get_query($name)
    {
        $query_post = udesly_get_post_by_slug($name, OBJECT, Posts::TYPE_NAME);
        if (!$query_post) {
            return new PostsQueryBuilder("invalid_query", array("posts_per_page" => 1));
        } else {
            $args = unserialize($query_post->post_content);
            $args = self::clean_query_post_content($args);
            return new PostsQueryBuilder($name, $args);
        }
    }

    private static function clean_query_post_content($data)
    {

        if (empty($data['taxonomy'])) {
            unset($data['taxonomy']);
        }
        if (empty($data['terms'])) {
            unset($data['terms']);
        }
        if (empty($data['author'])) {
            unset($data['author']);
        }
        if (empty($data['meta_key'])) {
            unset($data['meta_key']);
        }
        if (empty($data['meta_value'])) {
            unset($data['meta_value']);
        }

        if (isset($data['has_password'])) {
            if ($data['has_password'] == 'true') {
                $data['has_password'] = true;
            } else if ($data['has_password'] == 'false') {
                $data['has_password'] = false;
            } else {
                unset($data['has_password']);
            }
        }

        if (isset($data['taxonomy']) && udesly_string_starts_with( $data['taxonomy'], "udy_current_") ) {

            global $post;

            $taxonomies_part = explode("udy_current_", $data['taxonomy']);

            $terms = wp_get_post_terms( $post->ID, $taxonomies_part[1] );

            if (!is_wp_error($terms)) {

                $terms_id = [];

                foreach ($terms as $term) {
                    $terms_id[] = $term->term_id;
                }

                $data['tax_query'] = array(array(
                    'taxonomy' => $taxonomies_part[1],
                    'terms' => $terms_id
                ));
            }

            unset($data['taxonomy']);
        }
        if (isset($data['taxonomy']) && isset($data['terms'])) {
            $data['tax_query'] = array(array(
                'taxonomy' => $data['taxonomy'],
                'terms' => $data['terms']
            ));
            unset($data['taxonomy']);
            unset($data['terms']);
        }
        if (isset($data['author'])) {
            $data['author'] = join(",", $data['author']);
        }

        if (is_single()) {
            global $post;
            $data['post__not_in'] = array($post->ID);
        }

        return $data;
    }

    public $name;

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
            'no_found_rows' => false,
            'ignore_sticky_posts' => 1
        ), $query_arguments);
    }

    /**
     * Specify the post types that the query will retrieve.
     *
     * Can be a comma separated string or an array. Overwrites previous
     * specification criteria if called multiple times.
     *
     * @param string|array $from
     *
     * @return self
     */
    public function post_type($from)
    {
        if (is_string($from)) {
            $from = array_map('trim', explode(',', $from));
        } elseif (!is_array($from)) {
            return $this;
        }

        $this->query_arguments['post_type'] = $from;

        return $this;
    }

    /**
     * Specify the order of the query results.
     *
     * Overwrites previous specification criteria if called multiple times.
     *
     * @param string|array $sort
     * @param string $order
     *
     * @return self
     */
    public function order_by($sort, $order = 'DESC')
    {
        if (empty($sort) || (!is_array($sort) && !is_string($sort))) {
            return $this;
        } elseif (!is_string($order) || !in_array(strtoupper($order), array('ASC', 'DESC'))) {
            $order = 'DESC';
        }

        $this->query_arguments['orderby'] = $sort;
        $this->query_arguments['order'] = $order;


        return $this;
    }

    /**
     * Specify the maximum number of results that the query will retrieve.
     *
     * Overwrites previous specification criteria if called multiple times.
     *
     * @param int $limit
     *
     * @return self
     */
    public function limit($limit)
    {
        if (!is_numeric($limit)) {
            return $this;
        }

        $this->query_arguments['posts_per_page'] = (int)$limit;

        return $this;
    }

    public function tax_query($taxonomy, $taxonomies, $operator = 'IN', $field = 'id')
    {
        if (!is_array($taxonomies) && !is_string($taxonomies) && !is_numeric($taxonomies)) {
            return $this;
        }
        if (!isset($this->query_arguments['tax_query'])) {
            $this->query_arguments['tax_query'] = array(
                array(
                    'taxonomy' => $taxonomy,
                    'field' => $field,
                    'terms' => $taxonomies,
                    'operator' => $operator
                ));
        } else {
            $this->query_arguments['tax_query'][] =
                array(
                    'taxonomy' => $taxonomy,
                    'field' => $field,
                    'terms' => $taxonomies,
                    'operator' => $operator
                );
            $this->query_arguments['tax_query']['relation'] = 'AND';
        }

        return $this;
    }

    public function post_in($ids)
    {
        if (!array($ids)) {
            return $this;
        }
        if (isset($this->query_arguments['post__not_in'])) {
            unset($this->query_arguments['post__not_in']);
        }
        $this->query_arguments['post__in'] = $ids;
        return $this;
    }

    public function post_not_in($ids)
    {
        if (!array($ids)) {
            return $this;
        }
        if (isset($this->query_arguments['post__in'])) {
            unset($this->query_arguments['post__in']);
        }
        $this->query_arguments['post__not_in'] = $ids;
        return $this;
    }

    public function author($ids)
    {
        if (!array($ids)) {
            return $this;
        }

        $this->query_arguments['author'] = $ids;

        return $this;
    }

    public function meta_query($metas = array())
    {
        if (!is_array($metas) || empty($metas)) {
            return $this;
        }
        if (count($metas) == 1) {
            $this->query_arguments['meta_key'] = $metas[0]['key'];
            $this->query_arguments['meta_value'] = $metas[0]['value'];
        } else {
            $this->query_arguments['meta_query'] = $metas;
        }
        return $this;

    }

    public function set_page( $page ) {
        $this->query_arguments["offset"] = $this->query_arguments['posts_per_page'] * ($page - 1);
    }

    /**
     * Query WordPress using the current specifications of the builder.
     *
     * @return \WP_Query
     */
    public function get_wp_query()
    {
        $query_arguments = apply_filters("udesly_posts_query_$this->name", $this->query_arguments);
        $query = new \WP_Query($query_arguments);

        return $query;
    }

    public function get_results()
    {
        $query_arguments = apply_filters("udesly_posts_query_$this->name", $this->query_arguments);
        $query = new \WP_Query($query_arguments);

        return $query->posts;
    }
}