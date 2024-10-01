<?php

namespace Imoje\Payment;

/**
 * Class CartData
 *
 * @package Imoje\Payment
 */
class Invoice {

	/**
	 * @const string
	 */
	const TAX_23 = 'TAX_23';

	/**
	 * @const string
	 */
	const TAX_22 = 'TAX_22';

	/**
	 * @const string
	 */
	const TAX_8 = 'TAX_8';

	/**
	 * @const string
	 */
	const TAX_7 = 'TAX_7';

	/**
	 * @const string
	 */
	const TAX_5 = 'TAX_5';

	/**
	 * @const string
	 */
	const TAX_3 = 'TAX_3';

	/**
	 * @const string
	 */
	const TAX_0 = 'TAX_0';

	/**
	 * @const string
	 */
	const TAX_EXEMPT = 'TAX_EXEMPT';

	/**
	 * @const string
	 */
	const TAX_NOT_LIABLE = 'TAX_NOT_LIABLE';

	/**
	 * @const string
	 */
	const TAX_REVERSE_CHARGE = 'TAX_REVERSE_CHARGE';

	/**
	 * @const string
	 */
	const TAX_NOT_EXLUCDING = 'TAX_NOT_EXCLUDING';

	/**
	 * @const string
	 */
	const SHOP_TAX_EXEMPT = 'zw';

	/**
	 * @const string
	 */
	const BUYER_PERSON = 'PERSON';
	/**
	 * @const string
	 */
	const BUYER_COMPANY = 'COMPANY';

	/**
	 * @const string
	 */
	const ID_TYPE_VAT = 'VAT_ID';

	/**
	 * @const string
	 */
	const VAT_COUNTRY_ALPHA2 = 'PL';

	/**
	 * @var array
	 */
	protected $buyer;

	/**
	 * @var array
	 */
	protected $positions;

	/**
	 * @var string[]
	 */
	private $supportCurrencies = [
		'PLN' => 'PLN',
	];

	/**
	 * @param string $currency
	 *
	 * @return bool
	 */
	public function validateCurrency( $currency ) {
		return isset( $this->supportCurrencies[ $currency ] );
	}

	/**
	 * @param string $name
	 * @param int    $code
	 * @param int    $quantity
	 * @param string $taxStake
	 * @param string $grossAmount
	 * @param int    $discountAmount
	 *
	 * @return void
	 */
	public function addItem( $name, $code, $quantity, $taxStake, $grossAmount, $discountAmount = 0 ) {

		$position = [
			'name'        => $name,
			'code'        => (string) $code,
			'quantity'    => (float) $quantity,
			'unit'        => 'Sztuki',
			'taxStake'    => $taxStake,
			'grossAmount' => (int) $grossAmount,
		];

		if ( $discountAmount ) {
			$position['discountAmount'] = (int) $discountAmount;
		}

		$this->positions[] = $position;
	}

	/**
	 * @param string $type
	 * @param string $email
	 * @param string $fullName
	 * @param string $street
	 * @param string $city
	 * @param string $postalCode
	 * @param string $countryCodeAlpha2
	 * @param string $idCountryCodeAlpha2
	 * @param string $idType
	 * @param string $idNumber
	 *
	 * @return Invoice
	 */
	public function setBuyer( $type, $email, $fullName, $street, $city, $postalCode, $countryCodeAlpha2, $idCountryCodeAlpha2 = '', $idType = '', $idNumber = '' ) {

		$array = [
			'type'              => $type,
			'email'             => $email,
			'fullName'          => $fullName,
			'street'            => $street,
			'city'              => $city,
			'postalCode'        => $postalCode,
			'countryCodeAlpha2' => $countryCodeAlpha2,
		];

		if ( $idCountryCodeAlpha2 ) {
			$array['idCountryCodeAlpha2'] = $idCountryCodeAlpha2;
		}

		if ( $idType ) {
			$array['idType'] = $idType;
		}

		if ( $idNumber ) {
			$array['idNumber'] = $idNumber;
		}

		$this->buyer = $array;

		return $this;
	}

	/**
	 * @param string $idCountryCodeAlpha2
	 * @param string $idNumber
	 *
	 * @return void
	 */
	public function setCompanyBuyer( $idCountryCodeAlpha2, $idNumber, $fullName = '' ) {
		$this->buyer['type']                = self::BUYER_COMPANY;
		$this->buyer['idCountryCodeAlpha2'] = $idCountryCodeAlpha2;
		$this->buyer['idType']              = self::ID_TYPE_VAT;
		$this->buyer['idNumber']            = $idNumber;
		if ( $fullName ) {
			$this->buyer['fullName'] = $fullName;
		}
	}

	/**
	 * @return array|string
	 */
	public function prepare( $isApi ) {

		$array = [
			'buyer'     => $this->buyer,
			'positions' => $this->positions,
		];

		return $isApi
			? $array
			: base64_encode( gzencode( json_encode( $array ), 5 ) );
	}

	/**
	 * @param CartData $cart
	 * @param string   $email
	 * @param bool     $isApi
	 *
	 * @return array|string
	 */
	public static function get( $cart, $email, $isApi = true ) {

		if ( ! ( $cart instanceof CartData ) ) {
			return [];
		}

		$cart = $cart->prepareCartDataArray();

		$invoice = new Invoice();

		if ( empty( $cart['items'] ) ) {
			return [];
		}

		foreach ( $cart['items'] as $item ) {

			$tax = null;
			try {
				$tax = constant( 'self::TAX_' . $item['vat'] );
			} catch( \Error $e ) {
			}

			if ( ! $tax ) {
				return [];
			}

			$invoice->addItem( $item['name'],
				$item['id'],
				$item['quantity'],
				constant( '\Imoje\Payment\Invoice::TAX_' . $item['vat'] ),
				$item['amount']
			);
		}

		$invoice->setBuyer( Invoice::BUYER_PERSON,
			$email,
			$cart['address']['billing']['name'],
			$cart['address']['billing']['street'],
			$cart['address']['billing']['city'],
			$cart['address']['billing']['postalCode'],
			$cart['address']['billing']['country'] );

		return $invoice->prepare( $isApi );
	}
}
