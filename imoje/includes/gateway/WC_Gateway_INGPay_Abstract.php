<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Imoje\Payment\Api;
use Imoje\Payment\Util;
use Imoje\Payment\Notification;

/**
 * Class WC_Gateway_INGPay_Abstract
 */
abstract class WC_Gateway_INGPay_Abstract extends WC_Payment_Gateway {

	const PROCESSED_REFUNDS_META_KEY = 'imoje_processed_refund_uuids';

	/**
	 * @var string
	 */
	protected $payment_method_name;

	/**
	 * @var string
	 */
	protected $sandbox;

	/**
	 * Constructor
	 */
	public function __construct( $id ) {

		$this->payment_method_name = $id;

		$this->setup_properties();

		$this->init_form_fields();
		$this->init_settings();

		// region actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [
			$this,
			'process_admin_options',
		] );

		foreach ( $this->get_notification_endpoints() as $endpoint ) {
			add_action( 'woocommerce_api_' . $endpoint, [
				$this,
				'process_notification',
			] );
		}

		add_action( 'wp_enqueue_scripts', [
			$this,
			'enqueue_scripts',
		] );

		// endregion

		// region filters
		add_filter( 'woocommerce_available_payment_gateways', [
			$this,
			'check_available_payment_gateways',
		] );
		// endregion
	}

	/**
	 * @return array
	 */
	protected function get_notification_endpoints() {

		$current = strtolower( get_class( $this ) );

		$suffix = str_replace( WC_Gateway_INGPay::PAYMENT_METHOD_NAME, '', $this->payment_method_name );

		$legacy_suffixes = [
			'_leasenow' => '_lease',
		];

		$suffixes = [ $suffix ];

		if ( isset( $legacy_suffixes[ $suffix ] ) ) {
			$suffixes[] = $legacy_suffixes[ $suffix ];
		}

		$endpoints = [
			$current,
			str_replace( 'ingpay', 'imoje', $current ),
		];

		foreach ( $suffixes as $legacy_suffix ) {
			$endpoints[] = 'wc_gateway_ingpay' . $legacy_suffix;
			$endpoints[] = 'wc_gateway_imoje' . $legacy_suffix;
		}

		return array_values( array_unique( $endpoints ) );
	}

	/**
	 * Setup general properties for the gateway.
	 */
	protected function setup_properties() {
		$this->id                 = $this->payment_method_name;
		$this->method_title       = $this->get_payment_method_data( 'name' );
		$this->method_description = __( 'ING Pay payments', 'imoje' );
		$this->sandbox            = INGPay_Helper::check_is_config_value_selected( $this->get_option( 'sandbox' ) );
		$this->has_fields         = false;
		$this->supports           = [
			'products',
			'refunds',
		];
		$this->description        = $this->get_option( 'description', ' ' );

		$this->title = $this->get_option( 'title' );

		$this->icon = INGPay_Helper::check_is_config_value_selected( $this->get_option( 'hide_brand' ) )
			? null
			: WOOCOMMERCE_IMOJE_PLUGIN_URL . 'assets/images/' . $this->payment_method_name . '.' . INGPay_Helper::get_logo_ext( $this->payment_method_name );
	}

	/**
	 * @param string $field
	 *
	 * @return string
	 */
	public function get_payment_method_data( $field ) {

		return self::payment_method_list()[ $this->id ][ $field ];
	}

	/**
	 * @return array
	 */
	public static function payment_method_list() {

		return [
			WC_Gateway_INGPay::PAYMENT_METHOD_NAME              => INGPay_Helper::get_gateway_details(
				__( 'ING Pay - paywall', 'imoje' ),
				__( 'BLIK, cards, online transfers', 'imoje' ),
				__( 'You will be redirected to a payment method selection page', 'imoje' ),
				true
			),
			WC_Gateway_INGPayPbl::PAYMENT_METHOD_NAME          => INGPay_Helper::get_gateway_details(
				__( 'ING Pay - PBL', 'imoje' ),
				__( 'Online transfer', 'imoje' ),
				__( 'Choose payment channel and pay via ING Pay.', 'imoje' ),
				false
			),
			WC_Gateway_INGPayBlik::PAYMENT_METHOD_NAME         => INGPay_Helper::get_gateway_details(
				__( 'ING Pay - BLIK', 'imoje' ),
				__( 'BLIK', 'imoje' ),
				__( 'Pay with BLIK via ING Pay', 'imoje' ),
				false
			),
			WC_Gateway_INGPayCards::PAYMENT_METHOD_NAME        => INGPay_Helper::get_gateway_details(
				__( 'ING Pay - payment cards', 'imoje' ),
				__( 'Payment cards', 'imoje' ),
				__( 'Pay with card via ING Pay', 'imoje' ),
				true
			),
			WC_Gateway_INGPayVisa::PAYMENT_METHOD_NAME         => INGPay_Helper::get_gateway_details(
				__( 'ING Pay - Visa Mobile', 'imoje' ),
				__( 'Visa Mobile', 'imoje' ),
				__( 'Visa Mobile payment with ING Pay.', 'imoje' ),
				true
			),
			WC_Gateway_INGPayPaylater::PAYMENT_METHOD_NAME     => INGPay_Helper::get_gateway_details(
				__( 'ING Pay - pay later', 'imoje' ),
				__( 'Pay later', 'imoje' ),
				__( 'Buy now, pay later via ING Pay', 'imoje' ),
				false
			),
			WC_Gateway_INGPayInstallments::PAYMENT_METHOD_NAME => INGPay_Helper::get_gateway_details(
				__( 'ING Pay - installments', 'imoje' ),
				__( 'Installments', 'imoje' ),
				__( 'Installments', 'imoje' ),
				false
			),
			WC_Gateway_INGPayWallet::PAYMENT_METHOD_NAME       => INGPay_Helper::get_gateway_details(
				__( 'ING Pay - wallet', 'imoje' ),
				__( 'Wallet', 'imoje' ),
				__( 'Pay with wallet via ING Pay', 'imoje' ),
				false
			),
			WC_Gateway_INGPayWt::PAYMENT_METHOD_NAME           => INGPay_Helper::get_gateway_details(
				__( 'ING Pay - wire transfer', 'imoje' ),
				__( 'Wire transfer', 'imoje' ),
				__( 'Wire transfer payment with ING Pay', 'imoje' ),
				true
			),
			WC_Gateway_INGPayLeasenow::PAYMENT_METHOD_NAME        => INGPay_Helper::get_gateway_details(
				__( 'ING Pay - Lease Now', 'imoje' ),
				__( 'ING Lease Now', 'imoje' ),
				__( 'Pay with ING Lease Now via ING Pay', 'imoje' ),
				true
			),
		];
	}

	/**
	 * @param float  $cart_total
	 * @param string $payment_method
	 *
	 * @return bool
	 */
	abstract protected function verify_availability(
		$cart_total,
		$payment_method = ''
	);

	/**
	 * @param WC_Order $order
	 * @param string   $payment_method
	 * @param string   $payment_method_channel
	 *
	 * @return string
	 */
	abstract protected function prepare_data(
		WC_Order $order,
		$payment_method = '',
		$payment_method_channel = ''
	);

	/**
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	protected function get_invoice( WC_Order $order ) {
		return INGPay_Helper::get_invoice(
			$order,
			$this->get_option( 'ing_ksiegowosc' ),
			$this->get_option( 'ing_ksiegowosc_meta_tax' )
		);
	}

	/**
	 * @return string
	 */
	public static function get_notification_url() {
		return add_query_arg( 'wc-api', strtolower( static::class ), home_url( '/' ) );
	}

	/**
	 * @return void
	 */
	protected function init_form_fields_paywall() {

		$this->default_form_fields_merge( [
			'ing_lease_now' => [
				'title'   => __( 'ING Lease Now', 'imoje' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'label'   => __( 'Enable', 'imoje' ),
			],
		] );
	}

	/**
	 * @param array $config
	 *
	 * @return void
	 */
	protected function default_form_fields_merge( array $config = array() ) {
		$this->form_fields = array_merge( $this->get_default_form_fields(), $config );
	}

	/**
	 * @return void
	 */
	public function init_form_fields() {

		$this->form_fields = $this->get_default_form_fields();
	}

	/**
	 * @return array[]
	 */
	public function get_default_form_fields() {

		$gateway_details = self::payment_method_list()[ $this->payment_method_name ];

		return [
			'sandbox_hint' => [
				'title'   => ( $this->get_option( 'sandbox' ) === "yes" )
					? __( 'Sandbox is enabled', 'imoje' )
					: '',
				'type'    => 'title',
				'default' => '',
			],
			'hint'         => [
				'title'       => __( 'Hint', 'imoje' ),
				'default'     => '',
				'class'       => 'hidden',
				'type'        => 'title',
				'description' => __( 'The module requires a configuration with your shop in the ING Pay administration panel. <br/> Go to <b><a href="https://pay.ing.pl">pay.ing.pl</a></b> and log in to the administration panel. <br/> Then go to <b>Shops>your shop name>Details>Data for integration</b> and copy <b>Merchant ID</b>, <b>Shop ID</b>, <b>Shop key</b>, <b>Authorization token</b> into the fields described below.', 'imoje' ),
			],
			'enabled'      => [
				'title'   => __( 'Enable / Disable', 'imoje' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable', 'imoje' ),
				'default' => 'no',
			],
			'sandbox'      => [
				'title'       => __( 'Sandbox', 'imoje' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'label'       => __( 'Enable sandbox', 'imoje' ),
				'description' => __( 'In order to use sandbox mode, you must create an account in a dedicated <b><a href="https://sandbox.pay.ing.pl">sandbox environment</a></b>', 'imoje' ),
			],

			'title'                   => [
				'title'   => __( 'Payment title', 'imoje' ),
				'type'    => 'text',
				'default' => $gateway_details['display_name'],
			],
			'description'             => [
				'title'       => __( 'Description', 'imoje' ),
				'type'        => 'text',
				'description' => __( 'Text that users will see on checkout', 'imoje' ),
				'default'     => $gateway_details['default_description'],
				'desc_tip'    => true,
			],
			'merchant_id'             => [
				'title'   => __( 'Merchant ID', 'imoje' ),
				'type'    => 'text',
				'default' => '',
			],
			'service_id'              => [
				'title'   => __( 'Service ID', 'imoje' ),
				'type'    => 'text',
				'default' => '',
			],
			'service_key'             => [
				'title'   => __( 'Service Key', 'imoje' ),
				'type'    => 'text',
				'default' => '',
			],
			'authorization_token'     => [
				'title'   => __( 'Authorization token', 'imoje' ),
				'type'    => 'text',
				'default' => '',
			],
			'currencies'              => [
				'title'   => __( 'Currency', 'imoje' ),
				'type'    => 'multiselect',
				'class'   => 'wc-enhanced-select',
				'default' => '',
				'options' => Util::getSupportedCurrencies(),
			],
			'ing_ksiegowosc_meta_tax' => [
				'title'       => __( 'Meta name for VAT', 'imoje' ),
				'type'        => 'text',
				'default'     => '',
				'description' => __( 'Required if you want to use ING Księgowość and create invoices with VAT ID', 'imoje' ),
			],
			'hide_brand'              => [
				'title'   => __( 'Display brand', 'imoje' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'label'   => __( 'Hide brand', 'imoje' ),
			],
			'ing_ksiegowosc'          => [
				'title'       => __( 'ING Księgowość', 'imoje' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'label'       => __( 'Enable', 'imoje' ),
				'description' => __( 'If you are entitled to a tax exemption, and you want ING Pay to send the basis for the exemption to ING Księgowość, then create a new tax class with a name starting as <b>ZW_</b> and ending with one of the available values of the <b>basisForVatExemption</b> object at the following <b><a href="https://bump.sh/pgw/doc/ing-pay-api/topic/topic-ing-ksiegowosc">link</a></b>. Example: <b>ZW_DENTAL_TECHNICAN_SERVICES</b>', 'imoje' ),
			],
			'update_method_name'      => [
				'title'   => __( 'Update method name in order details via notification', 'imoje' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'label'   => __( 'Enable', 'imoje' ),
			],
			'cancel_order'            => [
				'title'   => __( 'Allow order to be cancelled via notification', 'imoje' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'label'   => __( 'Enable', 'imoje' ),
			],
		];
	}

	/**
	 * @param int        $order_id
	 * @param null|float $amount
	 * @param string     $reason
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function process_refund(
		$order_id,
		$amount = null,
		$reason = ''
	) {

		if ( $reason === 'imoje API' ) {
			return true;
		}

		if ( $amount <= 0 ) {

			throw new Exception( __( 'Refund amount must be higher than 0', 'imoje' ) );
		}

		return INGPay_Helper::process_refund(
			$order_id,
			$this->get_option( 'authorization_token' ),
			$this->get_option( 'merchant_id' ),
			$this->get_option( 'service_id' ),
			$amount,
			INGPay_Helper::check_is_config_value_selected( $this->get_option( 'sandbox' ) )
				? Util::ENVIRONMENT_SANDBOX
				: Util::ENVIRONMENT_PRODUCTION
		);
	}

	/**
	 * @param int $order_id
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {

		$order = new WC_Order( $order_id );

		return [
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		];
	}

	/**
	 * The 'payment' object is used for general information about the payment
	 * The 'transaction' object is used for specific transaction information in situations sensitive to transaction type or status, e.g. refunds.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function process_notification() {

		$service_id  = $this->get_option( 'service_id' );
		$service_key = $this->get_option( 'service_key' );

		$notification = new Notification(
			$service_id,
			$service_key
		);

		if ( ! $service_key || ! $service_id ) {

			$this->log_verification_error(
				__( 'imoje ipn rejected: gateway is not configured (missing service id or service key)', 'imoje' )
			);

			echo $notification->formatResponse( Notification::NS_ERROR, Notification::NC_INVALID_SIGNATURE );
			exit();
		}

		// it can be order data or notification code - depends on verification notification
		$result_check_request_notification = $notification->checkRequest();

		if ( is_int( $result_check_request_notification ) ) {
			echo $notification->formatResponse( Notification::NS_ERROR, $result_check_request_notification );
			exit();
		}

		if ( ! ( $order = wc_get_order( $result_check_request_notification['payment']['orderId'] ) ) ) {

			echo $notification->formatResponse( Notification::NS_ERROR, Notification::NC_ORDER_NOT_FOUND );
			exit();
		}

		$order_status = $order->get_status();

		$order_data = $order->get_data();

		/*
		* Verifies if notification contains 'transaction' object and transaction is a refund.
		* If so, triggers the refund processing for this transaction
		*/
		if ( isset( $result_check_request_notification['transaction']['type'] )
			&& $result_check_request_notification['transaction']['type'] === Notification::TRT_REFUND ) {

			if ( $result_check_request_notification['transaction']['status'] !== Notification::TRS_SETTLED ) {
				echo $notification->formatResponse( Notification::NS_OK, Notification::NC_IMOJE_REFUND_IS_NOT_SETTLED );

				exit();
			}

			$refund_uuid   = $result_check_request_notification['transaction']['id'];
			$refund_amount = (int) $result_check_request_notification['transaction']['amount'];

			if ( $this->is_refund_processed( $order, $refund_uuid ) ) {
				echo $notification->formatResponse( Notification::NS_OK, Notification::NC_ORDER_STATUS_IS_INVALID_FOR_REFUND );
				exit();
			}

			if ( $order_status === 'refunded' ) {
				echo $notification->formatResponse( Notification::NS_OK, Notification::NC_ORDER_STATUS_IS_INVALID_FOR_REFUND );
				exit();
			}

			$remaining_to_refund = Util::convertAmountToFractional( $order_data['total'] )
			                       - Util::convertAmountToFractional( $order->get_total_refunded() );

			if ( $refund_amount <= 0 || $refund_amount > $remaining_to_refund ) {

				$this->log_verification_error( sprintf(
					__( 'imoje ipn refund %1$s rejected: amount %2$d exceeds remaining refundable amount %3$d', 'imoje' ),
					$refund_uuid,
					$refund_amount,
					$remaining_to_refund
				) );

				echo $notification->formatResponse( Notification::NS_OK, Notification::NC_AMOUNT_NOT_MATCH );
				exit();
			}

			try {
				$refund = wc_create_refund( [
					'amount'         => Util::convertAmountToMain( $refund_amount ),
					'reason'         => 'imoje API',
					'order_id'       => $order->get_id(),
					'refund_payment' => true,
				] );
			} catch( Exception $e ) {

				$this->log_verification_error( "imoje: exception during create refund: " . $e->getMessage() );
				echo $notification->formatResponse( Notification::NS_ERROR );
				exit();
			}

			if ( is_wp_error( $refund ) ) {

				$this->log_verification_error( "imoje: could not create refund: " . $refund->get_error_message() );
				echo $notification->formatResponse( Notification::NS_ERROR );
				exit();
			}

			$this->mark_refund_processed( $order, $refund_uuid );

			$order->add_order_note(
				sprintf(
					__( 'Refund for amount %1$s with UUID %2$s has been correctly processed.', 'imoje' ),
					$result_check_request_notification['transaction']['amount'],
					$refund_uuid
				)
			);

			echo $notification->formatResponse( Notification::NS_OK );
			exit;
		}

		if ( $order_status === 'completed' || $order_status === 'processing' ) {

			echo $notification->formatResponse( Notification::NS_ERROR, Notification::NC_INVALID_ORDER_STATUS );
			exit();
		}

		if ( ! Notification::checkRequestAmount(
			$result_check_request_notification,
			Util::convertAmountToFractional( $order_data['total'] ),
			$order_data['currency']
		) ) {

			echo $notification->formatResponse( Notification::NS_ERROR, Notification::NC_AMOUNT_NOT_MATCH );
			exit();
		}

		$transactionStatuses = Util::getTransactionStatuses();

		if ( ! isset( $transactionStatuses[ $result_check_request_notification['payment']['status'] ] ) ) {
			echo $notification->formatResponse( Notification::NS_ERROR, Notification::NC_UNHANDLED_STATUS );
			exit;
		}

		switch ( $result_check_request_notification['payment']['status'] ) {
			case Notification::TRS_SETTLED:

				$transaction_uuid = isset( $result_check_request_notification['transaction']['id'] )
					? $result_check_request_notification['transaction']['id']
					: '';

				$order->update_status( $order->needs_processing()
					? 'processing'
					: 'completed',
					__( 'Transaction reference', 'imoje' ) . ': ' . ( $transaction_uuid
						?: $result_check_request_notification['payment']['id'] ) );

				if ( INGPay_Helper::check_is_config_value_selected( $this->get_option( 'update_method_name' ) ) && $order->get_payment_method() !== $this->payment_method_name && $order->get_payment_method_title() !== $this->get_payment_method_data( 'display_name' ) ) {

					try {
						$order->set_payment_method( $this->payment_method_name );
						$order->set_payment_method_title( $this->get_payment_method_data( 'display_name' ) );
					} catch( Exception $e ) {
						$this->log_verification_error( 'exception during IPN update method: ' . $e->getMessage() );
					}
					$order->save();
				}

				if ( $transaction_uuid ) {
					$order->update_meta_data( 'imoje_transaction_uuid', $transaction_uuid );
					$order->save_meta_data();
				} else {

					$this->log_verification_error( sprintf(
						__( 'imoje ipn for order %s is settled but carries no transaction id - refunds for this order will not be possible', 'imoje' ),
						$order->get_id()
					) );
				}

				echo $notification->formatResponse( Notification::NS_OK );
				exit;
			case Notification::TRS_REJECTED:
				$order->update_status( 'failed' );
				$order->add_order_note( __( 'Payment reference', 'imoje' ) . ': ' . $result_check_request_notification['payment']['id'] );
				echo $notification->formatResponse( Notification::NS_OK );
				exit;
			case Notification::TRS_CANCELLED:
				if ( ! INGPay_Helper::check_is_config_value_selected( $this->get_option( 'cancel_order' ) ) ) {
					echo $notification->formatResponse(
						Notification::NS_OK,
						Notification::NC_ORDER_CANCELLATION_IS_NOT_ENABLED,
						$order_status
					);
					exit();
				}
				if ( $order_status !== 'pending' ) {
					echo $notification->formatResponse(
						Notification::NS_OK,
						Notification::NC_INVALID_ORDER_STATUS_FOR_CANCELLATION
					);
					exit();
				}
				$order->update_status( 'cancelled' );
				$order->add_order_note( __( 'Payment reference', 'imoje' ) . ': ' . $result_check_request_notification['payment']['id'] );
				echo $notification->formatResponse( Notification::NS_OK );
				exit;
			default:
				echo $notification->formatResponse( Notification::NS_OK, Notification::NC_UNHANDLED_STATUS );
				exit;
		}
	}

	/**
	 * @param array $gateways
	 *
	 * @return array
	 */
	public function check_available_payment_gateways( array $gateways ) {

		if ( ! is_checkout() || ! WC()->cart ) {
			return $gateways;
		}

		$currencies = $this->get_option( 'currencies', [] );

		if ( is_array( $currencies )
		     && INGPay_Helper::check_is_config_value_selected( $this->get_option( 'enabled' ) )
		     && in_array( strtolower( get_woocommerce_currency() ), $currencies )
		     && $this->get_option( 'service_key' )
		     && $this->get_option( 'service_id' )
		     && $this->get_option( 'merchant_id' )
		     && $this->verify_availability( WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax() )
		) {

			return $gateways;
		}

		return $this->disable_gateway( $gateways );
	}

	/**
	 * @return void
	 */
	public function enqueue_scripts() {

		if ( ! is_checkout() ) {
			return;
		}

		// only the first call has anything to do
		if ( wp_script_is( 'imoje-gateway-js', 'registered' ) ) {
			return;
		}

		$version = INGPay_Helper::get_version();

		wp_enqueue_script( 'imoje-gateway-js', plugins_url( '/assets/js/imoje-gateway.min.js', WOOCOMMERCE_IMOJE_PLUGIN_FILE_DIR ),
			[ 'jquery' ], $version, true );
		wp_enqueue_style( 'imoje-gateway-css', plugins_url( '/assets/css/imoje-gateway.min.css', WOOCOMMERCE_IMOJE_PLUGIN_FILE_DIR ),
			[], $version );

		wp_localize_script( 'imoje-gateway-js', 'imoje_js_object', [
			'imoje_blik_tooltip' => __( "You must insert exactly 6 numbers as BLIK code!", "imoje" ),
			'imoje_nonce'       => wp_create_nonce( 'imoje_ajax_nonce' ),
		] );
	}

	/**
	 * @param array $gateways
	 *
	 * @return array
	 */
	public function disable_gateway( array $gateways ) {

		unset( $gateways[ $this->id ] );

		return $gateways;
	}

	/**
	 * @param string $text
	 *
	 * @return void
	 */
	protected function render_unavailable_template( $text = '' ) {

		load_template(
			dirname( __DIR__ ) . '/templates/unavailable_payment_method.php',
			false,
			[ 'imoje_unavailable_message' => $text ]
		);
	}

	/**
	 * @param WC_Order $order
	 * @param string   $refund_uuid
	 *
	 * @return bool
	 */
	protected function is_refund_processed( WC_Order $order, $refund_uuid ) {

		$processed = $order->get_meta( self::PROCESSED_REFUNDS_META_KEY );

		return is_array( $processed ) && in_array( $refund_uuid, $processed, true );
	}

	/**
	 * @param WC_Order $order
	 * @param string   $refund_uuid
	 *
	 * @return void
	 */
	protected function mark_refund_processed( WC_Order $order, $refund_uuid ) {

		$processed = $order->get_meta( self::PROCESSED_REFUNDS_META_KEY );

		if ( ! is_array( $processed ) ) {
			$processed = [];
		}

		$processed[] = $refund_uuid;

		$order->update_meta_data( self::PROCESSED_REFUNDS_META_KEY, array_values( array_unique( $processed ) ) );
		$order->save_meta_data();
	}

	/**
	 * @param string $message
	 *
	 * @return void
	 */
	protected function log_verification_error( $message ) {

		wc_get_logger()->error(
			$this->get_payment_method_data( 'display_name' ) . ' | ' . $message,
			[ 'source' => 'imoje' ]
		);
	}
}