<?php

namespace Imoje\Payment;

class Installments
{

	const WIDGET = 'installments.js';

	/**
	 * @var array
	 */
	private static $serviceUrls = [
		Util::ENVIRONMENT_PRODUCTION => 'https://paywall.imoje.pl/js',
		Util::ENVIRONMENT_SANDBOX    => 'https://sandbox.paywall.imoje.pl/js',
	];

	/**
	 * @var string
	 */
	protected $merchantId;

	/**
	 * @var string
	 */
	protected $serviceId;

	/**
	 * @var string
	 */
	protected $serviceKey;

	/**
	 * @var string
	 */
	protected $environment;

	/**
	 * @param string $merchantId
	 * @param string $serviceId
	 * @param string $serviceKey
	 * @param string $environment
	 */
	public function __construct($merchantId, $serviceId, $serviceKey, $environment = '')
	{
		$this->merchantId = $merchantId;
		$this->serviceId = $serviceId;
		$this->serviceKey = $serviceKey;

		$this->environment = $environment
			?: Util::ENVIRONMENT_PRODUCTION;
	}

	/**
	 * @return string
	 */
	public function getScriptUrl()
	{

		$serviceUrl = $this->getServiceUrl();

		if($serviceUrl) {
			return $serviceUrl
				. '/'
				. self::WIDGET;
		}

		return '';
	}

	/**
	 * @return string
	 */
	private function getServiceUrl()
	{

		if(isset(self::$serviceUrls[$this->environment])) {
			return self::$serviceUrls[$this->environment];
		}

		return '';
	}

	/**
	 * @param number $amount
	 * @param string $currency
	 *
	 * @return array
	 */
	public function getData($amount, $currency)
	{

		$data = [
			'amount'     => Util::convertAmountToFractional($amount),
			'currency'   => strtoupper($currency),
			'serviceId'  => $this->serviceId,
			'merchantId' => $this->merchantId,
		];

		$data['signature'] = Paywall::createSignature(
			$data,
			$this->serviceKey);

		return $data;
	}
}
