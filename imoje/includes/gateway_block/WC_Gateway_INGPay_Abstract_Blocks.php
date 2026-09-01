<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Imoje\Payment\Util;

class WC_Gateway_INGPay_Abstract_Blocks extends AbstractPaymentMethodType {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @return void
	 */
	public function initialize() {}

	/**
	 * @return string[]
	 */
	public function get_payment_method_script_handles() {

		$common_handle     = 'imoje-common-block';
		$common_asset_path = WOOCOMMERCE_IMOJE_PLUGIN_DIR . 'build/js/imoje_common.asset.php';

		if ( ! wp_script_is( $common_handle, 'registered' ) ) {

			if ( ! file_exists( $common_asset_path ) ) {

				$this->log_missing_asset( $common_asset_path );

				return [];
			}

			$common_asset = require $common_asset_path;

			wp_register_script(
				$common_handle,
				WOOCOMMERCE_IMOJE_PLUGIN_URL . 'build/js/imoje_common.js',
				array_merge(
					[ 'wc-blocks-registry', 'wc-settings', 'wp-element' ],
					$common_asset['dependencies']
				),
				$common_asset['version'],
				true
			);
		}

		$handle = $this->name . '-block';
		$asset_path = WOOCOMMERCE_IMOJE_PLUGIN_DIR . 'build/js/' . $this->name . '.asset.php';

		if ( ! file_exists( $asset_path ) ) {

			$this->log_missing_asset( $asset_path );

			return [];
		}

		$asset = require $asset_path;

		wp_register_script(
			$handle,
			WOOCOMMERCE_IMOJE_PLUGIN_URL . 'build/js/' . $this->name . '.js',
			array_merge(
				[ $common_handle, 'wc-blocks-registry', 'wc-settings', 'wp-element' ],
				$asset['dependencies']
			),
			$asset['version'],
			true
		);

		return [ $handle ];
	}

	/**
	 * @return array
	 */
	public function get_payment_method_data() {

		$settings = get_option( 'woocommerce_' . $this->name . '_settings', [] );

		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		$settings = array_intersect_key( $settings, array_flip( [
			'title',
			'description',
			'hide_brand',
			'view_field',
			'currencies',
		] ) );

		$currencies = [];
		if ( ! empty( $settings['currencies'] ) && is_array( $settings['currencies'] ) ) {
			$currencies = array_map( 'strtolower', array_filter( $settings['currencies'] ) );
		}
		$settings['currencies'] = array_values( $currencies );

		$hide_brand = isset( $settings['hide_brand'] ) && $settings['hide_brand'] === 'yes';
		$logo = $hide_brand ? '' : $this->get_image_src( $this->name . '.' . INGPay_Helper::get_logo_ext($this->name) );

		return array_merge(
			[
				'name'                  => $this->name,
				'logo'                  => $logo,
				'settings'              => $settings,
				'legal_copy'            => $this->get_legal_copy(),
				'choose_payment_method' => __( 'Choose a payment method', 'imoje' ),
				'ajax_nonce'            => wp_create_nonce( 'imoje_ajax_nonce' ),
			],
			$this->extra_data()
		);
	}

	/**
	 * @param string $asset_path
	 *
	 * @return void
	 */
	protected function log_missing_asset( $asset_path ) {

		wc_get_logger()->error(
			sprintf(
			/* translators: %s: absolute path to the missing build asset */
				__( 'imoje: missing block checkout asset "%s". The payment method will not be shown in block checkout.', 'imoje' ),
				$asset_path
			),
			[ 'source' => 'imoje' ]
		);
	}

	/**
	 * @return array
	 */
	protected function extra_data() {
		return [];
	}

	/**
	 * @return array
	 */
	protected function get_legal_copy() {

		$locale = get_locale();
		$lang   = ( $locale === 'pl_PL' || $locale === 'pl' )
			? Util::LANG_PL
			: Util::LANG_EN;

		return [
			'regulation_url'  => Util::getDocUrl( $lang, Util::REGULATION ),
			'regulation_text' => __( 'Regulations of ING Pay', 'imoje' ),
			'iodo_url'        => Util::getDocUrl( $lang, Util::IODO ),
			'iodo_text'       => __( 'Information on personal data ING Pay', 'imoje' ),
			'consent_text'    => __( 'I declare that I have read and accept the {regulation} and {iodo}.', 'imoje' ),
		];
	}

	/**
	 * @param string $file
	 *
	 * @return string
	 */
	protected function get_image_src( $file ) {
		return WOOCOMMERCE_IMOJE_PLUGIN_URL . 'assets/images/' . $file;
	}

	/**
	 * @param string $class
	 *
	 * @return WC_Payment_Gateway
	 */
	protected function get_gateway( $class ) {

		$payment_gateways = WC()->payment_gateways();

		$gateways = $payment_gateways
			? $payment_gateways->payment_gateways()
			: [];

		if ( isset( $gateways[ $this->name ] ) && $gateways[ $this->name ] instanceof $class ) {
			return $gateways[ $this->name ];
		}

		return new $class();
	}
}
