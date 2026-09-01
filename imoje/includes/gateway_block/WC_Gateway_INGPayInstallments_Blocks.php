<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Gateway_INGPayInstallments_Blocks extends WC_Gateway_INGPay_Abstract_Blocks {

	/**
	 * @var string
	 */
	protected $name = 'imoje_installments';

	/**
	 * @return array
	 */
	protected function extra_data() {

		$gateway = $this->get_gateway( 'WC_Gateway_INGPayInstallments' );

		return [
			'calculator_data' => $gateway->get_calculator_data(),
		];
	}
}
