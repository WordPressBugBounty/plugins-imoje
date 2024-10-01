<?php

namespace Imoje\Payment;

use Exception;

/**
 * Class Notification
 *
 * @package Imoje\Payment
 */
class Notification
{

	// region notification codes

	/**
	 * @const int
	 */
	const NC_OK = 0;

	/**
	 * @const int
	 */
	const NC_INVALID_SIGNATURE = 1;

	/**
	 * @const int
	 */
	const NC_SERVICE_ID_NOT_MATCH = 2;

	/**
	 * @const int
	 */
	const NC_ORDER_NOT_FOUND = 3;

	/**
	 * @const int
	 */
	const NC_INVALID_SIGNATURE_HEADERS = 4;

	/**
	 * @const int
	 */
	const NC_EMPTY_NOTIFICATION = 5;

	/**
	 * @const int
	 */
	const NC_NOTIFICATION_IS_NOT_JSON = 6;

	/**
	 * @const int
	 */
	const NC_INVALID_JSON_STRUCTURE = 7;

	/**
	 * @const int
	 */
	const NC_INVALID_ORDER_STATUS = 8;

	/**
	 * @const int
	 */
	const NC_AMOUNT_NOT_MATCH = 9;

	/**
	 * @const int
	 */
	const NC_UNHANDLED_STATUS = 10;

	/**
	 * @const int
	 */
	const NC_ORDER_STATUS_NOT_CHANGED = 11;

	/**
	 * @const int
	 */
	const NC_CART_NOT_FOUND = 12;

	/**
	 * @const int
	 */
	const NC_ORDER_STATUS_IS_NOT_SETTLED_ORDER_ARRANGEMENT_AFTER_IPN = 13;

	/**
	 * @const int
	 */
	const NC_ORDER_EXISTS_ORDER_ARRANGEMENT_AFTER_IPN = 14;

	/**
	 * @const int
	 */
	const NC_REQUEST_IS_NOT_POST = 15;

	/**
	 * @const int
	 */
	const NC_MISSING_ORDER_ID_IN_POST = 16;

	/**
	 * @const int
	 */
	const NC_CURL_IS_NOT_INSTALLED = 17;

	/**
	 * @const int
	 */
	const NC_MISSING_SIGNATURE_IN_POST = 18;

	/**
	 * @const int
	 */
	const NC_COULD_NOT_INSERT_TRANSACTION_ID_TO_DB = 20;

	/**
	 * @const int
	 */
	const NC_IMOJE_REFUND_IS_NOT_SETTLED = 21;

	/**
	 * @const int
	 */
	const NC_ORDER_STATUS_IS_INVALID_FOR_REFUND = 22;

	/**
	 * @const int
	 */
	const NC_TRANSACTION_NOT_FOUND = 23;

	/**
	 * @const int
	 */
	const NC_UNKNOWN = 100;
	// endregion

	// region notification status

	/**
	 * @const string
	 */
	const NS_OK = 'ok';

	/**
	 * @const string
	 */
	const NS_ERROR = 'error';
	// endregion

	// region transaction type

	/**
	 * @const string
	 */
	const TRT_SALE = 'sale';

	/**
	 * @const string
	 */
	const TRT_REFUND = 'refund';
	// endregion

	// region transaction status

	/**
	 * @const string
	 */
	const TRS_SETTLED = 'settled';

	/**
	 * @const string
	 */
	const TRS_REJECTED = 'rejected';

	/**
	 * @const string
	 */
	const TRS_NEW = 'new';
	//endregion

	/**
	 * @const string
	 */
	const HEADER_SIGNATURE_NAME = 'HTTP_X_IMOJE_SIGNATURE';

	/**
	 * @var string
	 */
	private $serviceId = '';

	/**
	 * @var string
	 */
	private $serviceKey = '';

	/**
	 * @var string
	 */
	private $isDebugMode = '';

	/**
	 * @var string|bool
	 */
	private $orderArrangement = '';

	/**
	 * Notification constructor.
	 *
	 * @param string $serviceId
	 * @param string $serviceKey
	 * @param bool   $isDebugMode
	 */
	public function __construct($serviceId, $serviceKey, $isDebugMode, $orderArrangement = false)
	{
		$this->serviceId = $serviceId;
		$this->serviceKey = $serviceKey;
		$this->isDebugMode = $isDebugMode;

		if($orderArrangement) {
			$this->orderArrangement = $orderArrangement;
		}
	}

	/**
	 * @param string          $status
	 * @param string          $code
	 * @param string|int|null $statusBefore
	 * @param string|int|null $statusAfter
	 *
	 * @return string
	 */
	public function formatResponse($status, $code = '', $statusBefore = null, $statusAfter = null)
	{
		$response = [
			'status' => $status,
		];

		if($this->isDebugMode && $code) {
			$response['data'] = [
				'code' => $code,
			];
		}

		if($this->orderArrangement) {
			$response['data']['creatingOrderMode'] = $this->orderArrangement;
		}

		if($statusBefore) {
			$response['data']['statusBefore'] = $statusBefore;
		}

		if($statusAfter) {
			$response['data']['statusAfter'] = $statusAfter;
		}

		// region adds additional data for some cases and set proper header
		switch($status) {
			case self::NS_OK:

				header('HTTP/1.1 200 OK');
				break;
			case self::NS_ERROR:

				header('HTTP/1.1 404 Not Found');
				break;
			default:

				break;
		}
		// endregion

		header('Content-Type: application/json');

		return json_encode($response);
	}

	/**
	 * @param array  $payloadDecoded
	 * @param int    $amount
	 * @param string $currency
	 *
	 * @return bool
	 */
	public static function checkRequestAmount($payloadDecoded, $amount, $currency)
	{
		return $payloadDecoded['transaction']['amount'] === $amount && $payloadDecoded['transaction']['currency'] === $currency;
	}

	/**
	 * Verify notification body and signature
	 *
	 * @return bool|array
	 * @throws Exception
	 */
	public function checkRequest()
	{

		if(!isset($_SERVER['CONTENT_TYPE'], $_SERVER[self::HEADER_SIGNATURE_NAME]) || strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== 0) {

			return self::NC_INVALID_SIGNATURE_HEADERS;
		}

		$payload = file_get_contents('php://input', true);

		if(!$payload) {

			return self::NC_EMPTY_NOTIFICATION;
		}

		if(!Util::isJson($payload)) {

			return self::NC_NOTIFICATION_IS_NOT_JSON;
		}

		$header = $_SERVER[self::HEADER_SIGNATURE_NAME];
		$header = (explode(';', $header));

		$algoFromNotification = explode('=', $header[3]);
		$algoFromNotification = $algoFromNotification[1];

		$headerSignature = explode('=', $header[2]);

		if($headerSignature[1] !== Util::hashSignature($algoFromNotification, $payload, $this->serviceKey)) {

			return self::NC_INVALID_SIGNATURE;
		}

		try {

			Validate::notification($payload);
		} catch(Exception $e) {

			return self::NC_INVALID_JSON_STRUCTURE;
		}

		$payloadDecoded = json_decode($payload, true);

		if($payloadDecoded['transaction']['serviceId'] !== $this->serviceId) {

			return self::NC_SERVICE_ID_NOT_MATCH;
		}

		return $payloadDecoded;
	}
}
