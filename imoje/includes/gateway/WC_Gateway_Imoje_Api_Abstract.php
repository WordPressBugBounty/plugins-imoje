<?php

use Imoje\Payment\Api;
use Imoje\Payment\Util;

/**
 * Class WC_Gateway_Imoje_Api_Abstract
 */
abstract class WC_Gateway_Imoje_Api_Abstract extends WC_Gateway_Imoje_Abstract {

	/**
	 * @var Api
	 */
	protected $imoje_api;

	/**
	 * @var array
	 */
	protected $imoje_service;

	/**
	 * @var string
	 */
	protected $version;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( static::PAYMENT_METHOD_NAME );

		$this->version = Helper::get_version();

		$this->imoje_api = new Api(
			$this->get_option( 'authorization_token' ),
			$this->get_option( 'merchant_id' ),
			$this->get_option( 'service_id' ),
			$this->sandbox
				? Util::ENVIRONMENT_SANDBOX
				: Util::ENVIRONMENT_PRODUCTION
		);
	}

	/**
	 * @return array
	 */
	protected function get_service_active() {

		$service = $this->imoje_api->getServiceInfo();

		$wc_logger = wc_get_logger();

		if ( ! $service['success'] ) {

			$wc_logger->error( $this->get_payment_method_data( 'display_name' ) . ' | ' . __( 'Bad response in api, errors:', 'imoje' )
			                   . ' '
			                   . json_encode( $service ), [
				'source' => 'imoje',
			] );

			return [];
		}

		if ( empty( $service['body']['service']['isActive'] ) ) {

			$wc_logger->error( __( 'Service is inactive in imoje', 'imoje' ), [
				'source' => 'imoje',
			] );

			return [];
		}

		return $service['body']['service'];
	}

	/**
	 * @param array $transaction
	 *
	 * @return bool
	 */
	protected function verify_transaction( $transaction ) {

		if ( Helper::check_is_config_value_selected( $this->get_option( 'view_field' ) ) ) {
			return isset( $transaction['success'] )
			       && $transaction['success'];
		}

		return isset( $transaction['success'] )
		       && isset( $transaction['body']['action']['url'] )
		       && $transaction['success']
		       && $transaction['body']['action']
		       && $transaction['body']['action']['url'];
	}

	/**
	 * @param array $transaction
	 *
	 * @return bool
	 */
	protected function verify_payment_link( $transaction ) {

		return isset( $transaction['success'] )
		       && isset( $transaction['body']['payment']['url'] )
		       && $transaction['success']
		       && $transaction['body']['payment']
		       && $transaction['body']['payment']['url'];
	}

	/**
	 * @param WC_Order $order
	 * @param string   $payment_method
	 * @param string   $payment_method_channel
	 *
	 * @return array
	 */
	protected function create_transaction_and_process_order( $order, $payment_method, $payment_method_channel, $empty_cart = false, $installments_period = 0 ) {

		$is_payment_link = in_array( $this->payment_method_name,
			Helper::get_payment_methods_for_payment_link()
		);

		// check if transaction should be payment link or direct transaction
		$transaction = $is_payment_link
			? $this->imoje_api->createPaymentLink(
				$this->prepare_data(
					$order
				)
			)
			: $this->imoje_api->createTransaction(
				$this->prepare_data(
					$order,
					$payment_method,
					$payment_method_channel,
					$installments_period
				)
			);

		if ( ! ( $is_payment_link
			? $this->verify_payment_link( $transaction )
			: $this->verify_transaction( $transaction ) ) ) {
			$wc_logger = wc_get_logger();

			$wc_logger->error( $this->get_payment_method_data( 'display_name' ) . ' | ' . __( 'Could not initialize transaction: ', 'imoje' ) . json_encode( $transaction ), [
				'source' => 'imoje',
			] );

			return [];
		}

		if ( $empty_cart ) {
			$wc = WC();

			// Remove cart
			$wc->cart->empty_cart();
		}

		return $transaction['body'];
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	protected static function get_address_data( $order ) {

		$address = [
			'billing' => Api::prepareAddressData(
				$order->get_billing_first_name(),
				$order->get_billing_last_name(),
				$order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
				$order->get_billing_city(),
				$order->get_billing_state(),
				$order->get_billing_postcode(),
				$order->get_billing_country()
			),
		];

		$shipping_data = current( $order->get_items( 'shipping' ) );

		if ( $shipping_data ) {
			$address['shipping'] = Api::prepareAddressData(
				$order->get_shipping_first_name(),
				$order->get_shipping_last_name(),
				$order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2(),
				$order->get_shipping_city(),
				$order->get_shipping_state(),
				$order->get_shipping_postcode(),
				$order->get_shipping_country()
			);
		}

		return $address;
	}

	/**
	 * @return bool
	 */
	protected function check_availability( $cart_total, $payment_method, $payment_method_code ) {

		if ( ! is_checkout() || ! $cart_total ) {
			return false;
		}

		$this->imoje_service = $this->get_service_active();

		if ( ! $this->imoje_service ) {

			return false;
		}

		$imoje_payment_method = $this->imoje_api->getPaymentChannelInServiceAndVerify( $this->imoje_service['paymentMethods'],
			Util::getPaymentMethod( $payment_method ),
			Util::getPaymentMethodCode( $payment_method_code )
		);

		if ( ! $imoje_payment_method || empty( $imoje_payment_method['transactionLimits'] ) ) {
			return false;
		}

		return $this->imoje_api->verifyTransactionLimits( $imoje_payment_method['transactionLimits'], $cart_total );
	}

	/**
	 * @param array $pm
	 *
	 * @return bool
	 */
	protected function render_channels( $pm, $is_blik = false ) {

		global $wp_query;

		$this->imoje_service = $this->get_service_active();

		if ( ! $this->imoje_service ) {
			$this->render_unavailable_template();

			return false;
		}

		$total = WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax();

		if ( ! $total ) {

			$order_key = isset( $_GET['key'] )
				? wc_clean( wp_unslash( $_GET['key'] ) )
				: '';
			$order     = wc_get_order( absint( get_query_var( 'order-pay' ) ) );

			if ( $order && $order->get_order_key() === $order_key ) {
				$total = $order->get_total();
			} else {
				$this->render_unavailable_template(
					__( 'No payment channel available. Choose another payment method.' )
				);

				return false;
			}
		}

		$pm_blik = Util::getPaymentMethod( 'blik' );

		if ( in_array( $pm_blik, $pm )
		     && Helper::check_is_config_value_selected( $this->get_option( 'view_field' ) ) ) {

			foreach ( $this->imoje_service['paymentMethods'] as $payment_method ) {

				if ( $payment_method['paymentMethod'] === $pm_blik
				     && ! $this->imoje_api->verifyTransactionLimits(
						$payment_method['transactionLimits'],
						$total ) ) {

					$this->render_unavailable_template(
						Helper::get_tooltip_payment_channel(
							$this->get_payment_channel_to_array( $payment_method, $total )
						) );

					return false;
				}
			}

			load_template( dirname( __DIR__ ) . '/templates/blik/field.php' );

			return true;
		}

		$currency_iso_code             = get_woocommerce_currency();
		$payment_method_list_online    = [];
		$payment_method_list_no_online = [];
		$pm_wallet                     = Util::getPaymentMethod( 'wallet' );
		$pmc_gpay                      = Util::getPaymentMethodCode( 'gpay' );
		$pmc_applepay                  = Util::getPaymentMethodCode( 'applepay' );
		$pmc_blik                      = Util::getPaymentMethodCode( 'blik' );

		foreach ( $this->imoje_service['paymentMethods'] as $payment_method ) {

			if ( ! $payment_method['isActive']
			     || strtolower( $payment_method['currency'] ) !== strtolower( $currency_iso_code )
			     || ! in_array( $payment_method['paymentMethod'], $pm )
			     || $this->check_payment_method_with_inclusions( $payment_method['paymentMethod'], $payment_method['paymentMethodCode'], $pm_wallet, [ $pmc_gpay, $pmc_applepay ] )
			     || $this->check_payment_method_with_inclusions( $payment_method['paymentMethod'], $payment_method['paymentMethodCode'], $pm_blik, [ $pmc_blik ] )
			) {
				continue;
			}

			if ( $this->imoje_api->verifyTransactionLimits( $payment_method['transactionLimits'], $total ) ) {

				$payment_method_list_online[] = $this->get_payment_channel_to_array( $payment_method, $total );

				continue;
			}

			$payment_method_list_no_online[] = $this->get_payment_channel_to_array( $payment_method, $total );
		}

		if ( ! $payment_method_list_online && ! $payment_method_list_no_online ) {
			$this->render_unavailable_template(
				__( 'No payment channel available. Choose another payment method.' )
			);

			return false;
		}

		$wp_query->query_vars['payment_method_list_active']    = $payment_method_list_online;
		$wp_query->query_vars['payment_method_list_no_active'] = $payment_method_list_no_online;
		$wp_query->query_vars['is_blik']                       = $is_blik;

		load_template( dirname( __DIR__ ) . '/templates/payment_method_list.php', false );

		return true;
	}

	/**
	 * @return void
	 */
	protected function render_regulations() {
		global $wp_query;

		$locale = get_locale();

		$locale = ( $locale === 'pl_PL' || $locale === 'pl' )
			? Util::LANG_PL
			: Util::LANG_EN;

		$wp_query->query_vars[ Util::REGULATION ] = Util::getDocUrl( $locale, Util::REGULATION );
		$wp_query->query_vars[ Util::IODO ]       = Util::getDocUrl( $locale, Util::IODO );

		load_template( dirname( __DIR__ ) . '/templates/regulation.php', false );
	}

	/**
	 * @param array  $payment_method
	 * @param number $cart_total
	 * @param bool   $add_currency
	 *
	 * @return array
	 */
	protected function get_payment_channel_to_array( $payment_method, $cart_total, $add_currency = false ) {
		$logo = Util::getPaymentMethodCodeLogo( $payment_method['paymentMethodCode'] );

		foreach ( $payment_method['paymentMethodCodeImage'] as $imgList ) {
			if ( isset( $imgList['png'] ) && $imgList['png'] ) {
				$logo = $imgList['png'];
			}
		}

		$array = [
			'payment_method'      => $payment_method['paymentMethod'],
			'payment_method_code' => $payment_method['paymentMethodCode'],
			'description'         => $payment_method['description'],
			'logo'                => $logo,
			'is_available'        => $payment_method['isOnline'],
			'limit'               => null,
			'min_transaction'     => null,
			'max_transaction'     => null,
		];

		if ( $payment_method['isOnline'] ) {
			$min_transaction_value = isset( $payment_method['transactionLimits']['minTransaction']['value'] )
				? Util::convertAmountToMain( $payment_method['transactionLimits']['minTransaction']['value'] )
				: null;
			$max_transaction_value = isset( $payment_method['transactionLimits']['maxTransaction']['value'] )
				? Util::convertAmountToMain( $payment_method['transactionLimits']['maxTransaction']['value'] )
				: null;

			$array['min_transaction'] = $min_transaction_value;
			$array['max_transaction'] = $max_transaction_value;

			if ( $min_transaction_value && ( $cart_total < $min_transaction_value ) ) {
				$array['limit']        = 1;
				$array['is_available'] = false;

				return $array;
			}

			if ( $max_transaction_value && $cart_total > $max_transaction_value ) {
				$array['limit']        = 2;
				$array['is_available'] = false;

				return $array;
			}
		}

		if ( $add_currency ) {
			$array['currency'] = $payment_method['currency'];
		}

		return $array;
	}

	/**
	 * @param int $order_id
	 *
	 * @return array|bool
	 */
	public function process_payment( $order_id ) {
		$customer_notice_error = __( 'Payment error. Contact with shop administrator.', 'imoje' );

		$order = wc_get_order( $order_id );
		$wc_logger = wc_get_logger();

		if ( ! $order || ! $order->get_id() ) {

			wc_add_notice( $customer_notice_error, 'error' );


			$wc_logger->error( __( 'Could not get order data', 'imoje' ), [
				'source' => 'imoje',
			] );

			return [
				'result' => 'failure',
			];
		}

		// adjust it to installments
		$formSelectedChannel = $_POST['imoje-selected-channel'];

		if ( static::PAYMENT_METHOD_NAME == WC_Gateway_ImojeInstallments::PAYMENT_METHOD_NAME ) {
			$formSelectedChannel = $_POST['imoje-selected-channel-installments'];
		}

		$post_imoje_selected_channel = '';

		if ( isset( $formSelectedChannel ) && $formSelectedChannel ) {
			$post_imoje_selected_channel = sanitize_text_field( $formSelectedChannel );
		}

		$post_selected_channel = explode( '-', $post_imoje_selected_channel );

		if ( ! in_array( $this->payment_method_name,
				array_merge( Helper::get_payment_methods_for_payment_link(), [
					WC_Gateway_ImojeInstallments::PAYMENT_METHOD_NAME => WC_Gateway_ImojeInstallments::PAYMENT_METHOD_NAME,
				] )
			)
		     && (
			     ! $post_imoje_selected_channel ||
			     ! preg_match( '/^[a-z0-9_]+-[a-z0-9_]+$/i', $post_imoje_selected_channel ) ||
			     ! $this->check_availability( $order->get_total(), $post_selected_channel[0], $post_selected_channel[1] )
		     )
		) {

			wc_add_notice( $customer_notice_error, 'error' );

			$wc_logger->error( $this->get_payment_method_data( 'display_name' ) . ' | ' . __( 'Payment method not selected or not available', 'imoje' ), [
				'source' => 'imoje',
			] );

			return [
				'result' => 'failure',
			];
		}

		$transaction = $this->create_transaction_and_process_order(
			wc_get_order( $order_id ),
			empty( $post_selected_channel[0] )
				? ''
				: $post_selected_channel[0],
			empty( $post_selected_channel[1] )
				? ''
				: $post_selected_channel[1],
			false,
			empty( $_POST['imoje-installments-period'] )
				? 0
				: (int) $_POST['imoje-installments-period']
		);

		if ( ! $transaction ) {
			wc_add_notice( $customer_notice_error, 'error' );

			$wc_logger->error( $this->get_payment_method_data( 'display_name' ) . ' | ' . __( 'Could not create an order', 'imoje' ), [
				'source' => 'imoje',
			] );

			return [
				'result' => 'failure',
			];
		}

		$redirect_url = isset( $transaction['action']['url'] ) && $transaction['action']['url']
			? $transaction['action']['url']
			: ( ( isset( $transaction['payment']['url'] ) && $transaction['payment']['url'] )
				? $transaction['payment']['url']
				: '' );

		if ( ! $redirect_url ) {

			wc_add_notice( $customer_notice_error, 'error' );

			$wc_logger->error( $this->get_payment_method_data( 'display_name' ) . ' | ' . __( 'Could not redirect to payment: ', 'imoje' ) . $transaction['transaction']['paymentMethodCode'], [
				'source' => 'imoje',
			] );

			return [
				'result' => 'failure',
			];
		}

		return [
			'result'   => 'success',
			'redirect' => $redirect_url,
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function verify_availability( $cart_total, $payment_method = '' ) {

		return true;
	}

	/**
	 * @return void
	 */
	protected function render_twisto_regulations() {

		load_template( dirname( __DIR__ ) . '/templates/twisto/regulation.php', false );
	}

	/**
	 * @return array
	 */
	public function get_payment_channels() {
		return $this->prepare_payment_methods_block_checkout( static::PAYMENT_METHOD_NAME );
	}

	/**
	 * @param string $payment_method_name
	 *
	 * @return array
	 */
	protected function prepare_payment_methods_block_checkout( $payment_method_name ) {
		$child_payment_method_name = static::PAYMENT_METHOD_NAME;
		$settings                  = get_option( "woocommerce_" . $child_payment_method_name . "_settings" );
		$currencies                = ( is_array( $settings ) && isset( $settings['currencies'] ) )
			? $settings['currencies']
			: [];
		$cart_total                = WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax();

		if ( ! is_array( $currencies ) ) {
			$currencies = [ $currencies ];
		}

		$currencies    = array_map( 'strtolower', $currencies );
		$imoje_service = $this->get_service_active();

		if ( ! isset( $imoje_service['paymentMethods'] ) || ! is_array( $imoje_service['paymentMethods'] ) ) {
			return [];
		}

		$filtered_methods = array_filter( $imoje_service['paymentMethods'], function ( $method ) use ( $currencies, $payment_method_name ) {
			return isset( $method['paymentMethod'] )
			       && $method['paymentMethod'] === $payment_method_name
			       && in_array( strtolower( $method['currency'] ), $currencies, true )
			       && $method['isActive'];
		} );

		$prepared_methods = [];
		$wallet           = Util::getPaymentMethod( 'wallet' );
		$pmc_gpay         = Util::getPaymentMethodCode( 'gpay' );
		$pmc_applepay     = Util::getPaymentMethodCode( 'applepay' );
		$pm_blik          = Util::getPaymentMethod( 'blik' );
		$pmc_blik         = Util::getPaymentMethodCode( 'blik' );

		foreach ( $filtered_methods as $key => $payment_method ) {

			if ( $this->check_payment_method_with_inclusions( $payment_method['paymentMethod'], $payment_method['paymentMethodCode'], $wallet, [ $pmc_gpay, $pmc_applepay ] )
			     || $this->check_payment_method_with_inclusions( $payment_method['paymentMethod'], $payment_method['paymentMethodCode'], $pm_blik, [ $pmc_blik ] ) ) {
				continue;
			}

			$prepared_methods[ $key ] = $this->get_payment_channel_to_array( $payment_method, $cart_total, true );
		}

		return $prepared_methods;
	}

	/**
	 * @param string $payment_method
	 * @param string $payment_method_code
	 * @param string $expected_method
	 * @param array  $excluded_payment_method_codes
	 *
	 * @return bool
	 */
	private function check_payment_method_with_inclusions( $payment_method, $payment_method_code, $expected_method, $excluded_payment_method_codes ) {
		return $payment_method === $expected_method
		       && ! in_array( $payment_method_code, $excluded_payment_method_codes );
	}
}
