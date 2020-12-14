<?php
/**
 * Payment instructions.
 *
 * @package WooCommerce_Pix/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<?php if ( 2 == $type ) : ?>

	<div class="woocommerce-message">
		<span><a class="button" href="<?php echo esc_url( $link ); ?>" target="_blank"><?php _e( 'Pay the Banking Ticket', 'woocommerce-pix' ); ?></a><?php _e( 'Please click in the following button to view your Banking Ticket.', 'woocommerce-pix' ); ?><br /><?php _e( 'You can print and pay in your internet banking or in a lottery retailer.', 'woocommerce-pix' ); ?><br /><?php _e( 'After we receive the ticket payment confirmation, your order will be processed.', 'woocommerce-pix' ); ?></span>
	</div>

<?php elseif ( 3 == $type ) : ?>

	<div class="woocommerce-message">
		<span><a class="button" href="<?php echo esc_url( $link ); ?>" target="_blank"><?php _e( 'Pay at your bank', 'woocommerce-pix' ); ?></a><?php _e( 'Please use the following button to make the payment in your bankline.', 'woocommerce-pix' ); ?><br /><?php _e( 'After we receive the confirmation from the bank, your order will be processed.', 'woocommerce-pix' ); ?></span>
	</div>

<?php else : ?>

	<div class="woocommerce-message">
		<span><?php echo sprintf( __( 'You just made the payment in %s using the %s.', 'woocommerce-pix' ), '<strong>' . $installments . 'x</strong>', '<strong>' . $method . '</strong>' ); ?><br /><?php _e( 'As soon as the credit card operator confirm the payment, your order will be processed.', 'woocommerce-pix' ); ?></span>
	</div>

<?php
endif;
