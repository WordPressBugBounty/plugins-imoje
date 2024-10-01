<?php

use Imoje\Payment\Api;
use Imoje\Payment\Installments;
use Imoje\Payment\Util;

/**
 * Class WC_Gateway_ImojeInstallments
 */
class WC_Gateway_ImojeInstallments extends WC_Gateway_Imoje_Api_Abstract {

	/**
	 *
	 */
	const PAYMENT_METHOD_NAME = 'imoje_installments';

	/**
	 * @param WC_Order $order
	 * @param string   $payment_method
	 * @param string   $payment_method_channel
	 *
	 * @return string
	 */
	protected function prepare_data( $order, $payment_method = '', $payment_method_channel = '', $installments_period = 0 ) {

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
			parent::get_invoice( $order ),
			$installments_period
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
	 * @return bool
	 */
	private function render_calculator() {

		global $wp_query;

		$this->imoje_service = $this->get_service_active();

		if ( ! $this->imoje_service ) {
			$this->render_unavailable_template();

			return false;
		}

		$installments = new Installments(
			$this->get_option( 'merchant_id' ),
			$this->get_option( 'service_id' ),
			$this->get_option( 'service_key' ),
			$this->sandbox
				? Util::ENVIRONMENT_SANDBOX
				: Util::ENVIRONMENT_PRODUCTION
		);

		$installments_data = $installments->getData(
			WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax(),
			get_woocommerce_currency()
		);

		$installments_data['url'] = $installments->getScriptUrl();

		$wp_query->query_vars['installments_data'] = $installments_data;

		load_template( dirname( __DIR__ ) . '/templates/installments.php', false );

		return true;
	}
}
