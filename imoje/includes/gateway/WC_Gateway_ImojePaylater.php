<?php

use Imoje\Payment\Api;
use Imoje\Payment\Util;

/**
 * Class WC_Gateway_ImojeTwisto
 */
class WC_Gateway_ImojePaylater extends WC_Gateway_Imoje_Api_Abstract {

	/**
	 *
	 */
	const PAYMENT_METHOD_NAME = 'imoje_paylater';

	/**
	 * @param WC_Order $order
	 * @param string   $payment_method
	 * @param string   $payment_method_channel
	 *
	 * @return string
	 */
	protected function prepare_data( $order, $payment_method = '', $payment_method_channel = '' ) {

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
			$this->get_notification_url(),
			Api::TRANSACTION_TYPE_SALE,
			'',
			'',
			parent::get_address_data( $order ),
			'',
			parent::get_invoice( $order )
		);
	}

	/**
	 * @return void
	 */
	public function payment_fields() {
		parent::payment_fields();

		if($this->render_channels( [ Util::getPaymentMethod( 'imoje_paylater' ) ] )){

			$this->render_regulations();

			$this->render_twisto_regulations();
		}

	}
}
