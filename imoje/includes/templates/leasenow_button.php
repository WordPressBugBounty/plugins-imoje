<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var int $leasenow_image_scale */

$leasenowImageScale = isset( $leasenow_image_scale ) && $leasenow_image_scale
	? esc_html( $leasenow_image_scale )
	: "50";

?>

<div class="leasenow_button-content">
	<img width="<?php echo $leasenowImageScale; ?>%" alt="ING Lease Now" class="leasenow_button-image" src="<?php echo esc_html( WOOCOMMERCE_IMOJE_PLUGIN_URL . 'assets/images/leasenow_button.png' ); ?>">
</div>

