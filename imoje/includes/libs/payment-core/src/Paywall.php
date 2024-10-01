<?php

namespace Imoje\Payment;

/**
 * Class Paywall
 *
 * @package Imoje\Payment
 */
class Paywall
{
	const ROUTE_PAY = '/payment';

	/**
	 * @var array
	 */
	public static $serviceUrls = [
		Util::ENVIRONMENT_PRODUCTION => 'https://paywall.imoje.pl',
		Util::ENVIRONMENT_SANDBOX    => 'https://sandbox.paywall.imoje.pl',
	];

	/**
	 * @var string
	 */
	private $serviceKey;

	/**
	 * @var string
	 */
	private $serviceId;

	/**
	 * @var string
	 */
	private $merchantId;

	/**
	 * @var string
	 */
	private $environment;

	/**
	 * Constructor
	 *
	 * @param string $environment
	 * @param string $serviceKey
	 * @param string $serviceId
	 * @param string $merchantId
	 */
	public function __construct($environment, $serviceKey, $serviceId, $merchantId)
	{
		$this->environment = $environment;
		$this->serviceKey = trim($serviceKey);
		$this->serviceId = trim($serviceId);
		$this->merchantId = trim($merchantId);
	}

	/**
	 * @param int    $amount
	 * @param string $currency
	 * @param string $orderId
	 * @param string $customerFirstName
	 * @param string $customerLastName
	 * @param string $orderDescription
	 * @param string $customerEmail
	 * @param string $customerPhone
	 * @param string $urlSuccess
	 * @param string $urlFailure
	 * @param string $urlReturn
	 * @param string $version
	 * @param string $cartData
	 * @param string $visibleMethod
	 * @param string $cart
	 *
	 * @return array
	 */
	public static function prepareData(
		$amount,
		$currency,
		$orderId,
		$customerFirstName,
		$customerLastName,
		$notificationUrl,
		$orderDescription = '',
		$customerEmail = '',
		$customerPhone = '',
		$urlSuccess = '',
		$urlFailure = '',
		$urlReturn = '',
		$version = '',
		$cartData = '',
		$visibleMethod = '',
		$invoice = '',
		$cart = '',
		$preselectMethodCode = ''
	) {

		$data = [
			'amount'            => $amount,
			'currency'          => $currency,
			'orderId'           => $orderId,
			'customerFirstName' => $customerFirstName,
			'customerLastName'  => $customerLastName,
			'urlNotification'   => $notificationUrl,
		];

		if($orderDescription) {
			$data['orderDescription'] = $orderDescription;
		}
		if($customerPhone) {
			$data['customerPhone'] = $customerPhone;
		}
		if($customerEmail) {
			$data['customerEmail'] = $customerEmail;
		}
		if($urlSuccess) {
			$data['urlSuccess'] = $urlSuccess;
		}
		if($urlFailure) {
			$data['urlFailure'] = $urlFailure;
		}
		if($urlReturn) {
			$data['urlReturn'] = $urlReturn;
		}
		if($version) {
			$data['version'] = $version;
		}
		if($cartData) {
			$data['cartData'] = $cartData;
		}
		if($visibleMethod) {
			$data['visibleMethod'] = $visibleMethod;
		}
		if($invoice) {
			$data['invoice'] = $invoice;
		}
		if($cart) {
			$data['cart'] = $cart;
		}
		if($preselectMethodCode) {
			$data['preselectMethodCode'] = $preselectMethodCode;
		}

		return $data;
	}

	/**
	 * Creates full form with order data.
	 *
	 * @param array  $orderData
	 * @param string $submitValue
	 * @param string $submitClass
	 * @param string $submitStyle
	 *
	 * @return string
	 */
	public function buildOrderForm(
		$orderData,
		$submitValue = '',
		$submitClass = '',
		$submitStyle = ''
	) {
		return Util::createOrderForm(
			$this->prepareOrderData($orderData),
			$this->getTransactionCreateUrl($this->environment),
			Util::METHOD_REQUEST_POST,
			$submitValue,
			$submitClass,
			$submitStyle
		);
	}

	/**
	 * @param array  $order
	 * @param string $hashMethod
	 *
	 * @return array
	 */
	private function prepareOrderData($order, $hashMethod = 'sha256')
	{

		$order['serviceId'] = $this->serviceId;
		$order['merchantId'] = $this->merchantId;
		$order['signature'] = self::createSignature($order, $this->serviceKey, $hashMethod);

		return $order;
	}

	/**
	 * @param array  $orderData
	 * @param string $serviceKey
	 * @param string $hashMethod
	 *
	 * @return string|bool
	 */
	public static function createSignature($orderData, $serviceKey, $hashMethod = 'sha256')
	{

		$isHashMethodSupported = Util::getHashMethod($hashMethod);

		if(!$isHashMethodSupported
			|| !is_array($orderData)) {
			return false;
		}

		ksort($orderData);

		$data = [];
		foreach($orderData as $key => $value) {
			$data[] = $key . '=' . $value;
		}

		return Util::hashSignature($hashMethod, implode('&', $data), $serviceKey) . ';' . $hashMethod;
	}

	/**
	 * @param string $environment
	 *
	 * @return string
	 */
	private function getTransactionCreateUrl($environment)
	{

		$baseUrl = self::getServiceUrl($environment);

		if($baseUrl) {
			return $baseUrl . self::ROUTE_PAY;
		}

		return '';
	}

	/**
	 * @param string $environment
	 *
	 * @return string
	 */
	public static function getServiceUrl($environment)
	{

		if(isset(self::$serviceUrls[$environment])) {
			return self::$serviceUrls[$environment];
		}

		return '';
	}
}
