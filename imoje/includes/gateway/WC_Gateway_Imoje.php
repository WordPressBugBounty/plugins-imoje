<?php

use Imoje\Payment\Util;

/**
 * Class WC_Gateway_Imoje
 */
class WC_Gateway_Imoje extends WC_Gateway_Imoje_Api_Abstract {

	/**
	 *
	 */
	const PAYMENT_METHOD_NAME = 'imoje';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * @inheritDoc
	 */
	public function init_form_fields() {

		parent::init_form_fields_paywall();
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	public function prepare_data( $order, $payment_method = '', $payment_method_channel = '' ) {

		$return_url = $this->get_return_url( $order );

		return $this->imoje_api->prepareDataPaymentLink(
			$order->get_total(),
			$order->get_currency(),
			$order->get_id(),
			wc_get_checkout_url(),
			$return_url,
			$return_url,

			$order->get_billing_first_name(),
			$order->get_billing_last_name(),
			$order->get_billing_email(),
			$this->get_notification_url(),
			$order->get_billing_phone()
				?: '',
			[],
			Helper::get_lease_now( $order, $this->get_option( 'ing_lease_now' ), true ),
			Helper::get_invoice(
				$order,
				$this->get_option( 'ing_ksiegowosc' ),
				true,
				$this->get_option( 'ing_ksiegowosc_meta_tax' )
			)
		);
	}
}
