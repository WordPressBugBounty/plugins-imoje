<?php

/** @var $imoje_unavailable_message */

?>
<div class="imoje-payment-method-container">


	<div class="woocommerce-error mb-1" role="alert">
		<?php isset( $imoje_unavailable_message ) && $imoje_unavailable_message
			? esc_html_e( $imoje_unavailable_message, 'imoje' )
			: esc_html_e( 'Payment method is unavailable, please select another one.', 'imoje' ) ?>
	</div>
</div>