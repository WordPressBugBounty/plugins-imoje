<?php

use Imoje\Payment\Util;

/**
 * Class WC_Gateway_ImojeCards
 */
class WC_Gateway_ImojeVisa extends WC_Gateway_Imoje_Api_Abstract {

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
			[ Util::getPaymentMethod( 'card' ) ],
			Helper::get_lease_now( $order, $this->get_option( 'ing_lease_now' ), true ),
			Helper::get_invoice(
				$order,
				$this->get_option( 'ing_ksiegowosc' ),
				true,
				$this->get_option( 'ing_ksiegowosc_meta_tax' )
			),
			self::PRESELECT_METHOD_CODE
		);
	}
}
