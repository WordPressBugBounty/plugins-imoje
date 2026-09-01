<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array $args */

$imoje_lease_image_scale = ( isset( $args['lease_image_scale'] ) && $args['lease_image_scale'] )
	? (int) $args['lease_image_scale']
	: 50;

?>

<div class="lease_button-content">
	<img width="<?php echo esc_attr( $imoje_lease_image_scale ); ?>%" alt="ING Lease Now" class="lease_button-image" src="<?php echo esc_url( WOOCOMMERCE_IMOJE_PLUGIN_URL . 'assets/images/leasenow_button.png' ); ?>">
</div>

