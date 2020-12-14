<?php
/**
 * Admin View: Notice - Currency not supported.
 *
 * @package WooCommerce_Pix/Admin/Notices
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="error inline">
	<p><strong><?php _e( 'Pix Disabled', 'woocommerce-pix' ); ?></strong>: <?php printf( __( 'Currency <code>%s</code> is not supported. Works only with Brazilian Real.', 'woocommerce-pix' ), get_woocommerce_currency() ); ?>
	</p>
</div>
