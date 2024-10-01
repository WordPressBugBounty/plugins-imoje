<?php

use Imoje\Payment\Api;
use Imoje\Payment\Util;

/**
 * Class WC_Gateway_ImojeBlik
 */
class WC_Gateway_ImojeBlik extends WC_Gateway_Imoje_Api_Abstract {

	protected $blik0 = false;

	/**
	 *
	 */
	const PAYMENT_METHOD_NAME = 'imoje_blik';

	/**
	 * Constructor
	 */
	public function __construct() {

		parent::__construct();

		if ( ! $this->sandbox ) {
			add_action( 'woocommerce_receipt_' . $this->id, [
				$this,
				'receipt_page',
			] );
		}

		$this->blik0 = Helper::check_is_config_value_selected( $this->get_option( 'view_field' ) );
	}

	/**
	 * @param int $order_id
	 *
	 * @return array|bool
	 */
	public function process_payment( $order_id ) {

		if ( ! $this->blik0 ) {
			return parent::process_payment( $order_id );
		}

		$customer_notice_error = __( 'Payment error. Contact with shop administrator.', 'imoje' );

		$order = wc_get_order( $order_id );

		if ( ! $order || ! $order->get_id() ) {

			wc_add_notice( $customer_notice_error, 'error' );

			return false;
		}

		$post_imoje_blik_code = '';

		if ( isset( $_POST['imoje-blik-code'] ) && $_POST['imoje-blik-code'] ) {
			$post_imoje_blik_code = sanitize_text_field( $_POST['imoje-blik-code'] );
		}

		$pm  = Util::getPaymentMethod( 'blik' );
		$pmc = Util::getPaymentMethodCode( 'blik' );

		if ( ! $post_imoje_blik_code
		     || ! preg_match( '/^[0-9]{6}$/i', $post_imoje_blik_code )
		     || ! $this->check_availability( $order->get_total(), $pm, $pmc ) ) {
			wc_add_notice( $customer_notice_error, 'error' );

			return false;
		}

		$transaction = $this->create_transaction_and_process_order( wc_get_order( $order_id ), $pm, $pmc );

		if ( empty( $transaction['transaction']['id'] ) ) {
			wc_add_notice( $customer_notice_error, 'error' );

			return false;
		}

		$order->update_meta_data( 'imoje_transaction_uuid', $transaction['transaction']['id'] );
		$order->save();

		return [
			'result'   => 'success',
			'redirect' => $this->sandbox
				? $transaction['action']['url']
				: $order->get_checkout_payment_url( true ),
		];
	}

	/**
	 * @param WC_Order $order
	 * @param string   $payment_method
	 * @param string   $payment_method_channel
	 *
	 * @return string
	 */
	protected function prepare_data( $order, $payment_method = '', $payment_method_channel = '' ) {

		$blik_code = '';

		if ( $this->blik0 ) {

			$post_imoje_blik_code = '';

			if ( isset( $_POST['imoje-blik-code'] ) && $_POST['imoje-blik-code'] ) {
				$post_imoje_blik_code = sanitize_text_field( $_POST['imoje-blik-code'] );
			}

			if ( $post_imoje_blik_code
			     && preg_match( '/^[0-9]{6}$/i', $post_imoje_blik_code ) ) {

				$blik_code = $post_imoje_blik_code;
			}
		}

		$return_url = $this->get_return_url( $order );

		return $this->imoje_api->prepareData(
			$order->get_total(),
			$order->get_currency(),
			$order->get_id(),
			Util::getPaymentMethod( 'blik' ),
			Util::getPaymentMethodCode( 'blik' ),
			$return_url,
			$return_url,
			$order->get_billing_first_name(),
			$order->get_billing_last_name(),
			$order->get_billing_email(),
			$this->get_notification_url(),
			Api::TRANSACTION_TYPE_SALE,
			'',
			$blik_code,
			[],
			'',
			$this->get_invoice( $order )
		);
	}

	/**
	 * @param string $order_id
	 */
	public function receipt_page( $order_id ) {

		header( "Cache-Control: no-cache, no-store, must-revalidate" );

		global $wp_query;

		$order = wc_get_order( $order_id );

		$wp_query->query_vars['imoje_transaction_id']   = $order->get_meta( 'imoje_transaction_uuid' );
		$wp_query->query_vars['imoje_order_return_url'] = $this->get_return_url( $order );

		load_template( dirname( __DIR__ ) . '/templates/blik/check.php', false );
	}

	/**
	 * @inheritDoc
	 */
	public function init_form_fields() {

		parent::default_form_fields_merge( [
			'view_field' => [
				'title'   => __( 'Display field', 'imoje' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'label'   => __( 'Display field on the checkout', 'imoje' ),
			],
		] );
	}

	/**
	 * @return void
	 */
	public function payment_fields() {
		parent::payment_fields();

		if ( $this->render_channels( [ Util::getPaymentMethod( 'blik' ) ], true ) ) {

			$this->render_regulations();
		}
	}
}
