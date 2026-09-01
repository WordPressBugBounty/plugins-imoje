<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Imoje\Payment\Api;
use Imoje\Payment\Installments;
use Imoje\Payment\Util;

/**
 * Class WC_Gateway_INGPayInstallments
 */
class WC_Gateway_INGPayInstallments extends WC_Gateway_INGPay_Api_Abstract {

	/**
	 *
	 */
	const PAYMENT_METHOD_NAME = 'imoje_installments';

	/**
	 * @inerhitDoc
	 */
	protected function prepare_data( WC_Order $order, $payment_method = '', $payment_method_channel = '', $installments_period = 0 ) {

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
			$installments_period,
			$this->version
		);
	}

	/**
	 * @return void
	 */
	public function payment_fields() {
		parent::payment_fields();

		if ( $this->render_calculator() ) {
			$this->render_regulations();
		}
	}

	/**
	 * @return Installments
	 */
	private function get_installments_instance() {

		$options = imoje_get_options( static::PAYMENT_METHOD_NAME );

		return new Installments(
			$this->get_option( 'merchant_id' ),
			$this->get_option( 'service_id' ),
			is_array( $options ) && isset( $options['service_key'] )
				? $options['service_key']
				: $this->get_option( 'service_key' ),
			$this->sandbox ? Util::ENVIRONMENT_SANDBOX : Util::ENVIRONMENT_PRODUCTION
		);
	}

	/**
	 *
	 * @return array
	 */
	private function fetch_installments_data() {

		if (!WC()->cart){
			return [];
		}

		$total = WC()->cart->get_total( 'edit' );

		if ( ! $total ) {

			$order_key = isset( $_GET['key'] )
				? wc_clean( wp_unslash( $_GET['key'] ) )
				: '';
			$order     = wc_get_order( absint( get_query_var( 'order-pay' ) ) );

			if ( $order && $order->get_order_key() === $order_key ) {
				$total = $order->get_total();
			}
		}

		$installments = $this->get_installments_instance();

		$installments_data = $installments->getData(
			$total,
			get_woocommerce_currency()
		);

		$installments_data['url'] = $installments->getScriptUrl();

		return $installments_data;
	}

	/**
	 *
	 * @return bool
	 */
	private function render_calculator() {

		$this->imoje_service = $this->get_service_active();

		if ( ! $this->imoje_service ) {
			$this->render_unavailable_template();
			return false;
		}

		load_template( dirname( __DIR__ ) . '/templates/installments.php', false, [
			'installments_data' => $this->fetch_installments_data(),
		] );

		return true;
	}

	/**
	 *
	 * @return array
	 */
	public function get_calculator_data() {
		return $this->fetch_installments_data();
	}
}
