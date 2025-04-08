const imojePaymentMethodContainerClass = 'imoje-payment-method-container'
const imojeBlockCheckoutHeaderClass = 'imoje-block-checkout__header'

function isEnabled(value){
	return value === 'yes'
}

const regulationLinkPaylater = React.createElement(
	'a',
	{ href: imoje_common_js_object.legal_copy.regulation_url, target: "_blank" },
	imoje_common_js_object.legal_copy.regulation_text
);

const iodoLinkPaylater = React.createElement(
	'a',
	{ href: imoje_common_js_object.legal_copy.iodo_url, target: "_blank"},
	imoje_common_js_object.legal_copy.iodo_text
);

const consentMessage = React.createElement(
	'span',
	null,
	imoje_common_js_object.legal_copy.consent_text.split('{regulation}')[0],
	regulationLinkPaylater,
	imoje_common_js_object.legal_copy.consent_text.split('{regulation}')[1]?.split('{iodo}')[0],
	iodoLinkPaylater,
	imoje_common_js_object.legal_copy.consent_text.split('{iodo}')[1]
);

const legalRC = React.createElement(
	'div',
	{
		class: `${imojePaymentMethodContainerClass}__legal`
	},
	consentMessage
);

function imojeCanMakePayment(paymentSettings) {
	return function () {
		const currentCurrency = wc.wcSettings.CURRENCY.code.toLowerCase();
		return Array.isArray(paymentSettings.currencies) && paymentSettings.currencies.includes(currentCurrency);
	};
}