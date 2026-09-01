<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Imoje\Payment\Api;
use Imoje\Payment\Util;

/**
 * Class WC_Gateway_INGPayBlik
 */
class WC_Gateway_INGPayBlik extends WC_Gateway_INGPay_Api_Abstract {

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

		$this->blik0 = INGPay_Helper::check_is_config_value_selected( $this->get_option( 'view_field' ) );
	}

	/**
	 * @param int $order_id
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		if ( ! $this->blik0 ) {
			return parent::process_payment( $order_id );
		}

		$customer_notice_error = __( 'Payment error. Contact with shop administrator.', 'imoje' );

		$order = wc_get_order( $order_id );

		if ( ! $order || ! $order->get_id() ) {

			wc_add_notice( $customer_notice_error, 'error' );

			return [
				'result' => 'failure',
			];
		}

		$post_imoje_blik_code = '';

		if ( isset( $_POST['imoje-blik-code'] ) && $_POST['imoje-blik-code'] ) {
			$post_imoje_blik_code = sanitize_text_field( wp_unslash( $_POST['imoje-blik-code'] ) );
		}

		$pm  = Util::getPaymentMethod( 'blik' );
		$pmc = 'blik';

		if ( ! $post_imoje_blik_code
		     || ! preg_match( '/^[0-9]{6}$/i', $post_imoje_blik_code )
		     || ! $this->check_availability( $order->get_total(), $pm, $pmc ) ) {

			wc_add_notice( $customer_notice_error, 'error' );

			return [
				'result' => 'failure',
			];
		}

		$transaction = $this->create_transaction_and_process_order( wc_get_order( $order_id ), $pm, $pmc );

		if ( empty( $transaction['transaction']['id'] ) ) {

			wc_add_notice( $customer_notice_error, 'error' );

			return [
				'result' => 'failure',
			];
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
	 * @inerhitDoc
	 */
	protected function prepare_data( WC_Order $order, $payment_method = '', $payment_method_channel = '' ) {

		$blik_code = '';

		if ( $this->blik0 ) {

			$post_imoje_blik_code = '';

			if ( isset( $_POST['imoje-blik-code'] ) && $_POST['imoje-blik-code'] ) {
				$post_imoje_blik_code = sanitize_text_field( wp_unslash( $_POST['imoje-blik-code'] ) );
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
			'blik',
			$return_url,
			$return_url,
			$order->get_billing_first_name(),
			$order->get_billing_last_name(),
			$order->get_billing_email(),
			self::get_notification_url(),
			Api::TRANSACTION_TYPE_SALE,
			'',
			$blik_code,
			[],
			'',
			$this->get_invoice( $order ),
			0,
			$this->version
		);
	}

	/**
	 * @param string $order_id
	 */
	public function receipt_page( $order_id ) {

		header( "Cache-Control: no-cache, no-store, must-revalidate" );

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$handle = 'imoje-blik-check-js';

		wp_enqueue_script(
			$handle,
			plugins_url( '/assets/js/imoje-blik-check.js', WOOCOMMERCE_IMOJE_PLUGIN_FILE_DIR ),
			[ 'jquery' ],
			$this->version,
			true
		);

		wp_localize_script( $handle, 'imoje_blik_check_object', [
			'ajax_url'         => admin_url( 'admin-ajax.php' ),
			'nonce'            => wp_create_nonce( 'imoje_ajax_nonce' ),
			'transaction_uuid' => (string) $order->get_meta( 'imoje_transaction_uuid' ),
			'order_id'         => $order->get_id(),
			'order_key'        => $order->get_order_key(),
			'return_url'       => $this->get_return_url( $order ),
			'i18n'             => [
				'error'   => __( 'Something went wrong. Please contact with shop staff', 'imoje' ),
				'timeout' => __( 'Response timed out. Create new order or contact with shop staff.', 'imoje' ),
				'settled' => __( 'Your payment was correctly processed, you will be informed about next steps.', 'imoje' ),
			],
		] );

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

	/**
	 * @return array
	 */
	public function get_payment_channels() {
		return $this->prepare_payment_methods_block_checkout( ['blik'] );
	}
}
