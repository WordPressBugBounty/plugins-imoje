<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array $args */

?>
<div class="imoje-payment-method-container">

	<div class="woocommerce-error mb-1" role="alert">
		<?php
		if ( isset( $args['imoje_unavailable_message'] ) && $args['imoje_unavailable_message'] ) {
			echo esc_html( $args['imoje_unavailable_message'] );
		} else {
			esc_html_e( 'Payment method is unavailable, please select another one.', 'imoje' );
		}
		?>
	</div>
</div>