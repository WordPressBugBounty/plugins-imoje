<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Imoje\Payment\Api;
use Imoje\Payment\CartData;
use Imoje\Payment\Invoice;
use Imoje\Payment\LeaseNow;
use Imoje\Payment\Notification;
use Imoje\Payment\Util;

/**
 * Class INGPay_Helper
 */
class INGPay_Helper {

	/**
	 * @return string
	 */
	public static function get_version() {
		$wc = WC();

		return get_file_data( WOOCOMMERCE_IMOJE_PLUGIN_FILE_DIR, [ 'Version' ], 'plugin' )[0] . ';woocommerce_' . $wc->version;
	}

	/**
	 * @param string $payment_method_name
	 *
	 * @return string
	 */
	public static function get_logo_ext( $payment_method_name ) {

		switch ( $payment_method_name ) {
			case WC_Gateway_INGPayVisa::PAYMENT_METHOD_NAME:
			case WC_Gateway_INGPayWallet::PAYMENT_METHOD_NAME:
				$ext = 'png';
				break;
			default:
				$ext = 'svg';
		}

		return $ext;
	}

	/**
	 * @param string $name
	 * @param string $display_name
	 * @param string $default_description
	 * @param bool   $is_payment_link
	 *
	 * @return array
	 */
	static function get_gateway_details( $name, $display_name, $default_description, $is_payment_link ) {
		return [
			'name'                => $name,
			'display_name'        => $display_name,
			'default_description' => $default_description,
			'is_payment_link'     => $is_payment_link,
		];
	}

	/**
	 * @param string $config_value
	 *
	 * @return bool
	 */
	public static function check_is_config_value_selected( $config_value ) {
		return $config_value === 'yes';
	}

	/**
	 * @param string $post_id
	 * @param string $taxonomy
	 * @param string $before
	 * @param string $sep
	 * @param string $after
	 *
	 * @return array|false|int|string|WP_Error|WP_Term|WP_Term[]|null
	 */
	public static function imoje_leasenow_get_product_category( $post_id, $taxonomy, $before = '', $sep = '', $after = '' ) {
		$terms = get_the_terms( $post_id, $taxonomy );

		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		if ( empty( $terms ) ) {
			return false;
		}

		$links = [];

		foreach ( $terms as $term ) {
			$link = get_term_link( $term, $taxonomy );
			if ( is_wp_error( $link ) ) {
				return $link;
			}
			$links[] = $term->name;
		}

		$term_links = apply_filters( "term_links-$taxonomy", $links );  // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

		return $before . implode( $sep, $term_links ) . $after;
	}

	/**

	 * @param WC_Order_Item_Product $item
	 *
	 * @return array
	 */
	public static function get_item_tax_rate( $item ) {

		$exempt_postfix = explode( '_', Invoice::TAX_EXEMPT )[1];

		$item_tax_class       = $item->get_tax_class();
		$item_tax_class_lower = strtolower( $item_tax_class );

		$rate        = '';
		$basis       = '';
		$is_exempted = false;

		$product         = $item->get_product();
		$item_tax_status = $product ? $product->get_tax_status() : 'taxable';

		if ( strpos( $item_tax_class_lower, 'zw_' ) === 0 ) {

			$basis_exempt = Invoice::getBasisExempt( substr( $item_tax_class_lower, strpos( $item_tax_class_lower, '_' ) + 1 ) );
			if ( $basis_exempt ) {
				$basis = $basis_exempt;
			}

			$is_exempted = true;
		}

		if ( $item_tax_class_lower === Invoice::SHOP_TAX_EXEMPT || $is_exempted ) {
			$rate = $exempt_postfix;
		}

		if ( ! $rate && in_array( $item_tax_status, [ 'none', 'shipping' ], true ) ) {
			$rate = $exempt_postfix;
		}

		if ( ! $rate ) {
			$rate             = 0;
			$tax_rate_product = current( WC_Tax::get_rates( $item_tax_class ) );
			if ( $tax_rate_product && isset( $tax_rate_product['rate'] ) ) {
				$rate = (float) $tax_rate_product['rate'];
			}
		}

		return [
			'rate'  => $rate,
			'basis' => $basis,
		];
	}

	/**
	 * @param WC_Order $order
	 * @param string   $config_value
	 *
	 * @return array
	 */
	public static function get_lease_now( WC_Order $order, $config_value ) {

		if ( ! self::check_is_config_value_selected( $config_value ) ) {
			return [];
		}

		$lease = new LeaseNow();

		foreach ( $order->get_items() as $item ) {

			$product    = $item->get_product();
			$product_id = $item->get_product_id();
			$name       = $product->get_title();

			if ( method_exists( $product, 'get_attribute_summary' ) ) {
				$attribute_summary = $product->get_attribute_summary();
				if ( $attribute_summary ) {
					$name .= ' - ' . $attribute_summary;
				}
			}

			$tax_rate = self::get_item_tax_rate( $item )['rate'];

			if ( ! is_numeric( $tax_rate ) ) {
				$tax_rate = 'TAX_' . $tax_rate;
			}

			$lease->addItem(
				$item->get_product_id(),
				self::imoje_leasenow_get_product_category(
					( $product->is_type( 'simple' )
						? $product_id
						: $product->get_parent_id() ),
					'product_cat',
					'',
					', ' ),
				$name,
				Util::convertAmountToFractional( $order->get_item_total( $item ) ),
				Util::convertAmountToFractional( $order->get_item_tax( $item ) ),
				$tax_rate,
				$item->get_quantity(),
				$product->get_permalink()
			);
		}

		return $lease->prepare();
	}

	/**
	 * @param WC_Order $order
	 * @param string   $config_value
	 * @param string   $meta_tax_field_name
	 *
	 * @return array
	 */
	public static function get_invoice( WC_Order $order, $config_value, $meta_tax_field_name ) {


		$invoice = new Invoice();

		if ( ! self::check_is_config_value_selected( $config_value )
		     || ! $invoice->validateCurrency( $order->get_currency() ) ) {
			return [];
		}

		$cart = self::get_cart( $order );

		$items = $cart->items;

		if ( empty( $items ) ) {
			return [];
		}

		foreach ( $items as $item ) {

			$tax = self::get_invoice_tax_constant( $item['vat'] );

			if ( ! $tax ) {
				return [];
			}

			$invoice->addItem( $item['name'],
				$item['id'],
				$item['quantity'],
				$tax,
				$item['amount']
			);
		}

		$basis_for_vat_exemption = $cart->getBasis();

		if ( $basis_for_vat_exemption ) {
			$invoice->setBasis( $basis_for_vat_exemption );
		}

		$shipping = $cart->getShipping();

		if ( $shipping ) {

			$shipping_tax = self::get_invoice_tax_constant( $shipping['vat'] );

			if ( ! $shipping_tax ) {
				return [];
			}

			$invoice->addItem( $shipping['name'],
				1,
				1,
				$shipping_tax,
				$shipping['amount']
			);
		}

		$billing = $cart->getAddressBilling();

		$invoice->setBuyer( Invoice::BUYER_PERSON,
			$order->get_billing_email(),
			$billing['name'],
			$billing['street'],
			$billing['city'],
			$billing['postalCode'],
			$billing['country'] );

		$vat_number = trim( $order->get_meta( $meta_tax_field_name ) );

		if ( $meta_tax_field_name && $vat_number ) {

			$vat_country_alpha2 = Invoice::VAT_COUNTRY_ALPHA2;

			if ( strlen( $vat_number ) > 2 ) {
				$first_two = substr( $vat_number, 0, 2 );

				if ( ctype_alpha( $first_two ) ) {
					$vat_country_alpha2 = $first_two;
				}
			}

			$invoice->setCompanyBuyer( $vat_country_alpha2, $vat_number, $order->get_billing_company()
				?: '' );
		}

		return $invoice->prepare();
	}

	/**
	 * @param int|float|string $vat
	 *
	 * @return string
	 */
	public static function get_invoice_tax_constant( $vat ) {

		$constant_name = '\Imoje\Payment\Invoice::TAX_' . $vat;

		if ( ! defined( $constant_name ) ) {

			wc_get_logger()->error(
				sprintf(
					__( 'imoje: unsupported VAT rate "%s" - invoice for ING Ksiegowosc will not be sent', 'imoje' ),
					$vat
				),
				[ 'source' => 'imoje' ]
			);

			return '';
		}

		return constant( $constant_name );
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return CartData
	 */
	public static function get_cart( WC_Order $order ) {

		$cart_data = new CartData();

		$cart_data->createdAt = strtotime( $order->get_date_created() );
		$cart_data->amount    = Util::convertAmountToFractional( $order->get_total() );

		$basis = '';

		foreach ( $order->get_items() as $item ) {

			$tax_info = self::get_item_tax_rate( $item );
			$rate     = $tax_info['rate'];

			if ( $tax_info['basis'] ) {
				$basis = $tax_info['basis'];
			}

			$cart_data->addItem(
				$item->get_id(),
				$rate,
				$item->get_name(),
				Util::convertAmountToFractional( $item->get_total() + $item->get_total_tax() ),
				$item->get_quantity(),
				false
			);
		}

		if ( $basis ) {
			$cart_data->setBasis( $basis );
		}

		$phone = $order->get_billing_phone();

		$cart_data->setAddressBilling(
			$order->get_billing_city(),
			$order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			$phone
				?: '',
			$order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
			$order->get_billing_country(),
			$order->get_billing_postcode()
		);

		if ( $order->get_total_discount( false ) > 0 ) {
			$cart_data->setDiscount(
				0,
				'Zniżka',
				Util::convertAmountToFractional( $order->get_total_discount( false ) )
			);
		}

		$shipping_data = current( $order->get_items( 'shipping' ) );

		if ( ! $shipping_data ) {
			$cart_data->setAddressDelivery(
				$order->get_billing_city(),
				$order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
				$phone
					?: '',
				$order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
				$order->get_billing_country(),
				$order->get_billing_postcode()
			);
			$cart_data->setShipping(
				0,
				'Brak wysyłki',
				0
			);

			return $cart_data;
		}

		$shipping_data   = $shipping_data->get_data();
		$id_shipping_tax = null;
		foreach ( $order->get_items( 'shipping' ) as $item_tax ) {
			$shipping_data   = json_decode( $item_tax, true );
			$id_shipping_tax = current( array_keys( $shipping_data['taxes']['total'] ) );
		}

		$cart_data->setShipping( $id_shipping_tax
			? (float) WC_Tax::_get_tax_rate( $id_shipping_tax )['tax_rate']
			: 0,
			$shipping_data['name'],
			Util::convertAmountToFractional( (float) $order->get_shipping_total() + (float) $order->get_shipping_tax() ) );

		$cart_data->setAddressDelivery(
			$order->get_shipping_city(),
			$order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
			$phone
				?: '',
			$order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2(),
			$order->get_shipping_country(),
			$order->get_shipping_postcode()
		);

		return $cart_data;
	}

	/**
	 * @param int    $order_id
	 * @param string $authorization_token
	 * @param string $merchant_id
	 * @param string $service_id
	 * @param float  $amount
	 * @param string $environment
	 *
	 * @return bool
	 * @throws Exception
	 */
	public static function process_refund( $order_id, $authorization_token, $merchant_id, $service_id, $amount, $environment = '' ) {

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			throw new Exception( __( 'Refund error: order not found.', 'imoje' ) );
		}

		$transaction_uuid = (string) $order->get_meta( 'imoje_transaction_uuid' );

		if ( ! $transaction_uuid ) {
			throw new Exception(
				__( 'Refund error: this order has no imoje transaction id, refund it in the ING Pay panel instead.', 'imoje' )
			);
		}

		$api = new Api( $authorization_token, $merchant_id, $service_id, $environment );

		$refund = $api->createRefund(
			$api->prepareRefundData(
				Util::convertAmountToFractional( $amount )
			),
			$transaction_uuid
		);

		$refund_error = self::get_refund_error( $refund );

		if ( ! $refund['success'] ) {

			$order->add_order_note(
				$refund_error
			);

			throw new Exception( $refund_error );
		}

		if ( $refund['body']['transaction']['status'] === Notification::TRS_SETTLED ) {

			$order->add_order_note(
				sprintf(
					__( 'Refund for amount %s with UUID %s has been correctly processed.', 'imoje' ),
					$amount,
					$refund['body']['transaction']['id']
				)
			);

			return true;
		}

		if ( $refund['body']['transaction']['status'] !== Notification::TRS_NEW ) {

			throw new Exception( $refund_error );
		}

		$order->add_order_note(
			sprintf( __( 'Refund for amount %s with UUID %s has been requested', 'imoje' ),
				$amount,
				$refund['body']['transaction']['id']
			)
		);

		throw new Exception( __( 'A refund has been requested and waiting for completion.', 'imoje' ) );
	}

	/**
	 * @param array $result
	 *
	 * @return string
	 */
	public static function format_api_error( array $result ) {

		$parts = [];

		if ( isset( $result['data']['httpCode'] ) ) {
			$parts[] = 'httpCode=' . $result['data']['httpCode'];
		}

		if ( ! empty( $result['data']['method'] ) && ! empty( $result['data']['url'] ) ) {
			$parts[] = 'request=' . $result['data']['method'] . ' ' . $result['data']['url'];
		}

		if ( ! empty( $result['data']['error'] ) ) {
			$parts[] = 'transport=' . $result['data']['error'];
		}

		if ( ! empty( $result['data']['body'] ) ) {
			$parts[] = 'message=' . ( is_scalar( $result['data']['body'] )
					? $result['data']['body']
					: wp_json_encode( $result['data']['body'] ) );
		}

		if ( ! empty( $result['data']['errors'] ) ) {
			$parts[] = 'errors=' . wp_json_encode( $result['data']['errors'] );
		}

		if ( ! empty( $result['data']['traceId'] ) ) {
			$parts[] = 'traceId=' . $result['data']['traceId'];
		}

		if ( ! empty( $result['data']['response'] ) ) {
			$parts[] = 'response=' . $result['data']['response'];
		}

		$identifiers = [
			'transactionId'     => [ 'transaction', 'id' ],
			'transactionStatus' => [ 'transaction', 'status' ],
			'transactionType'   => [ 'transaction', 'type' ],
			'statusCode'        => [ 'transaction', 'statusCode' ],
			'paymentId'         => [ 'payment', 'id' ],
			'paymentStatus'     => [ 'payment', 'status' ],
		];

		foreach ( $identifiers as $label => $path ) {
			if ( isset( $result['body'][ $path[0] ][ $path[1] ] ) && is_scalar( $result['body'][ $path[0] ][ $path[1] ] ) ) {
				$parts[] = $label . '=' . $result['body'][ $path[0] ][ $path[1] ];
			}
		}

		if ( ! $parts ) {
			return 'no diagnostic data in response';
		}

		return implode( ', ', $parts );
	}

	/**
	 * @param array $refund
	 *
	 * @return string|null
	 */
	public static function get_refund_error( array $refund ) {

		if ( ! $refund['success'] && ( isset( $refund['data']['body'] ) && $refund['data']['body'] ) ) {
			return __( 'Refund error: ', 'imoje' ) . ' ' . $refund['data']['body'];
		}

		return __( 'Refund error.', 'imoje' );
	}

	/**
	 * @param array $payment_method
	 *
	 * @return string
	 */
	public static function get_tooltip_payment_channel( array $payment_method ) {

		$is_limit = $payment_method['limit'] === 1;

		return sprintf( __( 'The payment method is available for transactions %s %s %s', 'imoje' ),
			( $is_limit
				? __( 'above', 'imoje' )
				: __( 'below', 'imoje' ) ),
			( number_format( (float) ( $is_limit
				? $payment_method['min_transaction']
				: $payment_method['max_transaction'] ), 2, ',', ' ' ) ),
			get_woocommerce_currency()
		);
	}

	/**
	 * @return array
	 */
	public static function get_payment_methods_for_payment_link() {

		$array = [];

		foreach ( WC_Gateway_INGPay_Abstract::payment_method_list() as $key => $payment_method ) {

			if ( $payment_method['is_payment_link'] ) {
				$array[ $key ] = $key;
			}
		}

		return $array;
	}
}
