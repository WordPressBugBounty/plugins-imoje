<?php

use Imoje\Payment\Api;
use Imoje\Payment\CartData;
use Imoje\Payment\Invoice;
use Imoje\Payment\LeaseNow;
use Imoje\Payment\Notification;
use Imoje\Payment\Util;

/**
 * Class Helper
 */
class Helper {

	/**
	 * @return string
	 */
	public static function get_version() {
		$wc = WC();

		return get_file_data( WOOCOMMERCE_IMOJE_PLUGIN_DIR, [ 'Version' ], 'plugin' )[0] . ';woocommerce_' . $wc->version;
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
	public static function leasenow_get_product_category( $post_id, $taxonomy, $before = '', $sep = '', $after = '' ) {
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
	 * @param WC_Order $order
	 * @param string   $config_value
	 * @param bool     $is_api
	 *
	 * @return string
	 */
	public static function get_lease_now( $order, $config_value, $is_api ) {

		if ( ! self::check_is_config_value_selected( $config_value ) ) {
			return '';
		}

		$leasenow = new LeaseNow();

		foreach ( $order->get_items() as $item ) {

			$product    = $item->get_product();
			$product_id = $item->get_product_id();
			$name       = $product->get_title();

			if ( method_exists( $product, 'get_attribute_summary' )
			     && $attribute_summary = $product->get_attribute_summary() ) {
				$name .= ' - ' . $attribute_summary
					? ( ' - ' . $product->get_attribute_summary() )
					: '';
			}

			$tax_rates = WC_Tax::get_rates( $item->get_tax_class() );
			$tax_rate  = 0;
			if ( ! empty( $tax_rates ) ) {
				$tax_rate = reset( $tax_rates );
				$tax_rate = (int) $tax_rate['rate'];
			}

			$leasenow->addItem(
				$item->get_product_id(),
				self::leasenow_get_product_category(
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

		return $leasenow->prepare( $is_api );
	}

	/**
	 * @param WC_Order $order
	 * @param bool     $config_value
	 * @param bool     $is_api
	 *
	 * @return array|string
	 */
	public static function get_invoice( $order, $config_value, $is_api, $meta_tax_field_name ) {

		$invoice = new Invoice();

		if ( ! self::check_is_config_value_selected( $config_value )
		     || ! $invoice->validateCurrency( $order->get_currency() ) ) {
			return [];
		}

		$cart = self::get_cart( $order );

		if ( empty( $cart['items'] ) ) {
			return [];
		}

		foreach ( $cart['items'] as $item ) {

			$tax = null;
			try {
				$tax = constant( '\Imoje\Payment\Invoice::TAX_' . $item['vat'] );
			} catch( Exception $e ) {
			}

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

		if ( isset( $cart['shipping'] ) && $cart['shipping'] ) {
			$invoice->addItem( $cart['shipping']['name'],
				1,
				1,
				constant( '\Imoje\Payment\Invoice::TAX_' . $cart['shipping']['vat'] ),
				$cart['shipping']['amount']
			);
		}

		$invoice->setBuyer( Invoice::BUYER_PERSON,
			$order->get_billing_email(),
			$cart['address']['billing']['name'],
			$cart['address']['billing']['street'],
			$cart['address']['billing']['city'],
			$cart['address']['billing']['postalCode'],
			$cart['address']['billing']['country'] );

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

		return $invoice->prepare( $is_api );
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	public static function get_cart( $order ) {

		$cart_data = new CartData();

		$cart_data->setCreatedAt( strtotime( $order->get_date_created() ) );
		$cart_data->setAmount( Util::convertAmountToFractional( $order->get_total() ) );

		foreach ( $order->get_items() as $item ) {

			$item_tax_class = $item->get_tax_class();

			$rate = '';
			if ( strtolower( $item_tax_class ) === Invoice::SHOP_TAX_EXEMPT ) {
				$rate = explode( '_', Invoice::TAX_EXEMPT )[1];
			}

			if ( ! $rate ) {
				$rate             = 0;
				$tax_rate_product = current( WC_Tax::get_rates( $item_tax_class ) );
				if ( $tax_rate_product && isset( $tax_rate_product['rate'] ) ) {
					$rate = (float) $tax_rate_product['rate'];
				}
				// endregion
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

			return $cart_data->prepareCartDataArray();
		}

		$shipping_data   = $shipping_data->get_data();
		$id_shipping_tax = null;
		foreach ( $order->get_items( 'shipping' ) as $item_id => $item_tax ) {
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

		return $cart_data->prepareCartDataArray();
	}

	/**
	 * @param string $service_id
	 * @param string $service_key
	 * @param string $debug_mode
	 *
	 * @throws Exception
	 */
	public static function check_notification( $service_id, $service_key, $debug_mode ) {
		$notification = new Notification(
			$service_id,
			$service_key,
			self::check_is_config_value_selected( $debug_mode )
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
	 * @param int    $order_id
	 * @param string $authorization_token
	 * @param string $merchant_id
	 * @param string $service_id
	 * @param float  $amount
	 *
	 * @return bool
	 * @throws Exception
	 */
	public static function process_refund( $order_id, $authorization_token, $merchant_id, $service_id, $amount, $environment = '' ) {

		$order = wc_get_order( $order_id );

		$api = new Api( $authorization_token, $merchant_id, $service_id, $environment );

		$refund = $api->createRefund(
			$api->prepareRefundData(
				Util::convertAmountToFractional( $amount )
			),
			$order->get_meta( 'imoje_transaction_uuid' )
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
	 * @param array $refund
	 *
	 * @return string|null
	 */
	public static function get_refund_error( $refund ) {

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
	public static function get_tooltip_payment_channel( $payment_method ) {

		$is_limit = $payment_method['limit'] === 1;

		return sprintf( __( 'The payment method is available for transactions %s %s %s', 'imoje' ),
			( $is_limit
				? __( 'above', 'imoje' )
				: __( 'below', 'imoje' ) ),
			( number_format( esc_attr( $is_limit
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

		foreach ( WC_Gateway_Imoje_Abstract::payment_method_list() as $key => $payment_method ) {

			if ( $payment_method['is_payment_link'] ) {
				$array[ $key ] = $key;
			}
		}

		return $array;
	}
}
