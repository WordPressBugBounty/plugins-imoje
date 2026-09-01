<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array $args */

$imoje_installments_data = isset( $args['installments_data'] ) && is_array( $args['installments_data'] )
	? $args['installments_data']
	: [];

$imoje_installments_value = function ( $key ) use ( $imoje_installments_data ) {
	return isset( $imoje_installments_data[ $key ] ) ? $imoje_installments_data[ $key ] : '';
};

?>

<div class="imoje-payment-method-container">
	<div class="imoje-installments__wrapper" id="imoje-installments__wrapper"
          data-installments-amount="<?php echo esc_attr( $imoje_installments_value( 'amount' ) ); ?>"
          data-installments-currency="<?php echo esc_attr( $imoje_installments_value( 'currency' ) ); ?>"
          data-installments-service-id="<?php echo esc_attr( $imoje_installments_value( 'serviceId' ) ); ?>"
          data-installments-merchant-id="<?php echo esc_attr( $imoje_installments_value( 'merchantId' ) ); ?>"
          data-installments-signature="<?php echo esc_attr( $imoje_installments_value( 'signature' ) ); ?>"
          data-installments-url="<?php echo esc_url( $imoje_installments_value( 'url' ) ); ?>"
     >
	</div>

	<input name="imoje-selected-channel-installments" id="imoje-selected-channel-installments" hidden>
	<input name="imoje-installments-period" id="imoje-installments-period" hidden>
</div>
