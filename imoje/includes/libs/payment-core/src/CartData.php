<?php

namespace Imoje\Payment;

/**
 * Class CartData
 *
 * @package Imoje\Payment
 */
class CartData
{

	const TYPE_SCHEMA_ITEMS = 'cartDataItems';
	const TYPE_SCHEMA_ADDRESS = 'cartDataAddress';
	const TYPE_SCHEMA_DISCOUNT = 'cartDataDiscount';
	const TYPE_SCHEMA_SHIPPING = 'cartDataShipping';
	const MAX_PREVIOUS_ORDERS = 3;

	/**
	 * @var array
	 */
	private $items;

	/**
	 * @var array
	 */
	private $addressBilling;

	/**
	 * @var int
	 */
	private $createdAt;

	/**
	 * @var int
	 */
	private $amount;

	/**
	 * @var array
	 */
	private $addressDelivery;

	/**
	 * @var array
	 */
	private $shipping = [];

	/**
	 * @var array
	 */
	private $discount = [];

	/**
	 * @var CartData[]
	 */
	private $previous = [];

	/**
	 * @return array
	 */
	public function getItems()
	{
		return $this->items;
	}

	/**
	 * @param mixed $id
	 * @param mixed $vat
	 * @param mixed $name
	 * @param mixed $amount
	 * @param mixed $quantity
	 * @param mixed $isUnitPrice
	 *
	 * @return void
	 */
	public function addItem(
		$id,
		$vat,
		$name,
		$amount,
		$quantity,
		$isUnitPrice
	) {

		if($isUnitPrice) {

			$this->items[] = [
				'id'       => $id,
				'vat'      => $vat,
				'name'     => $name,
				'amount'   => $amount,
				'quantity' => (int) $quantity,
			];

			return;
		}

		$amountCalc = floor($amount / $quantity);

		if((float) $amount !== ($amountCalc * $quantity)) {

			$quantityCalc = $amount % $quantity;

			$quantity = $quantity - $quantityCalc;

			$this->items[] = [
				'id'       => $id,
				'vat'      => $vat,
				'name'     => $name,
				'amount'   => $amountCalc + 1,
				'quantity' => $quantityCalc,
			];
		}

		$this->items[] = [
			'id'       => $id,
			'vat'      => $vat,
			'name'     => $name,
			'amount'   => $amountCalc,
			'quantity' => (int) $quantity,
		];
	}

	/**
	 * @param int    $vat
	 * @param string $name
	 * @param int    $amount
	 *
	 * @return void
	 */
	public function setDiscount($vat, $name, $amount)
	{

		$this->discount = [
			'vat'    => $vat,
			'name'   => $name,
			'amount' => $amount,
		];
	}

	/**
	 * @param CartData $previousOrder
	 *
	 * @return void
	 */
	public function addPrevious($previousOrder)
	{

		if(count($this->previous) === self::MAX_PREVIOUS_ORDERS) {
			return;
		}

		$this->previous[] = $previousOrder;
	}

	/**
	 * @param int    $vat
	 * @param string $name
	 * @param int    $amount
	 *
	 * @return void
	 */
	public function setShipping($vat, $name, $amount)
	{

		$this->shipping = [
			'vat'    => $vat,
			'name'   => $name,
			'amount' => $amount,
		];
	}

	/**
	 * @param int $amount
	 *
	 * @return void
	 */
	public function setAmount($amount)
	{

		$this->amount = $amount;
	}

	/**
	 * @param int $createdAt
	 *
	 * @return void
	 */
	public function setCreatedAt($createdAt)
	{

		$this->createdAt = $createdAt;
	}

	/**
	 * @param string $city
	 * @param string $name
	 * @param string $phone
	 * @param string $street
	 * @param string $country
	 * @param string $postalCode
	 * @param string $vatNumber
	 *
	 * @return void
	 */
	public function setAddressBilling($city, $name, $phone, $street, $country, $postalCode, $vatNumber = '')
	{

		$this->addressBilling = $this->commonPrepareAddress(
			$city,
			$name,
			$phone,
			$street,
			$country,
			$postalCode,
			$vatNumber
		);
	}

	/**
	 * @param string $city
	 * @param string $name
	 * @param string $phone
	 * @param string $street
	 * @param string $country
	 * @param string $postalCode
	 * @param string $vatNumber
	 *
	 * @return array
	 */
	private function commonPrepareAddress($city, $name, $phone, $street, $country, $postalCode, $vatNumber = '')
	{
		$array = [
			'city'       => $city,
			'name'       => $name,
			'phone'      => (string) $phone,
			'street'     => $street,
			'country'    => $country,
			'postalCode' => (string) $postalCode,
		];

		if($vatNumber) {
			$array['vatNumber'] = $vatNumber;
		}

		return $array;
	}

	/**
	 * @param string $city
	 * @param string $name
	 * @param string $phone
	 * @param string $street
	 * @param string $country
	 * @param string $postalCode
	 * @param string $vatNumber
	 *
	 * @return void
	 */
	public function setAddressDelivery($city, $name, $phone, $street, $country, $postalCode, $vatNumber = '')
	{
		$this->addressDelivery = $this->commonPrepareAddress(
			$city,
			$name,
			$phone,
			$street,
			$country,
			$postalCode,
			$vatNumber
		);
	}

	/**
	 * @return array
	 */
	public function prepareCartDataArray()
	{

		$data = [
			'address' => [
				'billing'  => $this->addressBilling,
				'delivery' => $this->addressDelivery,
			],
			'items'   => $this->items,
		];

		if(!empty($this->discount)) {
			$data['discount'] = $this->discount;
		}

		if(!empty($this->shipping)) {
			$data['shipping'] = $this->shipping;
		}

		if(!empty($this->createdAt)) {
			$data['createdAt'] = $this->createdAt;
		}

		if(!empty($this->amount)) {
			$data['amount'] = $this->amount;
		}

		return $data;
	}

	/**
	 * @return string
	 */
	public function prepareCartData()
	{

		$cartData = $this->prepareCartDataArray();

		//if(!empty($this->previous)) {
		//	$cartData['previous'] = $this->preparePrevious($this->previous);
		//}

		return base64_encode(gzencode(json_encode($cartData), 5));
	}

	/**
	 * @param CartData[] $cartDataList
	 *
	 * @return array
	 */
	private function preparePrevious($cartDataList)
	{

		$data = [];

		foreach($cartDataList as $cartData) {

			$data[] = $cartData->prepareCartDataArray();
		}

		return $data;
	}
}
