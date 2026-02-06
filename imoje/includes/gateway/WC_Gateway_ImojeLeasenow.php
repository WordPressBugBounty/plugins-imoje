<?php

use Imoje\Payment\Util;

/**
 * Class WC_Gateway_ImojeLeasenow
 */
class WC_Gateway_ImojeLeasenow extends WC_Gateway_Imoje_Api_Abstract {

	/**
	 *
	 */
	const PAYMENT_METHOD_NAME = 'imoje_leasenow';

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
			self::get_notification_url(),
			$order->get_billing_phone()
				?: '',
			[],
			Imoje_Helper::get_lease_now( $order, 'yes', true ),
			Imoje_Helper::get_invoice(
				$order,
				$this->get_option( 'ing_ksiegowosc' ),
				true,
				$this->get_option( 'ing_ksiegowosc_meta_tax' )
			),
			Util::getPaymentMethodCode( 'lease_now' ),
			$this->version
		);
	}

}
