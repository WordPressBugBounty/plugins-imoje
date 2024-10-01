<?php

namespace Imoje\Payment;

/**
 * Class LeaseNow
 *
 * @package Imoje\Payment
 */
class LeaseNow {

	/**
	 * @var array
	 */
	private $items;

	/**
	 * id - product id,
	 * categoryId - category name,
	 * name - product name
	 * amount - unit price net
	 * tax - tax value unit
	 * taxStake - percent tax
	 * quantity - quantity,
	 * url - url to product,
	 * discountAmount - discount amount,
	 * discountTax - discount tax amount
	 *
	 * @param string $id
	 * @param string $categoryId
	 * @param string $name
	 * @param int    $amount
	 * @param int    $tax
	 * @param string $taxStake
	 * @param int    $quantity
	 * @param string $url
	 * @param string $unit
	 * @param string $state
	 * @param int    $discountAmount
	 * @param int    $discountTax
	 *
	 * @return LeaseNow
	 */
	public function addItem(
		$id,
		$categoryId,
		$name,
		$amount,
		$tax,
		$taxStake,
		$quantity,
		$url,
		$discountAmount = 0,
		$discountTax = 0,
		$unit = '',
		$state = ''
	) {

		$item = [
			'id'         => $id,
			'categoryId' => $categoryId,
			'name'       => $name,
			'amount'     => $amount,
			'tax'        => $tax,
			'taxStake'   => $taxStake,
			'quantity'   => $quantity,
			'url'        => $url,
		];

		if ( $discountAmount ) {
			$item['discount']['amount'] = $discountAmount;

			if ( ! $discountTax ) {
				$item['discount']['tax'] = 0;
			}
		}

		if ( $discountTax ) {
			$item['discount']['tax'] = $discountTax;
		}

		if ( $unit ) {
			$item['unit'] = $unit;
		}
		if ( $state ) {
			$item['state'] = $state;
		}

		$this->items[] = $item;

		return $this;
	}

	/**
	 * @param bool $isApi
	 *
	 * @return array|string
	 */
	public function prepare( $isApi ) {
		$data = [
			'items' => $this->items,
		];

		return $isApi
			? $data
			: base64_encode( gzencode( json_encode( $data ), 5 ) );
	}
}
