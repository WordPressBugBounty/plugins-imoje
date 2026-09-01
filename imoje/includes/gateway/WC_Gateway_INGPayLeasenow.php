<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC_Gateway_INGPayLease
 */
class WC_Gateway_INGPayLeasenow extends WC_Gateway_INGPay_Api_Abstract {

	/**
	 *
	 */
	const PAYMENT_METHOD_NAME = 'imoje_leasenow';

	/**
	 * @const string
	 */
	const PAYMENT_METHOD_CODE = 'lease_now';

	/**
	 * @const string
	 */
	const PRODUCT_MIN_AMOUNT_CURRENCY = 'PLN';

	/**
	 * @return bool
	 */
	public function process_admin_options() {

		$saved = parent::process_admin_options();

		if ( $saved ) {
			$this->refresh_product_min_amount();
		}

		return $saved;
	}

	/**
	 * @return void
	 */
	protected function refresh_product_min_amount() {

		$this->init_settings();
		$this->sandbox = INGPay_Helper::check_is_config_value_selected( $this->get_option( 'sandbox' ) );
		$this->build_api();

		if ( ! $this->get_option( 'authorization_token' )
		     || ! $this->get_option( 'merchant_id' )
		     || ! $this->get_option( 'service_id' ) ) {

			return;
		}

		$service_info = $this->imoje_api->getServiceInfo();

		if ( empty( $service_info['success'] ) ) {

			WC_Admin_Settings::add_error(
				$this->get_payment_method_data( 'display_name' ) . ': ' . __( 'Inserted invalid credentials', 'imoje' )
			);

			wc_get_logger()->error(
				$this->get_payment_method_data( 'display_name' ) . ' | ' . __( 'Could not fetch service info: ', 'imoje' )
				. INGPay_Helper::format_api_error( $service_info ),
				[ 'source' => 'imoje' ]
			);

			return;
		}

		$payment_methods = isset( $service_info['body']['service']['paymentMethods'] )
		                   && is_array( $service_info['body']['service']['paymentMethods'] )
			? $service_info['body']['service']['paymentMethods']
			: [];

		foreach ( $payment_methods as $payment_method ) {

			if ( ! isset(
				$payment_method['paymentMethodCode'],
				$payment_method['currency'],
				$payment_method['transactionLimits']['minTransaction']['value']
			)
			     || $payment_method['paymentMethodCode'] !== self::PAYMENT_METHOD_CODE
			     || $payment_method['currency'] !== self::PRODUCT_MIN_AMOUNT_CURRENCY ) {

				continue;
			}

			$this->update_option(
				'product_min_amount',
				(int) $payment_method['transactionLimits']['minTransaction']['value']
			);

			return;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function init_form_fields() {

		$options = [
			'description'       => __( 'Insert 0 if you want to disable', 'imoje' ),
			'type'              => 'number',
			'default'           => '0',
			'custom_attributes' => [ 'max' => 100, 'min' => 0 ],
		];

		$scaleListText    = __( 'Scale image as percentage on product list', 'imoje' );
		$scaleProductText = __( 'Scale image as percentage on product page', 'imoje' );

		parent::default_form_fields_merge( [
			'image_scale_list'    => array_merge( $options, [
				'title' => $scaleListText,
				'label' => $scaleListText,
			] ),
			'image_scale_product' => array_merge( $options, [
				'title' => $scaleProductText,
				'label' => $scaleProductText,
			] ),
		] );
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
			[],
			INGPay_Helper::get_lease_now( $order, 'yes' ),
			INGPay_Helper::get_invoice(
				$order,
				$this->get_option( 'ing_ksiegowosc' ),
				$this->get_option( 'ing_ksiegowosc_meta_tax' )
			),
			self::PAYMENT_METHOD_CODE,
			$this->version
		);
	}

}
