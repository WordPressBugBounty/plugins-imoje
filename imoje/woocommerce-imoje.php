<?php
/*
Plugin Name: imoje
Plugin URI: https://imoje.pl
Description: Add payment via imoje to WooCommerce
Version: 4.15.3
Author: imoje <kontakt.tech@imoje.pl>
Author URI: https://imoje.pl
Text Domain: imoje
License: GPLv3
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Imoje\Payment\Api;
use Imoje\Payment\Util;
use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;

const WOOCOMMERCE_IMOJE_PLUGIN_FILE_DIR = __FILE__;
define( 'WOOCOMMERCE_IMOJE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WOOCOMMERCE_IMOJE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

add_action( 'plugins_loaded', 'imoje_init_woocommerce_gateway', 0 );

/**
 * Init function that runs after plugin installation.
 */
function imoje_init_woocommerce_gateway() {

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	$pathToAutoload = __DIR__ . "/includes/libs/payment-core/vendor/autoload.php";

	if ( file_exists( $pathToAutoload ) ) {
		include_once $pathToAutoload;
	} else {
		include_once __DIR__ . "/includes/libs/Payment-core/vendor/autoload.php";
	}

	@include_once __DIR__ . "/includes/Imoje_Helper.php";

	load_plugin_textdomain( 'imoje', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );

	require_once( 'includes/gateway/WC_Gateway_Imoje_Abstract.php' );
	require_once( 'includes/gateway/WC_Gateway_Imoje_Api_Abstract.php' );
	require_once( 'includes/gateway_block/WC_Gateway_Imoje_RestApi_Blocks.php' );

	foreach ( imoje_get_gateways() as $method ) {
		require_once( sprintf( 'includes/gateway/%1$s.php', $method ) );
	}

	add_action( 'before_woocommerce_init', function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__ );
		}
	} );

	add_filter( 'woocommerce_payment_gateways', 'imoje_add_gateways' );

	if ( class_exists( '\Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils' ) && CartCheckoutUtils::is_checkout_block_default() ) {
		add_action( 'woocommerce_blocks_payment_method_type_registration', function ( $payment_method_registry ) {
			$payment_method_registry->register( new WC_Gateway_Imoje_RestApi_Blocks() );
		} );
	}
}

/**
 * @return string[]
 */
function imoje_get_gateways() {

	$prefix = 'WC_Gateway_';

	return [
		$prefix . 'ImojeBlik',
		$prefix . 'Imoje',
		$prefix . 'ImojePaylater',
		$prefix . 'ImojeCards',
		$prefix . 'ImojePbl',
		$prefix . 'ImojeVisa',
		$prefix . 'ImojeInstallments',
		$prefix . 'ImojeWallet',
		$prefix . 'ImojeLeasenow',
		$prefix . 'ImojeWt',
	];
}

/**
 * @param array $methods
 *
 * @return array
 */
function imoje_add_gateways( $methods ) {

	foreach ( imoje_get_gateways() as $method ) {
		$methods[] = $method;
	}

	return $methods;
}

// region utils

// region ajax check transaction
add_action( 'wp_ajax_imoje_check_transaction', 'imoje_check_transaction_ajax' );
add_action( 'wp_ajax_nopriv_imoje_check_transaction', 'imoje_check_transaction_ajax' );

/**
 * @return void
 */
function imoje_check_transaction_ajax() {

	if ( ! isset( $_REQUEST['transaction_uuid'] ) || ! $_REQUEST['transaction_uuid']
	     || ! isset( $_REQUEST['method'] ) || ! $_REQUEST['method']
	) {

		wp_send_json_error();
	}

	$imoje_api = imoje_get_api_instance( sanitize_text_field( $_REQUEST['method'] ) );

	if ( ! $imoje_api ) {
		wp_send_json_error();
	}

	$transaction = $imoje_api->getTransaction( sanitize_text_field( $_REQUEST['transaction_uuid'] ) );

	if ( $transaction['success'] ) {
		wp_send_json_success( imoje_prepare_success_response( $transaction ) );
	}

	wp_send_json_error();
}

// endregion

// region create transaction
add_action( 'wp_ajax_imoje_create_transaction', 'imoje_create_transaction_ajax' );
add_action( 'wp_ajax_nopriv_imoje_create_transaction', 'imoje_create_transaction_ajax' );

/**
 * @return void
 */
function imoje_create_transaction_ajax() {

	if ( ( ! isset( $_REQUEST['method'] ) && ! $_REQUEST['method'] )
	     || ( ! isset( $_REQUEST['order_id'] ) && ! $_REQUEST['order_id'] ) ) {
		wp_send_json_error();
	}

	$blik_code = null;

	$is_code = isset( $_REQUEST['code'] ) && $_REQUEST['code'];

	// region blik
	if ( $is_code
	     && isset( $_REQUEST['order_id'] ) && $_REQUEST['order_id'] ) {

		if ( ! is_numeric( $_REQUEST['code'] )
		     || strlen( $_REQUEST['code'] ) != 6 ) {

			wp_send_json_error();
		}

		$blik_code = $_REQUEST['code'];
	}

	$order = wc_get_order( sanitize_text_field( $_REQUEST['order_id'] ) );

	if ( ! $order ) {
		wp_send_json_error();
	}

	$request_method = sanitize_text_field( $_REQUEST['method'] );

	$imoje_api = imoje_get_api_instance( $request_method );

	if ( ! $imoje_api ) {
		wp_send_json_error();
	}

	$payment_method      = '';
	$payment_method_code = '';
	$transaction_body    = '';
	$transaction_url     = '';

	if ( $blik_code ) {
		$payment_method      = Util::getPaymentMethod( 'blik' );
		$payment_method_code = Util::getPaymentMethodCode( 'blik' );

		$is_remember_blik_code = isset( $_REQUEST['remember_blik_code'] ) && $_REQUEST['remember_blik_code'];

		if ( $is_remember_blik_code ) {
			$payment_method_code = Util::getPaymentMethodCode( 'blik_oneclick' );
		}

		if ( $is_remember_blik_code && $is_code
		     && isset( $_REQUEST['profile_id'] ) && $_REQUEST['profile_id'] ) {

			$request_order_id = sanitize_text_field( $_REQUEST['order_id'] );
			$transaction_body = $imoje_api->prepareBlikOneclickData(
				sanitize_text_field( $_REQUEST['profile_id'] ),
				$order->get_total(),
				$order->get_currency(),
				$request_order_id,
				$request_order_id,
				sanitize_text_field( $_SERVER['REMOTE_ADDR'] ),
				'',
				$blik_code
			);
			$transaction_url  = $imoje_api->getDebitBlikProfileUrl();
		}
	}

	$cid = '';
	if ( is_user_logged_in() ) {
		$user_id = get_current_user_id();
		$user    = get_userdata( $user_id );
		$cid     = Util::getCid( $user_id . $user->user_email );
	}

	if ( ! $transaction_body ) {

		$options          = imoje_get_options( $request_method );
		$transaction_body = $imoje_api->prepareData(
			$order->get_total(),
			$order->get_currency(),
			$order->get_id(),
			$payment_method,
			$payment_method_code,
			$order->get_checkout_order_received_url(),
			$order->get_checkout_order_received_url(),
			$order->get_billing_first_name(),
			$order->get_billing_last_name(),
			$order->get_billing_email(),
			WC_Gateway_Imoje::get_notification_url(),
			Api::TRANSACTION_TYPE_SALE,
			'',
			$blik_code,
			'',
			$cid,
			Imoje_Helper::get_invoice( $order, $options['ing_ksiegowosc'], true, $options['ing_ksiegowosc_meta_tax'] ),
			'',
			Imoje_Helper::get_version()
		);
	}

	$transaction = $imoje_api->createTransaction( $transaction_body, $transaction_url );

	if ( $transaction['success'] ) {

		wp_send_json_success( imoje_prepare_success_response( $transaction ) );
	}

	if ( isset( $transaction['data']['body'] ) && $transaction['data']['body'] ) {

		wc_get_logger()->error(
			__( 'Transaction could not be initialized, error: ', 'imoje' ) . json_encode( $transaction['data']['body'] ), [
			'source' => 'imoje',
		] );
	}

	wp_send_json_error();
}

// endregion

// region deactivate alias
add_action( 'wp_ajax_imoje_deactivate_alias', 'imoje_deactivate_alias' );
add_action( 'wp_ajax_nopriv_imoje_deactivate_alias', 'imoje_deactivate_alias' );

/**
 * @return void
 */
function imoje_deactivate_alias() {

	if (
		( ! isset( $_REQUEST['method'] ) && ! $_REQUEST['method'] )
		|| ( ! isset( $_REQUEST['profile_id'] ) && ! $_REQUEST['profile_id'] )
		|| ( ! isset( $_REQUEST['blik_key'] ) && ! $_REQUEST['blik_key'] )
	) {
		wp_send_json_error();
	}

	$imoje_api = imoje_get_api_instance( sanitize_text_field( $_REQUEST['method'] ) );

	if ( ! $imoje_api ) {
		wp_send_json_error();
	}

	$deactivate = $imoje_api->createTransaction(
		json_encode( [
				'blikProfileId' => sanitize_text_field( $_REQUEST['profile_id'] ),
				'blikKey'       => sanitize_text_field( $_REQUEST['blik_key'] ),
			]
		),
		$imoje_api->getDeactivateBlikProfileUrl()
	);

	if ( $deactivate['success'] ) {
		wp_send_json_success();
	}

	wp_send_json_error();
}

// endregion

// region debit alias
add_action( 'wp_ajax_imoje_debit_alias', 'imoje_debit_alias' );
add_action( 'wp_ajax_nopriv_imoje_debit_alias', 'imoje_debit_alias' );

/**
 * @return void
 */
function imoje_debit_alias() {

	if (
		( ! isset( $_REQUEST['method'] ) && ! $_REQUEST['method'] )
		|| ( ! isset( $_REQUEST['order_id'] ) && ! $_REQUEST['order_id'] )
		|| ( ! isset( $_REQUEST['profile_id'] ) && ! $_REQUEST['profile_id'] )
		|| ( ! isset( $_REQUEST['blik_key'] ) && ! $_REQUEST['blik_key'] )
	) {
		wp_send_json_error();
	}

	$imoje_api = imoje_get_api_instance( sanitize_text_field( $_REQUEST['method'] ) );

	if ( ! $imoje_api ) {
		wp_send_json_error();
	}

	$order = wc_get_order( sanitize_text_field( $_REQUEST['order_id'] ) );

	if ( ! $order ) {
		wp_send_json_error();
	}

	$request_order_id = sanitize_text_field( $_REQUEST['order_id'] );

	$transaction = $imoje_api->createTransaction(
		$imoje_api->prepareBlikOneclickData(
			sanitize_text_field( $_REQUEST['profile_id'] ),
			$order->get_total(),
			$order->get_currency(),
			$request_order_id,
			$request_order_id,
			sanitize_text_field( $_SERVER['REMOTE_ADDR'] ),
			sanitize_text_field( $_REQUEST['blik_key'] )
		),
		$imoje_api->getDebitBlikProfileUrl()
	);

	if ( $transaction['success'] ) {
		wp_send_json_success( imoje_prepare_success_response( $transaction ) );
	}

	wp_send_json_error();
}

// endregion

/**
 * @param $method
 *
 * @return false|Api
 */
function imoje_get_api_instance( $method ) {

	$options = imoje_get_options( $method );

	if ( ! $options
	     || ! $options['authorization_token']
	     || ! $options['merchant_id']
	     || ! $options['service_id']
	) {
		return false;
	}

	return new Api(
		$options['authorization_token'],
		$options['merchant_id'],
		$options['service_id'],
		Imoje_Helper::check_is_config_value_selected( $options['sandbox'] )
			? Util::ENVIRONMENT_SANDBOX
			: Util::ENVIRONMENT_PRODUCTION
	);
}

/**
 * @param array $transaction
 *
 * @return array
 */
function imoje_prepare_success_response( $transaction ) {

	$array = [
		'transaction' => [
			'status' => $transaction['body']['transaction']['status'],
			'id'     => $transaction['body']['transaction']['id'],
		],
	];

	if ( isset( $transaction['body']['transaction']['statusCode'] ) && $transaction['body']['transaction']['statusCode'] ) {
		$array['error'] = imoje_get_error_message( $transaction['body']['transaction']['statusCode'] );
	}

	if ( isset( $transaction['body']['code'] ) && $transaction['body']['code'] ) {
		$array['code'] = $transaction['body']['code'];
	}

	if ( isset( $transaction['body']['newParamAlias'] ) && $transaction['body']['code'] ) {
		$array['newParamAlias'] = $transaction['body']['newParamAlias'];
	}

	return $array;
}

/**
 * @return array
 */
function imoje_get_options( $method ) {

	return get_option( 'woocommerce_' . $method . '_settings' );
}

// region blik

/**
 * @param string $code
 *
 * @return string
 */
function imoje_get_error_message( $code ) {

	$tEnterT6  = __( 'Insert BLIK code.', 'imoje' );
	$tTryAgain = __( 'Please try again.', 'imoje' );

	switch ( $code ) {
		case 'BLK-ERROR-210000':
			return __( 'Payment failed.', 'imoje' ) . ' ' . $tTryAgain;
		case 'BLK-ERROR-210001':
			return __( 'Technical break in your bank. Pay later or use another bank\'s application.', 'imoje' );
		case 'BLK-ERROR-210002':
			return __( 'Alias not found. To proceed the payment you need to pay with BLIK code.', 'imoje' );
		case 'BLK-ERROR-210003':
		case 'BLK-ERROR-210004':
			return __( 'Alias declined. To proceed the payment you need to pay with BLIK code.', 'imoje' );
		case 'BLK-ERROR-210005':
			$msg = __( 'You have entered wrong BLIK code.', 'imoje' );
			break;
		case 'BLK-ERROR-210006':
			$msg = __( 'BLIK code expired.', 'imoje' );
			break;
		case 'BLK-ERROR-210007':
		case 'BLK-ERROR-210008':
			$msg = __( 'Something went wrong with BLIK code.', 'imoje' );
			break;
		case 'BLK-ERROR-210009':
			$msg = __( 'Payment declined at the banking application.', 'imoje' );
			break;
		case 'BLK-ERROR-210010':
		case 'BLK-ERROR-210011':
			$msg = __( 'Payment failed - not confirmed on time in the banking application.', 'imoje' );
			break;
		case 'BLK-ERROR-210012':
			$msg = __( 'Inserted wrong PIN code in banking application.', 'imoje' );
			break;
		case 'BLK-ERROR-210013':
			$msg = __( 'Payment failed (security).', 'imoje' );
			break;
		case 'BLK-ERROR-210014':
			$msg = __( 'Limit exceeded in your banking application.', 'imoje' );
			break;
		case 'BLK-ERROR-210015':
			$msg = __( 'Insufficient funds in your bank account.', 'imoje' );
			break;
		case 'BLK-ERROR-210016':
			$msg = __( 'Issuer declined.', 'imoje' );
			break;
		case 'BLK-ERROR-210017':
			$msg = __( 'Transaction not found.', 'imoje' );
			break;
		case 'BLK-ERROR-210018':
			$msg = __( 'Bad IBAN.', 'imoje' );
			break;
		case 'BLK-ERROR-210019':
			$msg = __( 'Transfer not possible.', 'imoje' );
			break;
		case 'BLK-ERROR-210020':
			$msg = __( 'Return late.', 'imoje' );
			break;
		case 'BLK-ERROR-210021':
			$msg = __( 'Return amount exceeded.', 'imoje' );
			break;
		case 'BLK-ERROR-210022':
			$msg = __( 'Transfer late.', 'imoje' );
			break;
		default:
			return __( 'Payment failed.', 'imoje' ) . ' ' . $tEnterT6;
	}

	return $msg . ' ' . $tEnterT6;
}
// endregion

add_action( 'updated_option', 'imoje_leasenow_updated_settings', 10, 3 );

/**
 * @param string $option
 * @param mixed  $old_value
 * @param mixed  $value
 *
 * @return void
 */
function imoje_leasenow_updated_settings( $option, $old_value, $value ) {


	if ( $option !== 'woocommerce_imoje_leasenow_settings' ) {

		return;
	}

	$api = new Api( $value['authorization_token'], $value['merchant_id'], $value['service_id'], $value['sandbox'] === "no"
		? Util::ENVIRONMENT_PRODUCTION
		: Util::ENVIRONMENT_SANDBOX );

	$serviceInfo = $api->getServiceInfo();

	if ( ! $serviceInfo['success'] ) {

		wp_admin_notice( __( 'Inserted invalid credentials', 'imoje' ), [ 'type' => 'error', 'dismissible' => true ] );

		return;
	}

	$serviceInfo = $serviceInfo['body'];

	// save for leasenow amount to compare product price
	foreach ( $serviceInfo['service']['paymentMethods'] as $paymentMethod ) {
		if ( $paymentMethod['paymentMethodCode'] === Util::getPaymentMethodCode( 'lease_now' ) && $paymentMethod['currency'] === 'PLN' ) {

			$value['product_min_amount'] = $paymentMethod['transactionLimits']['minTransaction']['value'];

			update_option( 'woocommerce_imoje_leasenow_settings', $value );
		}
	}
}

// display button on a product list
add_action( 'woocommerce_after_shop_loop_item_title', 'imoje_leasenow_woocommerce_after_shop_loop_item_title' );

// display button on product page
add_action( 'woocommerce_after_add_to_cart_form', 'imoje_leasenow_woocommerce_after_add_to_cart_form' );

/**
 * @return void
 */
function imoje_leasenow_woocommerce_after_shop_loop_item_title() {
	imoje_leasenow_load_template_leasenow_button( 'image_scale_list' );
}

/**
 * @return void
 */
function imoje_leasenow_woocommerce_after_add_to_cart_form() {
	imoje_leasenow_load_template_leasenow_button( 'image_scale_product' );
}

/**
 * @param string $scale_option_name
 *
 * @return void
 */
function imoje_leasenow_load_template_leasenow_button( $scale_option_name ) {

	$plugin_options = imoje_get_options( 'imoje_leasenow' );

	if ( ! isset( $plugin_options['product_min_amount'] ) ) {
		return;
	}

	$image_scale = (int) $plugin_options[ $scale_option_name ];

	if ( $image_scale <= 0 ) {
		return;
	}

	/** @var WC_Product $product */
	global $product;

	if ( Util::convertAmountToFractional( $product->get_price() ) < $plugin_options['product_min_amount'] ) {
		return;
	}

	global $wp_query;

	$wp_query->query_vars['leasenow_image_scale'] = $image_scale;

	load_template( WOOCOMMERCE_IMOJE_PLUGIN_DIR . 'includes/templates/leasenow_button.php', false );
}
