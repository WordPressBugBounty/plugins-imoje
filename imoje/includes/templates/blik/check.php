<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="imoje-card">
	<div class="imoje-card-content">

		<img class="imoje-icon imoje-icon-info" src="<?php echo esc_url( WOOCOMMERCE_IMOJE_PLUGIN_URL . 'assets/images/icon-info.svg' ); ?>" alt="icon info">
		<img class="imoje-icon imoje-icon-success imoje-display-none" src="<?php echo esc_url( WOOCOMMERCE_IMOJE_PLUGIN_URL . 'assets/images/icon-success.svg' ); ?>" alt="icon success">

		<span class="imoje-blik-tip"><?php echo esc_html__( 'Now accept payment in your application.', 'imoje' ) ?></span>
	</div>
</div>
