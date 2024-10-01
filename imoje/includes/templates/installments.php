<?php

/** @var array $installments_data */

?>

<div class="imoje-payment-method-container">
	<div class="imoje-installments__wrapper" id="imoje-installments__wrapper"
	     data-installments-amount="<?= $installments_data['amount'] ?>"
	     data-installments-currency="<?= $installments_data['currency'] ?>"
	     data-installments-service-id="<?= $installments_data['serviceId'] ?>"
	     data-installments-merchant-id="<?= $installments_data['merchantId'] ?>"
	     data-installments-signature="<?= $installments_data['signature'] ?>"
	     data-installments-url="<?= $installments_data['url'] ?>"
	>
	</div>

	<input name="imoje-selected-channel" id="imoje-selected-channel" hidden>
	<input name="imoje-installments-period" id="imoje-installments-period" hidden>
</div>
