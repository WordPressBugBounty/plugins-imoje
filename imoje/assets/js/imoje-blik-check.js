(function ($) {

	var config = window.imoje_blik_check_object;

	if (!config) {
		return;
	}

	var $imoje_blik_tip = $('.imoje-blik-tip'),
		ms_blik_submit = 90000,
		ms_redirect = 5000,
		ms_speed_scroll = 2000,
		ms_check_payment = 2000,
		dont_processing = false,
		dont_processing_timeout = null,
		transaction_status_pending = 'pending',
		transaction_status_settled = 'settled',
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
		$.ajax({
			data:   {
				action:           action_imoje_check_transaction,
				method:           method_imoje_check_transaction,
				transaction_uuid: config.transaction_uuid,
				imoje_nonce:      config.nonce,
				order_id:         config.order_id,
				order_key:        config.order_key,
			},
			method: method_post,
			url:    config.ajax_url,
		})
			.then(function (response) { // done

				if (!response.success) {

					append_result_text(config.i18n.error);
					return;
				}

				if (response.data.error) {

					append_result_text(response.data.error);
					return;
				}

				if (response.data.transaction.status === transaction_status_pending) {

					if (dont_processing) {
						$('.imoje-icon-info').hide();

						append_result_text(config.i18n.timeout);
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
					append_result_text(config.i18n.settled);

					setTimeout(
						function () {
							window.location.href = config.return_url;
						}, ms_redirect)
				}
			});
	}

})(jQuery);
