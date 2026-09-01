<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Gateway_INGPayWallet_Blocks extends WC_Gateway_INGPay_Abstract_Blocks {

	/**
	 * @var string
	 */
	protected $name = 'imoje_wallet';

	/**
	 * @return array
	 */
	protected function extra_data() {

		$gateway = $this->get_gateway( 'WC_Gateway_INGPayWallet' );

		return [
			'payment_methods' => $gateway->get_payment_channels(),
		];
	}
}
