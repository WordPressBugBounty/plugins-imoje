<?php

namespace Imoje\Payment;

/**
 * Class Util
 *
 * @package Imoje\Payment
 */
class Util {

	/**
	 * @const string
	 */
	const METHOD_REQUEST_POST = 'POST';

	/**
	 * @const string
	 */
	const METHOD_REQUEST_GET = 'GET';

	/**
	 * @const string
	 */
	const ENVIRONMENT_PRODUCTION = 'production';

	/**
	 * @const string
	 */
	const ENVIRONMENT_SANDBOX = 'sandbox';

	/**
	 * @const string
	 */
	const REGULATION = 'regulation';

	/**
	 * @const string
	 */
	const IODO = 'iodo';

	/**
	 * @const string
	 */
	const LANG_PL = 'pl';

	/**
	 * @const string
	 */
	const LANG_EN = 'en';

	/**
	 * @var string
	 */
	private static $cdnUrl = 'https://data.imoje.pl';

	/**
	 * @var array[]
	 */
	private static $docUrls = [
		self::LANG_PL => [
			self::REGULATION => 'https://data.imoje.pl/docs/imoje_regulamin_platnosci.pdf',
			self::IODO       => 'https://data.imoje.pl/docs/imoje_informacja_administratora_danych_osobowych.pdf',
		],
		self::LANG_EN => [
			self::REGULATION => 'https://data.imoje.pl/docs/en/imoje_general_terms_and_conditions.pdf',
			self::IODO       => 'https://data.imoje.pl/docs/en/imoje_information_of_the_personal_data_controller.pdf',
		],
	];

	/**
	 * @var array
	 */
	private static $supportedCurrencies = [
		'pln' => 'PLN',
		'eur' => 'EUR',
		'czk' => 'CZK',
		'gbp' => 'GBP',
		'usd' => 'USD',
		'uah' => 'UAH',
		'hrk' => 'HRK',
		'huf' => 'HUF',
		'sek' => 'SEK',
		'ron' => 'RON',
		'chf' => 'CHF',
		'bgn' => 'BGN',
	];

	/**
	 * @var array
	 */
	private static $paymentMethods = [
		'blik'               => 'blik',
		'imoje_paylater'     => 'imoje_paylater',
		'pbl'                => 'pbl',
		'card'               => 'card',
		'ing'                => 'ing',
		'imoje_installments' => 'imoje_installments',
	];

	/**
	 * @var array
	 */
	private static $paymentMethodCodeList = [
		'blik'           => 'blik',
		'alior'          => 'alior',
		'bnpparibas'     => 'bnpparibas',
		'bos'            => 'bos',
		'bs'             => 'bs',
		'bspb'           => 'bspb',
		'bzwbk'          => 'bzwbk',
		'citi'           => 'citi',
		'creditagricole' => 'creditagricole',
		'envelo'         => 'envelo',
		'getin'          => 'getin',
		'ideabank'       => 'ideabank',
		'ing'            => 'ing',
		'inteligo'       => 'inteligo',
		'ipko'           => 'ipko',
		'millennium'     => 'millennium',
		'mtransfer'      => 'mtransfer',
		'nest'           => 'nest',
		'noble'          => 'noble',
		'pbs'            => 'pbs',
		'pekao24'        => 'pekao24',
		'plusbank'       => 'plusbank',
		'pocztowy'       => 'pocztowy',
		'tmobile'        => 'tmobile',
		'imoje_twisto'   => 'imoje_twisto',
		'blik_oneclick'  => 'blik_oneclick',
		'paypo'          => 'paypo',
		'pragma_go'      => 'pragma_go',
		'ecom3ds'        => 'ecom3ds',
		'visa_mobile'    => 'visa_mobile',
		'blik_paylater'  => 'blik_paylater',
		'inbank'         => 'inbank',
		'inbank_0'       => 'inbank_0',
	];

	/**
	 * @var array
	 */
	private static $paymentMethodCodeLogoExt = [
		'mtransfer'      => 'mtransfer.png',
		'bzwbk'          => 'bzwbk.png',
		'pekao24'        => 'pekao24.svg',
		'inteligo'       => 'inteligo.png',
		'ipko'           => 'ipko.png',
		'getin'          => 'getin.svg',
		'noble'          => 'noble.png',
		'creditagricole' => 'creditagricole.svg',
		'alior'          => 'alior.svg',
		'pbs'            => 'pbs.png',
		'millennium'     => 'millennium.svg',
		'citi'           => 'citi.png',
		'bos'            => 'bos.png',
		'bnpparibas'     => 'bnpparibas.png',
		'pocztowy'       => 'pocztowy.svg',
		'plusbank'       => 'plusbank.png',
		'bs'             => 'bs.png',
		'bspb'           => 'bspb.png',
		'nest'           => 'nest.svg',
		'ing'            => 'ing.png',
		'paypo'          => 'paypo.svg',
		'pragma_go'      => 'pragma_go.svg',
		'blik_paylater'  => 'blik_paylater.png',
	];

	/**
	 * @var array
	 */
	private static $hashMethods = [
		'sha224' => 'sha224',
		'sha256' => 'sha256',
		'sha384' => 'sha384',
		'sha512' => 'sha512',
	];

	/**
	 * @var array
	 */
	private static $transactionStatuses = [
		'new'        => 'new',
		'authorized' => 'authorized',
		'pending'    => 'pending',
		'submitted_for_settlement'
		             => 'submitted_for_settlement',
		'rejected'   => 'rejected',
		'settled'    => 'settled',
		'error'      => 'error',
		'cancelled'  => 'cancelled',
	];

	/**
	 * @param string $currencyCode ISO4217
	 *
	 * @return bool
	 */
	public static function canUseForCurrency( $currencyCode ) {
		return isset( self::$supportedCurrencies[ strtolower( $currencyCode ) ] );
	}

	/**
	 * @param string $hashMethod
	 *
	 * @return string
	 */
	public static function getHashMethod( $hashMethod ) {

		if ( isset( self::$hashMethods[ $hashMethod ] ) ) {
			return self::$hashMethods[ $hashMethod ];
		}

		return '';
	}

	/**
	 * @param string $language
	 * @param string $name
	 *
	 * @return string
	 */
	public static function getDocUrl( $language, $name ) {

		if ( isset( self::$docUrls[ $language ][ $name ] ) ) {
			return self::$docUrls[ $language ][ $name ];
		}

		return '';
	}

	/**
	 * @param array  $order
	 * @param string $url
	 * @param string $method
	 * @param string $submitValue
	 * @param string $submitClass
	 * @param string $submitStyle
	 *
	 * @return string
	 */
	public static function createOrderForm(
		$order,
		$url = '',
		$method = '',
		$submitValue = '',
		$submitClass = '',
		$submitStyle = ''
	) {
		if ( ! $submitValue ) {
			$submitValue = 'Kontynuuj';
		}

		if ( ! $url ) {
			$url = '';
		}

		if ( ! $method ) {
			$method = 'POST';
		}

		$form = '<form method="' . $method . '" action="' . $url . '">';

		if ( is_array( $order ) ) {
			foreach ( $order as $key => $value ) {
				$form .= '<input type="hidden" value="' . htmlentities( $value ) . '" name="' . $key . '" id="imoje_' . $key . '">';
			}
		}

		$form .= '<button' . ( $submitClass
				? ' class="' . $submitClass . '"'
				: '' ) . ( $submitStyle
				? ' style="' . $submitStyle . '"'
				: '' ) . ' type="submit" id="submit-payment-form">' . $submitValue . '</button>';
		$form .= '</form>';

		return $form;
	}

	/**
	 * @return array
	 */
	public static function getSupportedCurrencies() {
		return self::$supportedCurrencies;
	}

	/**
	 * @param string $paymentMethod
	 *
	 * @return string
	 */
	public static function getPaymentMethod( $paymentMethod ) {

		if ( isset( self::$paymentMethods[ $paymentMethod ] ) ) {
			return self::$paymentMethods[ $paymentMethod ];
		}

		return '';
	}

	/**
	 * @param string $paymentMethodCode
	 *
	 * @return string
	 */
	public static function getPaymentMethodCodeLogo( $paymentMethodCode ) {

		if ( isset( self::$paymentMethodCodeLogoExt[ $paymentMethodCode ] ) ) {
			return self::$cdnUrl . '/img/pay/' . self::$paymentMethodCodeLogoExt[ $paymentMethodCode ];
		}

		return '';
	}

	/**
	 * @param string $paymentMethodCode
	 *
	 * @return string
	 */
	public static function getPaymentMethodCode( $paymentMethodCode ) {
		if ( isset( self::$paymentMethodCodeList[ $paymentMethodCode ] ) ) {
			return self::$paymentMethodCodeList[ $paymentMethodCode ];
		}

		return '';
	}

	/**
	 * @param string $variable
	 *
	 * @return bool
	 */
	public static function isJson( $variable ) {
		json_decode( $variable );

		return ( json_last_error() === JSON_ERROR_NONE );
	}

	/**
	 * @return array
	 */
	public static function getTransactionStatuses() {
		return self::$transactionStatuses;
	}

	/**
	 * @param string|array $json
	 */
	public static function doResponseJson( $json ) {

		header( 'Content-Type: application/json' );

		echo $json;
		die();
	}

	/**
	 * @param string|int $data
	 *
	 * @return string
	 */
	public static function getCid( $data ) {
		return hash( 'md5', $data );
	}

	/**
	 * @param float $amount
	 *
	 * @return int
	 */
	public static function convertAmountToFractional( $amount ) {
		return (int) self::multiplyValues( round( $amount, 2 ), 100, 0 );
	}

	/**
	 * @param number $firstValue
	 * @param number $secondValue
	 * @param number $precision
	 *
	 * @return float
	 */
	public static function multiplyValues( $firstValue, $secondValue, $precision ) {
		return round( $firstValue * $secondValue, $precision );
	}

	/**
	 * @param int $amount
	 *
	 * @return float
	 */
	public static function convertAmountToMain( $amount ) {
		return round( $amount / 100, 2 );
	}

	/**
	 * @param string $hashMethod
	 * @param string $data
	 * @param string $serviceKey
	 *
	 * @return string
	 */
	public static function hashSignature( $hashMethod, $data, $serviceKey ) {
		return hash( $hashMethod, $data . $serviceKey );
	}

	/**
	 * @param array $transaction
	 *
	 * @return string|void
	 */
	public static function calculateAmountToRefund( $transaction ) {
		$refundAmount = 0;

		if ( isset( $transaction['body']['transaction']['refunds'] ) && $transaction['body']['transaction']['refunds'] ) {

			foreach ( $transaction['body']['transaction']['refunds'] as $refund ) {
				if ( $refund['transaction']['status'] === 'settled' ) {
					$refundAmount += $refund['transaction']['amount'];
				}
			}
		}

		return $transaction['body']['transaction']['amount'] - $refundAmount;
	}
}
