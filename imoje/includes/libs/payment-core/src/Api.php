<?php

namespace Imoje\Payment;

use Exception;

/**
 * Class Api
 *
 * @package Imoje\Payment
 */
class Api {

	const TRANSACTION = 'transaction';
	const PAYMENT = 'payment';
	const PROFILE = 'profile';
	const MERCHANT = 'merchant';
	const SERVICE = 'service';
	const TRANSACTION_TYPE_SALE = 'sale';
	const TRANSACTION_TYPE_REFUND = 'refund';

	/**
	 * @var array
	 */
	private static $serviceUrls = [
		Util::ENVIRONMENT_PRODUCTION => 'https://api.imoje.pl/v1',
		Util::ENVIRONMENT_SANDBOX    => 'https://sandbox.api.imoje.pl/v1',
	];

	/**
	 * @var string
	 */
	private $authorizationToken;

	/**
	 * @var string
	 */
	private $merchantId;

	/**
	 * @var string
	 */
	private $serviceId;

	/**
	 * @var string
	 */
	private $environment;

	/**
	 * Constructor
	 *
	 * @param string $authorizationToken
	 * @param string $merchantId
	 * @param string $serviceId
	 * @param string $environment
	 */
	public function __construct(
		$authorizationToken,
		$merchantId,
		$serviceId,
		$environment = ''
	) {

		$this->authorizationToken = $authorizationToken;
		$this->merchantId         = $merchantId;
		$this->serviceId          = $serviceId;

		$this->environment = $environment
			?: Util::ENVIRONMENT_PRODUCTION;
	}

	/**
	 * @param array $transaction
	 *
	 * @return bool
	 */
	public static function verifyPaymentLink( $transaction ) {

		return isset( $transaction['success'] )
		       && isset( $transaction['body']['payment']['url'] )
		       && $transaction['success']
		       && $transaction['body']['payment']
		       && $transaction['body']['payment']['url'];
	}

	/**
	 * @param array $transaction
	 *
	 * @return bool
	 */
	public static function verifyTransaction( $transaction ) {

		return isset( $transaction['success'] )
		       && isset( $transaction['body']['action']['url'] )
		       && $transaction['success']
		       && $transaction['body']['action']
		       && $transaction['body']['action']['url'];
	}

	/**
	 * @param string $firstName
	 * @param string $lastName
	 * @param string $street
	 * @param string $city
	 * @param string $region
	 * @param string $postalCode
	 * @param string $countryCodeAlpha2
	 *
	 * @return array
	 */
	public static function prepareAddressData(
		$firstName,
		$lastName,
		$street,
		$city,
		$region,
		$postalCode,
		$countryCodeAlpha2
	) {

		$array = [
			'firstName'         => $firstName,
			'lastName'          => $lastName,
			'street'            => $street,
			'city'              => $city,
			'postalCode'        => $postalCode,
			'countryCodeAlpha2' => $countryCodeAlpha2,
		];

		if ( $region ) {
			$array['region'] = $region;
		}

		return $array;
	}

	/**
	 * @param string $blikProfileId
	 * @param int    $amount
	 * @param string $currency
	 * @param string $orderId
	 * @param string $title
	 * @param string $clientIp
	 * @param string $blikKey
	 * @param string $blikCode
	 *
	 * @return string
	 */
	public function prepareBlikOneclickData(
		$blikProfileId,
		$amount,
		$currency,
		$orderId,
		$title,
		$clientIp,
		$blikKey = '',
		$blikCode = ''
	) {

		$array = [
			'serviceId'     => $this->serviceId,
			'blikProfileId' => $blikProfileId,
			'amount'        => Util::convertAmountToFractional( $amount ),
			'currency'      => $currency,
			'orderId'       => $orderId,
			'title'         => $title,
			'clientIp'      => $clientIp,
		];

		if ( $blikKey ) {
			$array['blikKey'] = $blikKey;
		}

		if ( $blikCode ) {
			$array['blikCode'] = $blikCode;
		}

		return json_encode( $array );
	}

	/**
	 * @param string $id
	 *
	 * @return array
	 */
	public function getTransaction( $id ) {

		return $this->call(
			$this->getTransactionUrl( $id ),
			Util::METHOD_REQUEST_GET
		);
	}

	/**
	 * @param string $url
	 * @param string $methodRequest
	 * @param string $body
	 *
	 * @return array
	 */
	private function call( $url, $methodRequest, $body = '' ) {

		$curl = curl_init( $url );
		curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, $methodRequest );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $body );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 10 );
		curl_setopt( $curl, CURLOPT_TIMEOUT, 10 );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Authorization: Bearer ' . $this->authorizationToken,
		] );

		$resultCurl = json_decode( curl_exec( $curl ), true );

		$httpCode = curl_getinfo( $curl, CURLINFO_HTTP_CODE );

		if ( ( $httpCode !== 200 ) || ! $resultCurl ) {

			$array = [
				'success' => false,
				'data'    => [
					'httpCode' => $httpCode,
					'error'    => curl_error( $curl ),
					'body'     => '',
					'errors'   => [],
				],
			];

			if ( isset( $resultCurl['apiErrorResponse']['message'] ) && $resultCurl['apiErrorResponse']['message'] ) {
				$array['data']['body'] = $resultCurl['apiErrorResponse']['message'];
			}

			if ( isset( $resultCurl['apiErrorResponse']['errors'] ) && $resultCurl['apiErrorResponse']['errors'] ) {
				$array['data']['errors'] = $resultCurl['apiErrorResponse']['errors'];
			}

			return $array;
		}

		if ( isset( $resultCurl['transaction']['statusCode'] ) && $resultCurl['transaction']['statusCode'] ) {

			if ( $this->check_retype_code( $resultCurl['transaction']['statusCode'] ) ) {
				$resultCurl['code'] = 1;
			}

			if ( $this->check_new_param_alias( $resultCurl['transaction']['statusCode'] ) ) {
				$resultCurl['newParamAlias'] = 1;
			}
		}

		return [
			'success' => true,
			'body'    => $resultCurl,
		];
	}

	/**
	 * Verify that error code needs to retype BLIK code in frontend
	 *
	 * @param string $code
	 *
	 * @return bool
	 */
	private function check_retype_code( $code ) {
		$array = [
			'BLK-ERROR-210002',
			'BLK-ERROR-210003',
			'BLK-ERROR-210004',
			'BLK-ERROR-210005',
			'BLK-ERROR-210006',
			'BLK-ERROR-210007',
			'BLK-ERROR-210008',
			'BLK-ERROR-210009',
			'BLK-ERROR-210010',
			'BLK-ERROR-210011',
			'BLK-ERROR-210012',
			'BLK-ERROR-210013',
			'BLK-ERROR-210014',
			'BLK-ERROR-210015',
			'BLK-ERROR-210016',
			'BLK-ERROR-210017',
			'BLK-ERROR-210018',
			'BLK-ERROR-210019',
			'BLK-ERROR-210020',
			'BLK-ERROR-210021',
			'BLK-ERROR-210022',

		];

		return in_array( $code, $array );
	}

	/**
	 * Verify that error code needs to create new paramAlias
	 *
	 * @param string $code
	 *
	 * @return bool
	 */
	private function check_new_param_alias( $code ) {

		$array = [
			'BLK-ERROR-210002',
			'BLK-ERROR-210003',
			'BLK-ERROR-210004',
		];

		return in_array( $code, $array );
	}
	// endregion

	/**
	 * @return string
	 */
	private function getTransactionUrl( $id ) {

		$baseUrl = self::getServiceUrl();

		if ( $baseUrl ) {
			return $baseUrl
			       . '/'
			       . self::MERCHANT
			       . '/'
			       . $this->merchantId
			       . '/'
			       . self::TRANSACTION
			       . '/'
			       . $id;
		}

		return '';
	}

	/**
	 * @return string
	 */
	private function getServiceUrl() {

		if ( isset( self::$serviceUrls[ $this->environment ] ) ) {
			return self::$serviceUrls[ $this->environment ];
		}

		return '';
	}

	/**
	 * @param int    $amount
	 * @param string $currency
	 * @param string $orderId
	 * @param string $paymentMethod
	 * @param string $paymentMethodCode
	 * @param string $successReturnUrl
	 * @param string $failureReturnUrl
	 * @param string $customerFirstName
	 * @param string $customerLastName
	 * @param string $customerEmail
	 * @param string $type
	 * @param string $clientIp
	 * @param string $blikCode
	 * @param array  $address
	 * @param string $cid
	 *
	 * @return string
	 */
	public function prepareData(
		$amount,
		$currency,
		$orderId,
		$paymentMethod,
		$paymentMethodCode,
		$successReturnUrl,
		$failureReturnUrl,
		$customerFirstName,
		$customerLastName,
		$customerEmail,
		$notificationUrl,
		$type = 'sale',
		$clientIp = '',
		$blikCode = '',
		$address = [],
		$cid = '',
		$invoice = [],
		$installmentsPeriod = 0
	) {

		if ( ! $clientIp ) {
			$clientIp = $_SERVER['REMOTE_ADDR'];
		}

		$array = [
			'type'              => $type,
			'serviceId'         => $this->serviceId,
			'amount'            => Util::convertAmountToFractional( $amount ),
			'currency'          => $currency,
			'orderId'           => (string) $orderId,
			'title'             => (string) $orderId,
			'paymentMethod'     => $paymentMethod,
			'paymentMethodCode' => $paymentMethodCode,
			'successReturnUrl'  => $successReturnUrl,
			'failureReturnUrl'  => $failureReturnUrl,
			'clientIp'          => $clientIp,
			'customer'          => [
				'firstName' => $customerFirstName,
				'lastName'  => $customerLastName,
				'email'     => $customerEmail,
			],
			'notificationUrl'   => $notificationUrl,
		];

		if ( $blikCode ) {
			$array['blikCode'] = $blikCode;
		}

		if ( isset( $address['billing'] ) && $address['billing'] ) {
			$array['billing'] = $address['billing'];
		}

		if ( isset( $address['shipping'] ) && $address['shipping'] ) {
			$array['shipping'] = $address['shipping'];
		}

		if ( $cid ) {
			$array['customer']['cid'] = (string) $cid;
		}

		if ( $invoice ) {
			$array['invoice'] = $invoice;
		}

		if ( $installmentsPeriod ) {
			$array['installment']['period'] = (int) $installmentsPeriod;
		}

		return json_encode( $array );
	}

	/**
	 * @param int    $amount
	 * @param string $currency
	 * @param string $orderId
	 * @param string $returnUrl
	 * @param string $successReturnUrl
	 * @param string $failureReturnUrl
	 * @param string $customerFirstName
	 * @param string $customerLastName
	 * @param string $customerEmail
	 * @param string $phone
	 * @param string $visibleMethod
	 * @param array  $cart
	 * @param array  $invoice
	 *
	 * @return string
	 */
	public function prepareDataPaymentLink(
		$amount,
		$currency,
		$orderId,
		$returnUrl,
		$successReturnUrl,
		$failureReturnUrl,
		$customerFirstName,
		$customerLastName,
		$customerEmail,
		$notificationUrl,
		$phone = '',
		$visibleMethod = [],
		$cart = [],
		$invoice = [],
		$preselectMethodCode = ''
	) {

		$array = [
			'serviceId'        => $this->serviceId,
			'amount'           => Util::convertAmountToFractional( $amount ),
			'currency'         => $currency,
			'orderId'          => (string) $orderId,
			'title'            => (string) $orderId,
			'returnUrl'        => $returnUrl,
			'successReturnUrl' => $successReturnUrl,
			'failureReturnUrl' => $failureReturnUrl,
			'customer'         => [
				'firstName' => $customerFirstName,
				'lastName'  => $customerLastName,
				'email'     => $customerEmail,
				'phone'     => $phone,
			],
			'notificationUrl'  => $notificationUrl,
		];

		if ( $phone ) {
			$array['customer']['phone'] = $phone;
		}

		if ( $visibleMethod ) {
			$array['visibleMethod'] = $visibleMethod;
		}

		if ( $cart ) {
			$array['cart'] = $cart;
		}

		if ( $invoice ) {
			$array['invoice'] = $invoice;
		}

		if ( $preselectMethodCode ) {
			$array['preselectMethodCode'] = $preselectMethodCode;
		}

		return json_encode( $array );
	}

	/**
	 * @param int $amount
	 *
	 * @return string
	 */
	public function prepareRefundData(
		$amount
	) {

		return json_encode( [
			'amount'    => $amount,
			'serviceId' => $this->serviceId,
			'type'      => self::TRANSACTION_TYPE_REFUND,
		] );
	}

	/**
	 * @param string $body
	 *
	 * @return array
	 */
	public function createTransaction( $body, $url = '', $method = '' ) {
		if ( ! $url ) {
			$url = $this->getTransactionCreateUrl();
		}
		if ( ! $method ) {
			$method = Util::METHOD_REQUEST_POST;
		}

		return $this->call(
			$url,
			$method,
			$body
		);
	}

	/**
	 * @param string $body
	 *
	 * @return array
	 */
	public function createPaymentLink( $body, $url = '', $method = '' ) {
		if ( ! $url ) {
			$url = $this->getPaymentLinkCreateUrl();
		}
		if ( ! $method ) {
			$method = Util::METHOD_REQUEST_POST;
		}

		return $this->call(
			$url,
			$method,
			$body
		);
	}

	/**
	 * @return string
	 */
	private function getTransactionCreateUrl() {

		$baseUrl = self::getServiceUrl();

		if ( $baseUrl ) {
			return $baseUrl
			       . '/'
			       . self::MERCHANT
			       . '/'
			       . $this->merchantId
			       . '/'
			       . self::TRANSACTION;
		}

		return '';
	}

	/**
	 * @return string
	 */
	private function getPaymentLinkCreateUrl() {

		$baseUrl = self::getServiceUrl();

		if ( $baseUrl ) {
			return $baseUrl
			       . '/'
			       . self::MERCHANT
			       . '/'
			       . $this->merchantId
			       . '/'
			       . self::PAYMENT;
		}

		return '';
	}

	/**
	 * @return array
	 */
	public function getServiceInfo() {
		return $this->call(
			$this->getServiceInfoUrl(),
			Util::METHOD_REQUEST_GET
		);
	}

	/**
	 * @return string
	 */
	private function getServiceInfoUrl() {

		$baseUrl = self::getServiceUrl();

		if ( $baseUrl ) {
			return $baseUrl
			       . '/'
			       . self::MERCHANT
			       . '/'
			       . $this->merchantId
			       . '/'
			       . self::SERVICE
			       . '/'
			       . $this->serviceId;
		}

		return '';
	}

	/**
	 * @param string $cid
	 *
	 * @return array
	 */
	public function getBlikProfileList( $cid ) {

		return $this->call(
			$this->getProfileBlikUrl( $cid ),
			Util::METHOD_REQUEST_GET
		);
	}

	/**
	 * @param string $cid
	 *
	 * @return string
	 */
	private function getProfileBlikUrl( $cid ) {

		$baseUrl = $this->getServiceUrl();

		if ( $baseUrl ) {
			return $baseUrl
			       . '/'
			       . self::MERCHANT
			       . '/'
			       . $this->merchantId
			       . '/'
			       . self::PROFILE
			       . '/'
			       . self::SERVICE
			       . '/'
			       . $this->serviceId
			       . '/cid/'
			       . $cid
			       . '/blik';
		}

		return '';
	}

	/**
	 * @param string $body
	 *
	 * @return array
	 */
	public function createRefund( $body, $transactionUuid ) {

		return $this->call(
			$this->getRefundCreateUrl( $transactionUuid ),
			Util::METHOD_REQUEST_POST,
			$body
		);
	}

	/**
	 * @param string $transactionUuid
	 *
	 * @return string
	 */
	private function getRefundCreateUrl( $transactionUuid ) {

		$baseUrl = $this->getServiceUrl();

		if ( $baseUrl ) {
			return $baseUrl
			       . '/'
			       . self::MERCHANT
			       . '/'
			       . $this->merchantId
			       . '/'
			       . self::TRANSACTION
			       . '/'
			       . $transactionUuid
			       . '/refund';
		}

		return '';
	}

	/**
	 * Creates full form with order data.
	 *
	 * @param array  $transaction
	 * @param string $submitValue
	 * @param string $submitClass
	 * @param string $submitStyle
	 *
	 * @return string
	 * @throws Exception
	 */
	public function buildOrderForm( $transaction, $submitValue = '', $submitClass = '', $submitStyle = '' ) {

		if ( ! isset( $transaction['body']['action'] ) ) {

			return false;
		}

		return Util::createOrderForm(
			self::parseStringToArray( $transaction['body'] ),
			$transaction['body']['action']['url'],
			$transaction['body']['action']['method'],
			$submitValue,
			$submitClass,
			$submitStyle
		);
	}

	/**
	 * @param array $transaction
	 *
	 * @return array
	 */
	public static function parseStringToArray( $transaction ) {

		$array = [];

		if ( $transaction['action']['method'] === Util::METHOD_REQUEST_POST ) {
			parse_str( $transaction['action']['contentBodyRaw'], $array );
		}

		if ( $transaction['action']['method'] === Util::METHOD_REQUEST_GET ) {
			$urlParsed = parse_url( $transaction['action']['url'] );

			if ( isset( $urlParsed['query'] ) && $urlParsed['query'] ) {
				parse_str( $urlParsed['query'], $array );

				return $array;
			}

			if ( isset( $transaction['action']['contentBodyRaw'] ) && $transaction['action']['contentBodyRaw'] ) {
				parse_str( $transaction['action']['contentBodyRaw'], $array );
			}
		}

		return $array;
	}

	/**
	 * @return string
	 */
	public function getDebitBlikProfileUrl() {

		$baseUrl = self::getServiceUrl();

		if ( $baseUrl ) {
			return $baseUrl
			       . '/'
			       . self::MERCHANT
			       . '/'
			       . $this->merchantId
			       . '/'
			       . self::TRANSACTION
			       . '/'
			       . self::PROFILE
			       . '/blik';
		}

		return '';
	}

	/**
	 * @return string
	 */
	public function getDeactivateBlikProfileUrl() {

		$baseUrl = self::getServiceUrl();

		if ( $baseUrl ) {
			return $baseUrl
			       . '/'
			       . self::MERCHANT
			       . '/'
			       . $this->merchantId
			       . '/'
			       . self::PROFILE
			       . '/deactivate/blik';
		}

		return '';
	}

	/**
	 * @param array  $paymentMethods
	 * @param string $paymentMethod
	 *
	 * @return array
	 */
	public function getPaymentChannelInServiceAndVerify( $paymentMethods, $paymentMethod, $paymentMethodCode ) {

		if ( ! is_array( $paymentMethods ) ) {
			return [];
		}

		foreach ( $paymentMethods as $pm ) {
			if ( $pm['paymentMethod'] === $paymentMethod
			     && $pm['paymentMethodCode'] === $paymentMethodCode
			     && $pm['isActive']
			     && $pm['isOnline']
			     && ( isset( $pm['transactionLimits'] ) && $pm['transactionLimits'] ) ) {

				return $pm;
			}
		}

		return [];
	}

	/**
	 * @param array  $imojeService
	 * @param number $total
	 *
	 * @return bool
	 */
	public function getPaymentMethodAvailable( $imojeService, $pm, $total ) {

		if ( empty( $imojeService['paymentMethods'] ) ) {
			return false;
		}

		foreach ( $imojeService['paymentMethods'] as $payment_method ) {
			if ( $payment_method['paymentMethod'] === $pm
			     && $payment_method['isActive']
			     && $payment_method['isOnline']
			     && ( isset( $payment_method['transactionLimits'] ) && $payment_method['transactionLimits'] )
			     && $this->verifyTransactionLimits( $payment_method['transactionLimits'], $total ) ) {

				return true;
			}
		}

		return false;
	}

	/**
	 * @param array  $imojeService
	 * @param string $pm
	 * @param string $pmc
	 * @param number $total
	 *
	 * @return bool
	 */
	public function getPaymentMethodChannelAvailable( $imojeService, $pm, $pmc, $total ) {

		if ( empty( $imojeService['paymentMethods'] ) ) {
			return false;
		}

		foreach ( $imojeService['paymentMethods'] as $payment_method ) {

			if ( $payment_method['paymentMethod'] === $pm
			     && $payment_method['paymentMethodCode'] === $pmc
			     && $payment_method['isActive']
			     && $payment_method['isOnline']
			     && ( isset( $payment_method['transactionLimits'] ) && $payment_method['transactionLimits'] )
			     && $this->verifyTransactionLimits( $payment_method['transactionLimits'], $total ) ) {

				return true;
			}
		}

		return false;
	}

	/**
	 * @param array  $transactionLimits
	 * @param number $total
	 *
	 * @return bool
	 */
	public function verifyTransactionLimits( $transactionLimits, $total ) {

		$isMaxValue = isset( $transactionLimits['maxTransaction']['value'] ) && $transactionLimits['maxTransaction']['value'];
		$isMinValue = isset( $transactionLimits['minTransaction']['value'] ) && $transactionLimits['minTransaction']['value'];

		if ( $isMaxValue || $isMinValue ) {
			$total = Util::convertAmountToFractional( $total );

			// leave this condition - in 99% of cases, it is going to be true, so there's no need to check another scenario
			if ( $isMaxValue && $isMinValue ) {
				return $total >= $transactionLimits['minTransaction']['value'] && $total <= $transactionLimits['maxTransaction']['value'];
			}

			if ( $isMaxValue ) {
				return $total <= $transactionLimits['maxTransaction']['value'];
			}

			if ( $isMinValue ) {
				return $total >= $transactionLimits['minTransaction']['value'];
			}
		}

		return false;
	}
}
