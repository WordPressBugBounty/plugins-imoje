<?php

use Imoje\Payment\Util;
use Imoje\Payment\Notification;

/**
 * Class WC_Gateway_Imoje_Abstract
 */
abstract class WC_Gateway_Imoje_Abstract extends WC_Payment_Gateway {

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

		add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), [
			$this,
			'process_notification',
		] );

		add_action( 'wp_enqueue_scripts', [
			$this,
			'imoje_enqueue_scripts',
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
	 * Setup general properties for the gateway.
	 */
	protected function setup_properties() {
		$this->id                 = $this->payment_method_name;
		$this->method_title       = $this->get_payment_method_data( 'name' );
		$this->method_description = __( 'imoje payments', 'imoje' );
		$this->sandbox            = Helper::check_is_config_value_selected( $this->get_option( 'sandbox' ) );
		$this->has_fields         = false;
		$this->supports           = [
			'products',
			'refunds',
		];
		$this->description        = $this->get_option( 'description', ' ' );

		$this->title = $this->get_option( 'title' );
		$this->icon  = Helper::check_is_config_value_selected( $this->get_option( 'hide_brand' ) )
			? null
			: WOOCOMMERCE_IMOJE_PLUGIN_URL . '/assets/images/' . $this->payment_method_name . '.png';
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
			WC_Gateway_ImojeBlik::PAYMENT_METHOD_NAME         => Helper::get_gateway_details(
				__( 'imoje - BLIK', 'imoje' ),
				__( 'BLIK', 'imoje' ),
				__( 'Pay with BLIK via imoje.', 'imoje' ),
				false
			),
			WC_Gateway_ImojeCards::PAYMENT_METHOD_NAME        => Helper::get_gateway_details(
				__( 'imoje - cards', 'imoje' ),
				__( 'Payment cards', 'imoje' ),
				__( 'Pay with card via imoje.', 'imoje' ),
				true
			),
			WC_Gateway_Imoje::PAYMENT_METHOD_NAME             => Helper::get_gateway_details(
				__( 'imoje - Paywall', 'imoje' ),
				__( 'Simple and easy online payments', 'imoje' ),
				__( 'You will be redirected to a payment method selection page.', 'imoje' ),
				true
			),
			WC_Gateway_ImojePbl::PAYMENT_METHOD_NAME          => Helper::get_gateway_details(
				__( 'imoje - PBL', 'imoje' ),
				__( 'Pay-By-Link', 'imoje' ),
				__( 'Choose payment channel and pay via imoje.', 'imoje' ),
				false
			),
			WC_Gateway_ImojePaylater::PAYMENT_METHOD_NAME     => Helper::get_gateway_details(
				__( 'imoje - pay later', 'imoje' ),
				__( 'imoje - pay later', 'imoje' ),
				__( 'Buy now, pay later via imoje.', 'imoje' ),
				false
			),
			WC_Gateway_ImojeVisa::PAYMENT_METHOD_NAME         => Helper::get_gateway_details(
				__( 'imoje - Visa Mobile', 'imoje' ),
				__( 'Visa Mobile', 'imoje' ),
				__( 'Visa Mobile payment with imoje ', 'imoje' ),
				true
			),
			WC_Gateway_ImojeInstallments::PAYMENT_METHOD_NAME => Helper::get_gateway_details(
				__( 'imoje - installments', 'imoje' ),
				__( 'imoje installments', 'imoje' ),
				__( 'imoje installments', 'imoje' ),
				false
			),
			WC_Gateway_ImojeWallet::PAYMENT_METHOD_NAME       => Helper::get_gateway_details(
				__( 'imoje - electronic wallet', 'imoje' ),
				__( 'Electronic wallet', 'imoje' ),
				__( 'Pay with electronic wallet via imoje.', 'imoje' ),
				false
			),
		];
	}

	/**
	 * @param number $cart_total
	 * @param string $payment_method
	 *
	 * @return mixed
	 */
	abstract protected function verify_availability( $cart_total, $payment_method = '' );

	/**
	 * @param WC_Order $order
	 * @param string   $payment_method
	 * @param string   $payment_method_channel
	 *
	 * @return array|string
	 */
	abstract protected function prepare_data( $order, $payment_method = '', $payment_method_channel = '' );

	/**
	 * @param WC_Order $order
	 *
	 * @return array|string
	 */
	protected function get_invoice( $order ) {
		return Helper::get_invoice(
			$order,
			$this->get_option( 'ing_ksiegowosc' ),
			true,
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
	 * @param $config
	 *
	 * @return void
	 */
	protected function default_form_fields_merge( $config = [] ) {

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
			'sandbox_hint'            => [
				'title' => ( $this->get_option( 'sandbox' ) === "yes" )
					? __( 'Sandbox is enabled', 'imoje' )
					: null,
				'type'  => 'title',
			],
			'hint'                    => [
				'title'       => __( 'Hint', 'imoje' ),
				'class'       => 'hidden',
				'type'        => 'title',
				'description' => __( 'The module requires a configuration with your shop in the imoje administration panel. <br/> Go to <b><a href="https://imoje.ing.pl">imoje.ing.pl</a></b> and log in to the administration panel. <br/> Then go to <b>Shops>your shop name>Details>Data for integration</b> and copy <b>Merchant ID</b>, <b>Shop ID</b>, <b>Shop key</b>, <b>Authorization token</b> into the fields described below.', 'imoje' ),
			],
			'enabled'                 => [
				'title'   => __( 'Enable / Disable', 'imoje' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable', 'imoje' ),
				'default' => 'no',
			],
			'sandbox'                 => [
				'title'   => __( 'Sandbox', 'imoje' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'label'   => __( 'Enable sandbox', 'imoje' ),
				'description' => __( 'In order to use sandbox mode, you must create an account in a dedicated <b><a href="https://sandbox.imoje.ing.pl">sandbox environment</a></b>', 'imoje' ),
			],
			'hide_brand'              => [
				'title'   => __( 'Display brand', 'imoje' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'label'   => __( 'Hide brand', 'imoje' ),
			],
			'ing_ksiegowosc'          => [
				'title'   => __( 'ING Księgowość', 'imoje' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'label'   => __( 'Enable', 'imoje' ),
				'description' => __( 'If you are entitled to a tax exemption and you want imoje to send the basis for the exemption to ING Księgowość, then create a new tax class with a name starting as <b>ZW_</b> and ending with one of the available values of the <b>basisForVatExemption</b> object at the following <b><a href="https://imojeapi.docs.apiary.io/#/introduction/ing-ksiegowosc">link</a></b>. Example: <b>ZW_DENTAL_TECHNICAN_SERVICES</b>', 'imoje' ),
			],
			'update_method_name'          => [
				'title'   => __( 'Update method name in order details via notification', 'imoje' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'label'   => __( 'Enable', 'imoje' ),
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
				'options' => Util::getSupportedCurrencies(),
			],
			'ing_ksiegowosc_meta_tax' => [
				'title'   => __( 'Meta name for VAT', 'imoje' ),
				'type'    => 'text',
				'default' => '',
				'description' => __( 'Required if you want to use ING Księgowość and create invoices with VAT ID', 'imoje' ),
			],
		];
	}

	/**
	 * @param int         $order_id
	 * @param null|number $amount
	 * @param string      $reason
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {

		if ( $reason === 'imoje API' ) {
			return true;
		}

		if ( $amount <= 0 ) {

			throw new Exception( __( 'Refund amount must be higher than 0', 'imoje' ) );
		}

		return Helper::process_refund(
			$order_id,
			$this->get_option( 'authorization_token' ),
			$this->get_option( 'merchant_id' ),
			$this->get_option( 'service_id' ),
			$amount,
			Helper::check_is_config_value_selected( $this->get_option( 'sandbox' ) )
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
	 * @return void
	 * @throws Exception
	 */
	public function process_notification() {

		$notification = new Notification(
			$this->get_option( 'service_id' ),
			$this->get_option( 'service_key' )
		);

		// it can be order data or notification code - depends on verification notification
		$result_check_request_notification = $notification->checkRequest();

		if ( is_int( $result_check_request_notification ) ) {
			echo $notification->formatResponse( Notification::NS_ERROR, $result_check_request_notification );
			exit();
		}

		if ( ! ( $order = wc_get_order( $result_check_request_notification['transaction']['orderId'] ) ) ) {

			echo $notification->formatResponse( Notification::NS_ERROR, Notification::NC_ORDER_NOT_FOUND );
			exit();
		}

		$order_status = $order->get_status();

		if ( $result_check_request_notification['transaction']['type'] === Notification::TRT_REFUND ) {

			if ( $result_check_request_notification['transaction']['status'] !== Notification::TRS_SETTLED ) {
				echo $notification->formatResponse( Notification::NS_OK, Notification::NC_IMOJE_REFUND_IS_NOT_SETTLED );

				exit();
			}

			if ( $order_status === 'refunded' ) {
				echo $notification->formatResponse( Notification::NS_ERROR, Notification::NC_ORDER_STATUS_IS_INVALID_FOR_REFUND );
				exit();
			}

			$refund = wc_create_refund( [
				'amount'         => Util::convertAmountToMain( $result_check_request_notification['transaction']['amount'] ),
				'reason'         => 'imoje API',
				'order_id'       => $result_check_request_notification['transaction']['orderId'],
				'refund_payment' => true,
			] );

			if ( $refund->errors ) {

				echo $notification->formatResponse( Notification::NS_ERROR );
				exit();
			}

			$order->add_order_note(
				sprintf(
					__( 'Refund for amount %s with UUID %s has been correctly processed.', 'imoje' ),
					$result_check_request_notification['transaction']['amount'],
					$result_check_request_notification['transaction']['id']
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
			Util::convertAmountToFractional( $order->data['total'] ),
			$order->data['currency']
		) ) {

			echo $notification->formatResponse( Notification::NS_ERROR, Notification::NC_AMOUNT_NOT_MATCH );
			exit();
		}

		$transactionStatuses = Util::getTransactionStatuses();

		if ( ! isset( $transactionStatuses[ $result_check_request_notification['transaction']['status'] ] ) ) {
			echo $notification->formatResponse( Notification::NS_ERROR, Notification::NC_UNHANDLED_STATUS );
			exit;
		}

		switch ( $result_check_request_notification['transaction']['status'] ) {
			case Notification::TRS_SETTLED:

				$order->update_status( $order->needs_processing()
					? 'processing'
					: 'completed',
					__( 'Transaction reference', 'imoje' ) . ': ' . $result_check_request_notification['transaction']['id'] );

				if ( Helper::check_is_config_value_selected( $this->get_option( 'update_method_name' ) ) && $order->get_payment_method() !== $this->payment_method_name && $order->get_payment_method_title() !== $this->get_payment_method_data( 'display_name' ) ) {

					$order->set_payment_method( $this->payment_method_name );
					$order->set_payment_method_title( $this->get_payment_method_data( 'display_name' ) );
					$order->save();
				};

				$order->update_meta_data( 'imoje_transaction_uuid', $result_check_request_notification['transaction']['id'] );
				$order->save_meta_data();

				echo $notification->formatResponse( Notification::NS_OK );
				exit;
			case Notification::TRS_REJECTED:
				$order->update_status( 'failed' );
				$order->add_order_note( __( 'Transaction reference', 'imoje' ) . ': ' . $result_check_request_notification['transaction']['id'] );
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
	public function check_available_payment_gateways( $gateways ) {

		if ( ! is_checkout() || ! WC()->cart ) {
			return $gateways;
		}

		$currencies = $this->get_option( 'currencies', [] );

		if ( is_array( $currencies )
		     && Helper::check_is_config_value_selected( $this->get_option( 'enabled' ) )
		     && in_array( strtolower( get_woocommerce_currency() ), $currencies )
		     && $this->get_option( 'service_key' )
		     && $this->get_option( 'service_id' )
		     && $this->get_option( 'merchant_id' )
		     && $this->verify_availability( WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax() )
		) {

			return $gateways;
		}

		//return $gateways;
		return $this->disable_gateway( $gateways );
	}

	/**
	 * @return void
	 */
	public function imoje_enqueue_scripts() {

		$version = Helper::get_version();

		wp_enqueue_script( 'imoje-gateway-js', plugins_url( '/assets/js/imoje-gateway.min.js', WOOCOMMERCE_IMOJE_PLUGIN_DIR ),
			[ 'jquery' ], $version, true );
		wp_enqueue_style( 'imoje-gateway-css', plugins_url( '/assets/css/imoje-gateway.min.css', WOOCOMMERCE_IMOJE_PLUGIN_DIR ),
			[], $version );

		wp_localize_script( 'imoje-gateway-js', 'imoje_js_object', [ 'imoje_blik_tooltip' => __( "You must insert exactly 6 numbers as BLIK code!", "imoje" ) ] );
	}

	/**
	 * @param array $gateways
	 *
	 * @return array
	 */
	public function disable_gateway( $gateways ) {

		unset( $gateways[ $this->id ] );

		return $gateways;
	}

	/**
	 * @param string $text
	 *
	 * @return void
	 */
	protected function render_unavailable_template( $text = '' ) {

		global $wp_query;

		$wp_query->query_vars['imoje_unavailable_message'] = $text;

		load_template( dirname( __DIR__ ) . '/templates/unavailable_payment_method.php', false );
	}
}
