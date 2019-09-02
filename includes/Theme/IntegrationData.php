<?php
namespace Udesly\Theme;

use Udesly\Query\Posts;
use Udesly\Query\Taxonomies;
use Udesly\Rules\Rule;
if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

class IntegrationData
{
    public $queries = array();
    public $rules = array();
    public $lists = array();
    public $custom_fields = array();
    public $menus = array();
    public $boxes = array();

    /* public function __construct( $obj )
    {
        $this->queries = $obj->query;
        $this->rules =  $obj->rule;
        $this->lists = $obj->list;
        $this->custom_fields = $obj->cf;
        $this->custom_post_types = $obj->cpt;
        $this->menus = $obj->menu;
        $this->boxes = $obj->boxes;
    } */

    public static function from_obj($obj)
    {

        $t = new IntegrationData();

        $t->queries = $obj->query;
        $t->rules = $obj->rule;
        $t->lists = $obj->list;
        $t->custom_fields = $obj->cf;
        $t->menus = $obj->menu;
        $t->boxes = $obj->boxes;

        return $t;
    }

    public function merge($obj)
    {
        $this->queries = array_merge($this->queries, $obj->query);
        $this->rules = array_merge($this->rules, $obj->rule);
        $this->lists = array_merge($this->lists, $obj->list);
        $this->custom_fields = array_merge($this->custom_fields, $obj->cf);
        $this->menus = array_merge($this->menus, $obj->menu);
        $this->boxes = array_merge($this->boxes, $obj->boxes);
    }

    public function get_missing_data()
    {
        $missing_data = new IntegrationData();
        $count = 0;

        foreach ($this->queries as $query) {

            if (!udesly_get_post_by_slug($query, OBJECT, Posts::TYPE_NAME)) {
                $missing_data->queries[] = $query;
                $count++;
            }
        }

        foreach ($this->rules as $rule) {

            if (!udesly_get_post_by_slug($rule, OBJECT, Rule::TYPE_NAME)) {
                $missing_data->rules[] = $rule;
                $count++;
            }
        }

        foreach ($this->lists as $list) {
            if (!udesly_get_post_by_slug($list, OBJECT, Taxonomies::TYPE_NAME)) {
                $missing_data->lists[] = $list;
                $count++;
            }
        }

        foreach ($this->menus as $menu) {
            if (!wp_get_nav_menu_items($menu)) {
                $missing_data->menus[] = $menu;
                $count++;
            }
        }

        return array(
            "data" => $missing_data,
            "count" => $count
        );
    }


}