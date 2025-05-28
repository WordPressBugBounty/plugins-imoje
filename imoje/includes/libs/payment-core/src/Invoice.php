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
	 * @var string
	 */
	protected $basisForVatExemption;

	/**
	 * @var array
	 */
	private static $basisExempt = [
		'dental_technican_services'        => 'Usługi techników dentystycznych - art. 43 ust. 1 pkt 14 ustawy o VAT',
		'doctor_dentist_services'          => 'Usługi lekarza i lekarza dentysty - art. 43 ust. 1 pkt 19 pkt a ustawy o VAT',
		'physiotherapy_services'           => 'Usługi fizjoterapeutyczne - art. 43 ust. 1 pkt 19 pkt a ustawy o VAT',
		'nursing_services'                 => 'Usługi pielęgniarskie - art. 43 ust. 1 pkt 19b ustawy o VAT',
		'psychological_services'           => 'Usługi psychologów - art. 43 ust. 1 pkt 19d ustawy o VAT',
		'medical_transport_services'       => 'Usługi transportu sanitarnego - art. 43 ust. 1 pkt 20 ustawy o VAT',
		'care_services'                    => 'Usługi w zakresie opieki nad dziećmi i młodzieżą - art. 43 ust. 1 pkt 24 pkt a i/lub b ustawy o VAT',
		'tutoring'                         => 'Usługi prywatnego nauczania świadczone przez nauczycieli - art. 43 ust. 1 pkt 27 ustawy o VAT',
		'teaching_foreign_languages'       => 'Usługi nauczania języków obcych - art. 43 ust. 1 pkt 28 ustawy o VAT',
		'artists'                          => 'Artyści wynagradzani w formie honorariów - art. 43 ust. 1 pkt 33 pkt b ustawy o VAT',
		'renting_property'                 => 'Najem nieruchomości wyłącznie na cele mieszkaniowe - art. 43 ust. 1 pkt 36 ustawy o VAT',
		'insurance_services'               => 'Usługi ubezpieczeniowe i pośrednictwo w ubezpieczeniach - art. 43 ust. 1 pkt 37 ustawy o VAT',
		'credits_and_loans_services'       => 'Usługi udzielania i pośrednictwo w udzielaniu kredytów lub pożyczek - art. 43 ust. 1 pkt 38 ustawy o VAT',
		'guarantiees'                      => 'Udzielanie poręczeń oraz gwarancji finansowych - art. 43 ust. 1 pkt 39 ustawy o VAT',
		'special_conditions_for_exemption' => 'Szczególne warunki zwolnienia zg. z art. 82 ust. 3',
		'ue_transactions'                  => 'Zwolnienie zg. z dyrektywą 2006/112/WE',
		'subjective_exemptions'            => 'Zwolnienie podmiotowe zg. z art. 113 ust. 1 i 9 ustawy o VAT',
		'other'                            => 'Inna',
		'other_objective_exemptions'       => 'Pozostałe zwolnienia przedmiotowe - art. 43',
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
	 * @param string $basis
	 *
	 * @return string
	 */
	public static function getBasisExempt( $basis ) {

		if ( isset( self::$basisExempt[ $basis ] ) ) {
			return strtoupper( $basis );
		}

		return '';
	}

	/**
	 * @param string $basis
	 *
	 * @return void
	 */
	public function setBasis( $basis) {

		$this->basisForVatExemption = [
			'type' => $basis
		];
	}

	/**
	 * @return array|string
	 */
	public function prepare( $isApi ) {

		$array = [
			'buyer'     => $this->buyer,
			'positions' => $this->positions,
		];

		if ( $this->basisForVatExemption ) {
			$array['basisForVatExemption'] = $this->basisForVatExemption;
		}

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
