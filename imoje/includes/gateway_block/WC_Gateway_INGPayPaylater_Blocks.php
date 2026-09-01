<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Gateway_INGPayPaylater_Blocks extends WC_Gateway_INGPay_Abstract_Blocks {

	/**
	 * @var string
	 */
	protected $name = 'imoje_paylater';

	/**
	 * @return array
	 */
	protected function extra_data() {

		$gateway = $this->get_gateway( 'WC_Gateway_INGPayPaylater' );

		$twisto_template = dirname( __DIR__ ) . '/templates/twisto/regulation.php';
		$twisto_legal    = '';

		if ( file_exists( $twisto_template ) ) {
			ob_start();
			load_template( $twisto_template, false );
			$twisto_legal = ob_get_clean();
		}

		return [
			'payment_methods' => $gateway->get_payment_channels(),
			'twisto_legal'    => $twisto_legal,
		];
	}
}
