<?php

/** @var array $payment_method_list_active */
/** @var array $payment_method_list_no_active */
/** @var bool $is_blik */

$payment_method_list = array_merge( $payment_method_list_active, $payment_method_list_no_active );

?>

<div class="imoje-payment-method-container">

	<div class="imoje-pbl-error woocommerce-error" role="alert">
		<?php esc_html_e( 'Choose payment channel if is available. In other way choose another payment method', 'imoje' ) ?>
	</div>

	<ul class="imoje-channels">
		<?php

		foreach ( $payment_method_list as $payment_method ) { ?>

			<li class="imoje-channel imoje-channel-<?php echo esc_attr( $payment_method['payment_method_code'] ) ?> imoje-c-<?php echo $payment_method['is_available']
				? 'active'
				: 'no-active' ?>"
			    title="<?php

			    $payment_method_title = esc_attr( $payment_method['description'] );

			    if ( ! $payment_method['is_available'] ) {

				    $payment_method_title = __( 'Payment channel is not available.', 'imoje' ) . ' ';

				    if (
					    $payment_method['limit']
					    && (
						    $payment_method['min_transaction']
						    || $payment_method['max_transaction'] )
				    ) {

					    $need_extra = $payment_method['limit'] === 1;

					    $payment_method_title = Helper::get_tooltip_payment_channel( $payment_method );
				    }
			    }

			    echo esc_attr( $payment_method_title );

			    $input_value     = $payment_method['payment_method'] . '-' . $payment_method['payment_method_code'];
			    $is_default_blik = $payment_method['is_available'] && $is_blik && $input_value = 'blik-blik';
			    ?>">
				<label class="imoje-c-<?php echo $payment_method['is_available']
					? 'active'
					: 'no-active' ?><?= $is_default_blik
					? ' imoje-active'
					: '' ?>">
					<input type="radio"
						<?php if ( $is_default_blik ) { ?>
							checked
						<?php } ?>
						   value="<?php echo esc_attr( $input_value ) ?>"
						   name="imoje-selected-channel"/>
					<div>
						<img src="<?php echo esc_url( $payment_method['logo'] ); ?>" alt="logo">
					</div>
				</label>
			</li>
		<?php } ?>
	</ul>
</div>