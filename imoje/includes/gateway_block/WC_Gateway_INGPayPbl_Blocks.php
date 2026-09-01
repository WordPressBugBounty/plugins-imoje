<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Gateway_INGPayPbl_Blocks extends WC_Gateway_INGPay_Abstract_Blocks {

	/**
	 * @var string
	 */
	protected $name = 'imoje_pbl';

	/**
	 * @return array
	 */
	protected function extra_data() {

		$gateway = $this->get_gateway( 'WC_Gateway_INGPayPbl' );

		return [
			'payment_methods' => $gateway->get_payment_channels(),
		];
	}
}
