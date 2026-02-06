<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use Imoje\Payment\Util;

/**
 * Class WC_Gateway_Imoje_RestApi_Blocks
 */
class WC_Gateway_Imoje_RestApi_Blocks extends AbstractPaymentMethodType {
	/**
	 * @var string
	 */
	private $regulation;

	/**
	 * @var string
	 */
	private $iodo;

	/**
	 * @return void
	 */
	public function initialize() {
		$locale = get_locale();

		$locale = ( $locale === 'pl_PL' || $locale === 'pl' )
			? Util::LANG_PL
			: Util::LANG_EN;

		$this->regulation = Util::getDocUrl( $locale, Util::REGULATION );
		$this->iodo       = Util::getDocUrl( $locale, Util::IODO );

		add_action( 'wp_enqueue_scripts', [
			$this,
			'enqueue_payment_scripts',
		] );
	}

	/**
	 * @return void
	 */
	public function enqueue_payment_scripts() {
		if ( is_checkout() && CartCheckoutUtils::is_checkout_block_default() ) {

			$this->enqueue_common_payment_script();
			$this->enqueue_imoje_blik_payment_script();
			$this->enqueue_imoje_payment_script();
			$this->enqueue_imoje_paylater_payment_script();
			$this->enqueue_imoje_cards_payment_script();
			$this->enqueue_imoje_pbl_payment_script();
			$this->enqueue_imoje_visa_payment_script();
			$this->enqueue_imoje_installments_payment_script();
			$this->enqueue_imoje_wallet_payment_script();
			$this->enqueue_imoje_wt_payment_script();
			$this->enqueue_imoje_leasenow_payment_script();
		}
	}

	/**
	 * @return void
	 */
	private function enqueue_common_payment_script() {
		wp_enqueue_script(
			'imoje-common-payment-block',
			plugins_url( '../../assets/js/checkout/imoje-common-block.min.js', __FILE__ ),
			[ 'wc-blocks-registry' ],
			'1.0.0',
			true
		);

		wp_localize_script(
			'imoje-common-payment-block',
			'imoje_common_js_object',
			[
				'legal_copy'            => [
					'regulation_url'  => esc_url( $this->regulation ),
					'iodo_url'        => esc_url( $this->iodo ),
					'regulation_text' => esc_html__( 'Regulations of imoje', 'imoje' ),
					'iodo_text'       => esc_html__( 'Information on personal data imoje', 'imoje' ),
					'consent_text'    => esc_html__( 'I declare that I have read and accept {regulation} and {iodo}.', 'imoje' ),
				],
				'choose_payment_method' => __( 'Choose payment channel if is available. In other way choose another payment method', 'imoje' ),
			]
		);
	}

	/**
	 * @return void
	 */
	private function enqueue_imoje_blik_payment_script() {
		wp_enqueue_script(
			'imoje-blik-payment-block',
			plugins_url( '../../assets/js/checkout/imoje-blik-block.min.js', __FILE__ ),
			[
				'wp-element',
				'wc-blocks-registry',
			],
			'1.0.0',
			true
		);

		$imojeBlik = new WC_Gateway_ImojeBlik();

		wp_localize_script(
			'imoje-blik-payment-block',
			'imoje_blik_js_object',
			[
				'name_blik'            => WC_Gateway_ImojeBlik::PAYMENT_METHOD_NAME,
				'logo_blik'            => plugins_url( '../../assets/images/imoje_blik.png', __FILE__ ),
				'logo_blik_oneclick'   => '',
				'settings_blik'        => get_option( 'woocommerce_imoje_blik_settings', [] ),
				'blik_text'            => __( 'Enter the correct 6-digit BLIK code.', 'imoje' ),
				'payment_methods_blik' => $this->get_block_checkout_tooltip( $imojeBlik->get_payment_channels() ),

			]
		);
	}

	/**
	 * @return void
	 */
	private function enqueue_imoje_payment_script() {
		wp_enqueue_script(
			'imoje-payment-block',
			plugins_url( '../../assets/js/checkout/imoje-block.min.js', __FILE__ ),
			[
				'wp-element',
				'wc-blocks-registry',
			],
			'1.0.0',
			true
		);

		wp_localize_script(
			'imoje-payment-block',
			'imoje_js_object',
			[
				'name_imoje'     => WC_Gateway_Imoje::PAYMENT_METHOD_NAME,
				'logo_imoje'     => plugins_url( '../../assets/images/imoje.png', __FILE__ ),
				'settings_imoje' => get_option( 'woocommerce_imoje_settings', [] ),
			]
		);
	}

	/**
	 * @return void
	 */
	private function enqueue_imoje_paylater_payment_script() {
		wp_enqueue_script(
			'imoje-paylater-payment-block',
			plugins_url( '../../assets/js/checkout/imoje-paylater-block.min.js', __FILE__ ),
			[
				'wp-element',
				'wc-blocks-registry',
			],
			'1.0.0',
			true
		);

		$imojePaylater = new WC_Gateway_ImojePaylater();

		wp_localize_script(
			'imoje-paylater-payment-block',
			'imoje_paylater_js_object',
			[
				'name_paylater'            => WC_Gateway_ImojePaylater::PAYMENT_METHOD_NAME,
				'logo_paylater'            => plugins_url( '../../assets/images/imoje_paylater.png', __FILE__ ),
				'settings_paylater'        => get_option( 'woocommerce_imoje_paylater_settings', [] ),
				'payment_methods_paylater' => $this->get_block_checkout_tooltip( $imojePaylater->get_payment_channels() ),
				'twisto_legal'             => __( 'I agree to provide Twisto S.A. with my transaction details in the imoje payment gateway in order to make an offer to finance my purchases.', 'imoje' ),
			]
		);
	}

	/**
	 * @return void
	 */
	private function enqueue_imoje_cards_payment_script() {
		wp_enqueue_script(
			'imoje-cards-payment-block',
			plugins_url( '../../assets/js/checkout/imoje-cards-block.min.js', __FILE__ ),
			[
				'wp-element',
				'wc-blocks-registry',
			],
			'1.0.0',
			true
		);

		wp_localize_script(
			'imoje-cards-payment-block',
			'imoje_cards_js_object',
			[
				'name_cards'     => WC_Gateway_ImojeCards::PAYMENT_METHOD_NAME,
				'logo_cards'     => plugins_url( '../../assets/images/imoje_cards.png', __FILE__ ),
				'settings_cards' => get_option( 'woocommerce_imoje_cards_settings', [] ),
			]
		);
	}

	/**
	 * @return void
	 */
	private function enqueue_imoje_pbl_payment_script() {
		wp_enqueue_script(
			'imoje-pbl-payment-block',
			plugins_url( '../../assets/js/checkout/imoje-pbl-block.min.js', __FILE__ ),
			[
				'wp-element',
				'wc-blocks-registry',
			],
			'1.0.0',
			true
		);

		$imojePbl = new WC_Gateway_ImojePbl;

		wp_localize_script(
			'imoje-pbl-payment-block',
			'imoje_pbl_js_object',
			[
				'name_pbl'            => WC_Gateway_ImojePbl::PAYMENT_METHOD_NAME,
				'logo_pbl'            => plugins_url( '../../assets/images/imoje_pbl.png', __FILE__ ),
				'settings_pbl'        => get_option( 'woocommerce_imoje_pbl_settings', [] ),
				'payment_methods_pbl' => $this->get_block_checkout_tooltip( $imojePbl->get_payment_channels() ),
			]
		);
	}

	/**
	 * @return void
	 */
	private function enqueue_imoje_visa_payment_script() {
		wp_enqueue_script(
			'imoje-visa-payment-block',
			plugins_url( '../../assets/js/checkout/imoje-visa-block.min.js', __FILE__ ),
			[
				'wp-element',
				'wc-blocks-registry',
			],
			'1.0.0',
			true
		);

		wp_localize_script(
			'imoje-visa-payment-block',
			'imoje_visa_js_object',
			[
				'name_visa'     => WC_Gateway_ImojeVisa::PAYMENT_METHOD_NAME,
				'logo_visa'     => plugins_url( '../../assets/images/imoje_visa.png', __FILE__ ),
				'settings_visa' => get_option( 'woocommerce_imoje_visa_settings', [] ),
			]
		);
	}

	/**
	 * @return void
	 */
	private function enqueue_imoje_installments_payment_script() {
		wp_enqueue_script(
			'imoje-installments-payment-block',
			plugins_url( '../../assets/js/checkout/imoje-installments-block.min.js', __FILE__ ),
			[
				'wp-element',
				'wc-blocks-registry',
			],
			'1.0.0',
			true
		);

		$imojeInstallments = new WC_Gateway_ImojeInstallments();

		wp_localize_script(
			'imoje-installments-payment-block',
			'imoje_installments_js_object',
			[
				'name_installments'     => WC_Gateway_ImojeInstallments::PAYMENT_METHOD_NAME,
				'logo_installments'     => plugins_url( '../../assets/images/imoje_installments.png', __FILE__ ),
				'settings_installments' => get_option( 'woocommerce_imoje_installments_settings', [] ),
				'calculator_data'       => $imojeInstallments->get_calculator_data(),
			]
		);
	}

	/**
	 * @return void
	 */
	private function enqueue_imoje_wallet_payment_script() {
		wp_enqueue_script(
			'imoje-wallet-payment-block',
			plugins_url( '../../assets/js/checkout/imoje-wallet-block.min.js', __FILE__ ),
			[
				'wp-element',
				'wc-blocks-registry',
			],
			'1.0.0',
			true
		);

		$imojeWallet = new WC_Gateway_ImojeWallet();

		wp_localize_script(
			'imoje-wallet-payment-block',
			'imoje_wallet_js_object',
			[
				'name_wallet'            => WC_Gateway_ImojeWallet::PAYMENT_METHOD_NAME,
				'logo_wallet'            => plugins_url( '../../assets/images/imoje_wallet.png', __FILE__ ),
				'settings_wallet'        => get_option( 'woocommerce_imoje_wallet_settings', [] ),
				'payment_methods_wallet' => $this->get_block_checkout_tooltip( $imojeWallet->get_payment_channels() ),
			]
		);
	}

	/**
	 * @return void
	 */
	private function enqueue_imoje_leasenow_payment_script() {
		wp_enqueue_script(
			'imoje-leasenow-payment-block',
			plugins_url( '../../assets/js/checkout/imoje-leasenow-block.min.js', __FILE__ ),
			[
				'wp-element',
				'wc-blocks-registry',
			],
			'1.0.0',
			true
		);

		wp_localize_script(
			'imoje-leasenow-payment-block',
			'imoje_leasenow_js_object',
			[
				'name_leasenow'     => WC_Gateway_ImojeLeasenow::PAYMENT_METHOD_NAME,
				'logo_leasenow'     => plugins_url( '../../assets/images/imoje_leasenow.png', __FILE__ ),
				'settings_leasenow' => get_option( 'woocommerce_imoje_leasenow_settings', [] ),
			]
		);
	}

	/**
	 * @return void
	 */
	private function enqueue_imoje_wt_payment_script() {
		wp_enqueue_script(
			'imoje-wt-payment-block',
			plugins_url( '../../assets/js/checkout/imoje-wt-block.min.js', __FILE__ ),
			[
				'wp-element',
				'wc-blocks-registry',
			],
			'1.0.0',
			true
		);

		wp_localize_script(
			'imoje-wt-payment-block',
			'imoje_wt_js_object',
			[
				'name_wt'     => WC_Gateway_ImojeWt::PAYMENT_METHOD_NAME,
				'logo_wt'     => plugins_url( '../../assets/images/imoje_wt.png', __FILE__ ),
				'settings_wt' => get_option( 'woocommerce_imoje_wt_settings', [] ),
			]
		);
	}

	/**
	 * @param array $payment_method
	 *
	 * @return array
	 */
	private function get_block_checkout_tooltip( $payment_method ) {
		return array_values( array_map( function ( $method ) {
			if ( isset( $method['limit'] ) ) {
				$method['tooltip'] = Imoje_Helper::get_tooltip_payment_channel( $method );
			}

			return $method;
		}, $payment_method ) );
	}
}
