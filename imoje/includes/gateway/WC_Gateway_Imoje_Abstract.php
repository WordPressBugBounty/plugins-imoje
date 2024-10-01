<?php

use Imoje\Payment\Util;

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
			WC_Gateway_ImojeInstallments::PAYMENT_METHOD_NAME         => Helper::get_gateway_details(
				__( 'imoje - installments', 'imoje' ),
				__( 'imoje installments', 'imoje' ),
				__( 'imoje installments', 'imoje' ),
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
	protected function get_notification_url() {
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
				'description' => __( 'The module requires a configuration in the imoje administration panel. <br/> Go to imoje.ing.pl and log in to the administration panel. <br/> Go to stores tab and enter the notification address in the appropriate field and copy the configuration keys. <br/> Copy the keys into the fields described below.', 'imoje' ),
			],
			'enabled'                 => [
				'title'   => __( 'Enable / Disable', 'imoje' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable', 'imoje' ),
				'default' => 'no',
			],
			'debug_mode'              => [
				'title'   => __( 'Debug mode', 'imoje' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable debug mode', 'imoje' ),
				'default' => 'no',
			],
			'sandbox'                 => [
				'title'   => __( 'Sandbox', 'imoje' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'label'   => __( 'Enable sandbox', 'imoje' ),
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

		// Return thank you redirect
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

		Helper::check_notification(
			$this->get_option( 'service_id' ),
			$this->get_option( 'service_key' ),
			$this->get_option( 'debug_mode' )
		);
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
