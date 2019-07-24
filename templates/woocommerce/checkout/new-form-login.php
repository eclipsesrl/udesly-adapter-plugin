<?php
/**
 * Checkout login form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-login.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.4.0
 */
defined('ABSPATH') || exit;
if (is_user_logged_in() || 'no' === get_option('woocommerce_enable_checkout_login_reminder')) {
    return;
}

global $udesly_checkout_classes;
if (is_user_logged_in()) {
    return;
}

$message = __('If you have shopped with us before, please enter your details below. If you are a new customer, please proceed to the Billing &amp; Shipping section.', 'woocommerce');
$redirect = wc_get_page_permalink('checkout');
$hidden = true;
?>
<form class="woocommerce-form woocommerce-form-login login <?php echo $udesly_checkout_classes['c_w']; ?>"
      method="post" <?php echo ($hidden) ? 'style="display:none;"' : ''; ?>>

    <?php do_action('woocommerce_login_form_start'); ?>

    <?php echo ($message) ? wpautop(wptexturize($message)) : ''; // @codingStandardsIgnoreLine ?>

    <p class="form-row form-row-first">
        <label for="username"
               class="<?php echo $udesly_checkout_classes['l']; ?>"><?php esc_html_e('Username or email', 'woocommerce'); ?>
            &nbsp;<span class="required">*</span></label>
        <input type="text" class="input-text <?php echo $udesly_checkout_classes['i']; ?>" name="username" id="username"
               autocomplete="username"/>
    </p>
    <p class="form-row form-row-last">
        <label for="password" <?php echo $udesly_checkout_classes['l']; ?>><?php esc_html_e('Password', 'woocommerce'); ?>
            &nbsp;<span class="required">*</span></label>
        <input class="input-text <?php echo $udesly_checkout_classes['i']; ?>" type="password" name="password"
               id="password" autocomplete="current-password"/>
    </p>
    <div class="clear"></div>

    <?php do_action('woocommerce_login_form'); ?>

    <p class="form-row">
        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme <?php echo $udesly_checkout_classes['l']; ?>">
            <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox"
                   id="rememberme" value="forever"/> <span><?php esc_html_e('Remember me', 'woocommerce'); ?></span>
        </label>
        <?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
        <input type="hidden" name="redirect" value="<?php echo esc_url($redirect) ?>"/>
        <button type="submit"
                class="<?php echo $udesly_checkout_classes['b']; ?>"
                name="login"
                value="<?php esc_attr_e('Login', 'woocommerce'); ?>"><?php esc_html_e('Login', 'woocommerce'); ?></button>
    </p>
    <p class="lost_password">
        <a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php esc_html_e('Lost your password?', 'woocommerce'); ?></a>
    </p>

    <div class="clear"></div>

    <?php do_action('woocommerce_login_form_end'); ?>

</form>