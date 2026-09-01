<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Imoje\Payment\Util;

/**
 * Class WC_Gateway_INGPayVisa
 */
class WC_Gateway_INGPayVisa extends WC_Gateway_INGPay_Api_Abstract {

	/**
	 *
	 */
	const PAYMENT_METHOD_NAME = 'imoje_visa';

	/**
	 *
	 */
	const PRESELECT_METHOD_CODE = 'visa_mobile';

	/**
	 * @inheritDoc
	 */
	public function init_form_fields() {

		parent::default_form_fields_merge();
	}

	/**
	 * @inerhitDoc
	 */
	public function prepare_data( WC_Order $order, $payment_method = '', $payment_method_channel = '' ) {

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
			self::get_notification_url(),
			$order->get_billing_phone()
				?: '',
			[ Util::getPaymentMethod( 'wallet' ) ],
			INGPay_Helper::get_lease_now( $order, $this->get_option( 'ing_lease_now' ) ),
			INGPay_Helper::get_invoice(
				$order,
				$this->get_option( 'ing_ksiegowosc' ),
				$this->get_option( 'ing_ksiegowosc_meta_tax' )
			),
			self::PRESELECT_METHOD_CODE,
			$this->version
		);
	}
}
