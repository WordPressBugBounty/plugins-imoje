<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Gateway_INGPayBlik_Blocks extends WC_Gateway_INGPay_Abstract_Blocks {

	/**
	 * @var string
	 */
	protected $name = 'imoje_blik';

	/**
	 * @return array
	 */
	protected function extra_data() {

		$gateway = $this->get_gateway( 'WC_Gateway_INGPayBlik' );

		return [
			'payment_methods' => $gateway->get_payment_channels(),
			'logo_oneclick'   => $this->get_image_src( 'imoje_blik.png' ),
			'blik_text'       => __( 'Enter BLIK code', 'imoje' ),
		];
	}
}
