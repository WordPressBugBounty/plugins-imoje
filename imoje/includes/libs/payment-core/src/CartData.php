<?php

namespace Imoje\Payment;

/**
 * Class CartData
 *
 * @package Imoje\Payment
 */
class CartData {

	/**
	 * @var array
	 */
	public $items;

	/**
	 * @var array
	 */
	private $addressBilling;

	/**
	 * @var int
	 */
	public $createdAt;

	/**
	 * @var int
	 */
	public $amount;

	/**
	 * @var array
	 */
	private $addressDelivery;

	/**
	 * @var array
	 */
	private $shipping = array();

	/**
	 * @var array
	 */
	private $discount = array();

	/**
	 * @var string
	 */
	private $basisForVatExemption = '';

	/**
	 * @return array
	 */
	public function getShipping() {
		return $this->shipping;
	}

	/**
	 * @return array
	 */
	public function getAddressBilling() {
		return $this->addressBilling;
	}

	/**
	 * @return string
	 */
	public function getBasis() {
		return $this->basisForVatExemption;
	}

	/**
	 * @param string $id
	 * @param int    $vat
	 * @param string $name
	 * @param int    $amount
	 * @param int    $quantity
	 * @param bool   $isUnitPrice
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

		if ( $isUnitPrice ) {

			$this->items[] = [
				'id'       => $id,
				'vat'      => $vat,
				'name'     => $name,
				'amount'   => $amount,
				'quantity' => $quantity,
			];

			return;
		}

		$amountCalc = floor( $amount / $quantity );

		if ( (float) $amount !== ( $amountCalc * $quantity ) ) {

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
			'quantity' => $quantity,
		];
	}

	/**
	 * @param int    $vat
	 * @param string $name
	 * @param int    $amount
	 *
	 * @return void
	 */
	public function setDiscount(
		$vat,
		$name,
		$amount
	) {
		$this->discount = [
			'vat'    => $vat,
			'name'   => $name,
			'amount' => $amount,
		];
	}

	/**
	 * @param int    $vat
	 * @param string $name
	 * @param int    $amount
	 *
	 * @return void
	 */
	public function setShipping(
		$vat,
		$name,
		$amount
	) {

		$this->shipping = [
			'vat'    => $vat,
			'name'   => $name,
			'amount' => $amount,
		];
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
	public function setAddressBilling(
		$city,
		$name,
		$phone,
		$street,
		$country,
		$postalCode,
		$vatNumber = ''
	) {

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
	private function commonPrepareAddress(
		$city,
		$name,
		$phone,
		$street,
		$country,
		$postalCode,
		$vatNumber = ''
	) {
		$array = [
			'city'       => $city,
			'name'       => $name,
			'phone'      => $phone,
			'street'     => $street,
			'country'    => $country,
			'postalCode' => $postalCode,
		];

		if ( $vatNumber ) {
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
	public function setAddressDelivery(
		$city,
		$name,
		$phone,
		$street,
		$country,
		$postalCode,
		$vatNumber = ''
	) {
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
	public function prepareCartDataArray() {

		$data = [
			'address' => [
				'billing'  => $this->addressBilling,
				'delivery' => $this->addressDelivery,
			],
			'items'   => $this->items,
		];

		if ( ! empty( $this->discount ) ) {
			$data['discount'] = $this->discount;
		}

		if ( ! empty( $this->shipping ) ) {
			$data['shipping'] = $this->shipping;
		}

		if ( ! empty( $this->createdAt ) ) {
			$data['createdAt'] = $this->createdAt;
		}

		if ( ! empty( $this->amount ) ) {
			$data['amount'] = $this->amount;
		}

		return $data;
	}

	/**
	 * @param string $basis
	 *
	 * @return void
	 */
	public function setBasis(
		$basis
	) {

		$this->basisForVatExemption = $basis;
	}
}
