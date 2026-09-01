<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Imoje\Payment\Api;
use Imoje\Payment\Util;

/**
 * Class WC_Gateway_INGPayTwisto
 */
class WC_Gateway_INGPayPaylater extends WC_Gateway_INGPay_Api_Abstract {

	/**
	 *
	 */
	const PAYMENT_METHOD_NAME = 'imoje_paylater';

	/**
	 * @inerhitDoc
	 */
	protected function prepare_data( WC_Order $order, $payment_method = '', $payment_method_channel = '' ) {

		$return_url = $this->get_return_url( $order );

		return $this->imoje_api->prepareData(
			$order->get_total(),
			$order->get_currency(),
			$order->get_id(),
			$payment_method,
			$payment_method_channel,
			$return_url,
			$return_url,
			$order->get_billing_first_name(),
			$order->get_billing_last_name(),
			$order->get_billing_email(),
			self::get_notification_url(),
			Api::TRANSACTION_TYPE_SALE,
			'',
			'',
			parent::get_address_data( $order ),
			'',
			parent::get_invoice( $order ),
			0,
			$this->version
		);
	}

	/**
	 * @return void
	 */
	public function payment_fields() {
		parent::payment_fields();

		if ( $this->render_channels( [ Util::getPaymentMethod( 'imoje_paylater' ) ] ) ) {

			$this->render_regulations();

			$this->render_twisto_regulations();
		}
	}
}
