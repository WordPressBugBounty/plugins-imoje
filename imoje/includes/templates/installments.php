<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array $installments_data */

?>

<div class="imoje-payment-method-container">
	<div class="imoje-installments__wrapper" id="imoje-installments__wrapper"
	     data-installments-amount="<?php echo $installments_data['amount']; ?>"
	     data-installments-currency="<?php echo $installments_data['currency']; ?>"
	     data-installments-service-id="<?php echo $installments_data['serviceId']; ?>"
	     data-installments-merchant-id="<?php echo $installments_data['merchantId']; ?>"
	     data-installments-signature="<?php echo $installments_data['signature']; ?>"
	     data-installments-url="<?php echo $installments_data['url']; ?>"
	>
	</div>

	<input name="imoje-selected-channel-installments" id="imoje-selected-channel-installments" hidden>
	<input name="imoje-installments-period" id="imoje-installments-period" hidden>
</div>
