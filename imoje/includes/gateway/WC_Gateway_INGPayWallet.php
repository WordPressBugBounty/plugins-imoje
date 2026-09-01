<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Imoje\Payment\Api;
use Imoje\Payment\Util;

/**
 * Class WC_Gateway_INGPayWallet
 */
class WC_Gateway_INGPayWallet extends WC_Gateway_INGPay_Api_Abstract {

	/**
	 *
	 */
	const PAYMENT_METHOD_NAME = 'imoje_wallet';

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
			[],
			'',
			$this->get_invoice( $order ),
			0,
			$this->version
		);
	}

	/**
	 * @return void
	 */
	public function payment_fields() {
		parent::payment_fields();

		if ( $this->render_channels( [ Util::getPaymentMethod( 'wallet' ) ] ) ) {
			$this->render_regulations();
		}
	}

	/**
	 * @return array
	 */
	public function get_payment_channels() {
		return $this->prepare_payment_methods_block_checkout( [ Util::getPaymentMethod( 'wallet' ) ] );
	}
}
