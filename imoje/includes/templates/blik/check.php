<?php

/** @var string $imoje_transaction_id */
/** @var string $imoje_order_return_url */

?>

<div class="imoje-card">
	<div class="imoje-card-content">

		<img class="imoje-icon imoje-icon-info" src="<?php echo esc_html( WOOCOMMERCE_IMOJE_PLUGIN_URL . 'assets/images/icon-info.svg' ); ?>" alt="icon info">
		<img class="imoje-icon imoje-icon-success imoje-display-none" src="<?php echo esc_html( WOOCOMMERCE_IMOJE_PLUGIN_URL . 'assets/images/icon-success.svg' ); ?>" alt="icon success">

		<span class="imoje-blik-tip"><?php echo esc_html__( 'Now accept payment in your application.', 'imoje' ) ?></span>
	</div>
</div>

<script type="text/javascript">

	(function ($) {

		var $imoje_blik_tip = $('.imoje-blik-tip'),
			ms_blik_submit = 90000,
			ms_redirect = 5000,
			ms_speed_scroll = 2000,
			ms_check_payment = 2000,
			dont_processing = false,
			dont_processing_timeout = null,
			transaction_status_pending = 'pending',
			transaction_status_settled = 'settled',
			transaction_status_rejected = 'rejected',
			action_imoje_check_transaction = 'imoje_check_transaction',
			method_imoje_check_transaction = 'imoje_blik',
			method_post = 'POST';

		dont_processing_timeout = setTimeout(
			function () {
				dont_processing = true
			}, ms_blik_submit);

		check_payment();

		$('html, body').animate({
			scrollTop: $('.imoje-card').offset().top
		}, ms_speed_scroll);

		function append_result_text(text) {

			$imoje_blik_tip.text(text);
		}

		function clear_dont_processing_timeout() {
			clearTimeout(dont_processing_timeout);
			dont_processing = false;
		}

		function check_payment() {
			jQuery.ajax({
				data:   {
					action:           action_imoje_check_transaction,
					method:           method_imoje_check_transaction,
					transaction_uuid: '<?php echo esc_html( $imoje_transaction_id ); ?>',
				},
				method: method_post,
				url:    "<?php echo esc_html( admin_url( 'admin-ajax.php' ) ) ?>",
			})
				.then(function (response) { // done

					if (!response.success) {

						append_result_text('<?php echo esc_html__( 'Something went wrong. Please contact with shop staff', 'imoje' ) ?>');
						return;
					}

					if (response.data.error) {

						if (response.data.code) {

							append_result_text(response.data.error);
							return;
						}

						append_result_text(response.data.error);
						return;
					}

					if (response.data.transaction.status === transaction_status_pending) { // state inny niz pending

						if (dont_processing) {
							$('.imoje-icon-info').hide();

							append_result_text('<?php echo esc_html__( 'Response timed out. Create new order or contact with shop staff.', 'imoje' ) ?>');
							clear_dont_processing_timeout();
							return;
						}

						setTimeout(function () {
							check_payment()
						}, ms_check_payment);

						return;
					}

					clear_dont_processing_timeout();

					if (response.data.transaction.status === transaction_status_settled) {

						$('.imoje-icon-info').hide();
						$('.imoje-icon-success').show();
						append_result_text('<?php echo esc_html__( 'Your payment was correctly processed, you will be informed about next steps.', 'imoje' ) ?>');

						setTimeout(
							function () {
								window.location.href = '<?php echo esc_html( $imoje_order_return_url ); ?>'
							}, ms_redirect)

					}

				});
		}

		// endregion
	})(jQuery)
</script>