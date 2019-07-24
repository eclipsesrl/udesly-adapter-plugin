<?php

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

/**
 * adds filter to add a rule
 * @param $subject_name
 * @param $name
 * @param array $options
 */
function udesly_add_rule($subject_name, $name, $options = []) {
    add_filter("udesly_rules_options_$subject_name", function($args) use($name, $options) {
        if (empty($options)) {
            $options = ["."];
        } else {
            foreach ($options as $key => $value) {
                $options[$key] = " " . trim($value);
            }
        }
        $args[$name] = $options;
        return $args;
    });
}


/**
 * Evaluates rule
 * @param $rule_slug
 * @return bool
 */
function udesly_eval_rule($rule_slug) {
   return \Udesly\Rules\Rule::eval_rule($rule_slug);
}

if(!function_exists('udesly_rule_evaluator_page_is_home_page')) {

    /**
     * Checks if is front page
     * @return bool
     */
    function udesly_rule_evaluator_page_is_home_page() {
        return is_front_page();
    }

}

if(!function_exists('udesly_rule_evaluator_user_is_logged_in')) {

    /**
     * Checks if user is logged in
     * @return bool
     */
    function udesly_rule_evaluator_user_is_logged_in() {
        return is_user_logged_in();
    }

}

if(!function_exists('udesly_rule_evaluator_user_is_not_logged_in')) {

    /**
     * Checks if user is logged in
     * @return bool
     */
    function udesly_rule_evaluator_user_is_not_logged_in() {
        return !is_user_logged_in();
    }

}

if(!function_exists('udesly_rule_evaluator_page_is_category')) {

    /**
     * Checks if current page is category page
     * @param mixed $category slug or id of the category
     * @return bool
     */
    function udesly_rule_evaluator_page_is_category( $category = "" ) {
        return is_category($category);
    }

}

if(!function_exists('udesly_rule_evaluator_page_is_blog_page')) {


    /**
     * Checks if current page is blog page Main archive page == HOME
     *
     * @return bool
     */
    function udesly_rule_evaluator_page_is_blog_page( ) {
        return is_home();
    }

}

if(!function_exists('udesly_rule_evaluator_page_is_archive')) {

    /**
     * Checks if current page is archive
     *
     * @return bool
     */
    function udesly_rule_evaluator_page_is_archive() {
        return is_archive();
    }

}


if(!function_exists('udesly_rule_evaluator_page_is_single')) {

    /**
     * checks if page is singular
     *
     * @return bool
     */

    function udesly_rule_evaluator_page_is_single() {
        return is_singular();
    }

}

if(!function_exists('udesly_rule_evaluator_page_is_single_of')) {
    /**
     * checks if page is singular of post type
     *
     * @param string $type
     * @return bool
     */

    function udesly_rule_evaluator_page_is_single_of($type)
    {
        return is_singular($type);
    }
}

if(!function_exists('udesly_rule_evaluator_user_is_role')) {

    /**
     * checks if user is of that role
     *
     * @param $role
     *
     * @return bool
     */
    function udesly_rule_evaluator_user_is_role( $role ) {
        $role = strtolower($role);

        if(is_user_logged_in()) {
            $user = wp_get_current_user();
            $u_r = (array) $user->roles;
            return $u_r[0] == $role;
        }
        else {
            return false;
        }

    }
}

if(!function_exists('udesly_rule_evaluator_archive_has_subcategories')) {

    /**
     *
     * @return bool
     */
    function udesly_rule_evaluator_archive_has_subcategories() {
        if (is_category()) {

            $childrens = get_term_children(get_queried_object_id(), 'category');
            if ( empty($childrens) ){
                return false;
            }else{
                return true;
            }
        }
        return false;
    }

}


if(!function_exists('udesly_rule_evaluator_archive_do_not_have_subcategories')) {


    function udesly_rule_evaluator_archive_do_not_have_subcategories() {
        if (is_category()) {

            $childrens = get_term_children(get_queried_object_id(), 'category');
            if ( empty($childrens) ){
                return true;
            }else{
                return false;
            }
        }
        return true;
    }

}

if(!function_exists('udesly_rule_evaluator_user_has_purchased_edd')) {

    function udesly_rule_evaluator_user_has_purchased_edd( $download_id ) {
        if (is_numeric($download_id)) {
            return edd_has_user_purchased(get_current_user_id(), array(intval($download_id)));
        } else {
            $download = edd_get_download($download_id);
            if ($download) {
                return edd_has_user_purchased(get_current_user_id(), array( $download->get_ID()));
            } else {
                return false;
            }
        }
    }

}

if(!function_exists('udesly_rule_evaluator_user_can_access_rcp')) {

    function udesly_rule_evaluator_user_can_access_rcp() {
        return rcp_user_can_access();
    }

}

if(!function_exists('udesly_rule_evaluator_user_is_active_rcp')) {

    function udesly_rule_evaluator_user_is_active_rcp() {
        if (function_exists('rcp_user_has_active_membership')) {
            return rcp_user_has_active_membership();
        } else {
            return rcp_is_active();
        }

    }

}

if(!function_exists('udesly_rule_evaluator_user_is_expired_rcp')) {

    function udesly_rule_evaluator_user_is_expired_rcp() {
        if (function_exists('rcp_user_has_expired_membership')) {
            return rcp_user_has_expired_membership();
        } else {
            return rcp_is_expired();
        }

    }

}

if (!function_exists('udesly_rule_evaluator_page_is_taxonomy_of')) {

    function udesly_rule_evaluator_page_is_taxonomy_of($tax) {
        return is_tax($tax);
    }
}

if(!function_exists('udesly_rule_evaluator_user_is_trialing_rcp')) {

    function udesly_rule_evaluator_user_is_trialing_rcp() {
        if (function_exists('rcp_get_customer_by_user_id')) {
            $rcp_customer = rcp_get_customer_by_user_id(get_current_user_id());
            $memberships = $rcp_customer->get_memberships();
            foreach ($memberships as $membership) {
                if ($membership->is_trialing()) {
                    return true;
                }
            }
            return false;
        } else {
            return rcp_is_trialing();
        }

    }

}

if(!function_exists('udesly_rule_evaluator_user_is_pending_verification_rcp')) {

    function udesly_rule_evaluator_user_is_pending_verification_rcp() {
        return rcp_is_pending_verification();
    }

}

if(!function_exists("udesly_rule_evaluator_user_has_used_trial_rcp")) {

    function udesly_rule_evaluator_user_has_used_trial_rcp() {
        if (function_exists('rcp_get_customer_by_user_id')) {
            $rcp_customer = rcp_get_customer_by_user_id(get_current_user_id());
            return $rcp_customer->has_trialed();
        } else {
            return rcp_has_used_trial();
        }
    }
}

if (!function_exists('udesly_rule_evaluator_post_is_paid_content_rcp')) {

    function udesly_rule_evaluator_post_is_paid_content_rcp() {
        global $post;
        return rcp_is_paid_content($post->ID);
    }
}

if (!function_exists('udesly_rule_evaluator_post_is_restricted_content_rcp')) {

    function udesly_rule_evaluator_post_is_restricted_content_rcp() {
        global $post;
        return rcp_is_restricted_content($post->ID);
    }
}

if(!function_exists('udesly_rule_evaluator_user_has_subscription_rcp')) {

    function udesly_rule_evaluator_user_has_subscription_rcp( $subscription_name ) {
        if (function_exists('rcp_get_customer_by_user_id')) {
            $rcp_customer = rcp_get_customer_by_user_id(get_current_user_id());
            $memberships = $rcp_customer->get_memberships( array(
                "status" => "active"
            ));
            $levels = new RCP_Levels();
            $level  = $levels->get_level_by( 'name', $subscription_name );
            foreach ($memberships as $membership) {
                if ($membership->get_object_id() === $level->id) {
                    return true;
                }
            }
            return false;
        } else {
            return rcp_get_subscription(get_current_user_id()) === $subscription_name;
        }
    }
}