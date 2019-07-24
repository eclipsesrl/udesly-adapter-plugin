<?php
/**
 * Checkout coupon form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-coupon.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.4.4
 */
defined( 'ABSPATH' ) || exit;
if ( ! wc_coupons_enabled() ) { // @codingStandardsIgnoreLine.
    return;
}
global $udesly_checkout_classes;

?>
<div class="woocommerce-form-coupon-toggle <?php echo $udesly_checkout_classes['h']; ?>" style="margin-bottom: 10px;">
    <div class="woocommerce-info">
        <?php
        echo wc_kses_notice( apply_filters( 'woocommerce_checkout_coupon_message', __( 'Have a coupon?', 'woocommerce' ) . ' <a href="#" class="showcoupon">' . __( 'Click here to enter your code', 'woocommerce' ) . '</a>' ) );
        ?>
    </div>
</div>

<form class="checkout_coupon woocommerce-form-coupon <?php echo $udesly_checkout_classes['c']; ?>" method="post" style="margin-top: -10px; display:none">

    <p><?php esc_html_e( 'If you have a coupon code, please apply it below.', 'woocommerce' ); ?></p>

    <div style="display: grid; grid-template-columns: 1fr 1fr; grid-gap: 8px; justify-items: flex-start;">
    <p class="form-row ">
        <input type="text" name="coupon_code" class="input-text <?php echo $udesly_checkout_classes['i']; ?>" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>" id="coupon_code" value="" />
    </p>

    <p class="form-row ">
        <button type="submit" class="<?php echo $udesly_checkout_classes['b']; ?>" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>"><?php esc_html_e( 'Apply coupon', 'woocommerce' ); ?></button>
    </p>
    </div>
    <div class="clear"></div>
</form>