function registerImojeWtPaymentMethod() {
	const ImojeWtPaymentElement = React.createElement(
		'div',
		{
			class: imojePaymentMethodContainerClass,

		}
		,
		React.createElement(
			'div',
			{
				class: `${imojePaymentMethodContainerClass}__title`,

			},
			imoje_wt_js_object.settings_wt.description,
		),
	);

	wc.wcBlocksRegistry.registerPaymentMethod({
		name:           imoje_wt_js_object.name_wt,
		label:          React.createElement(
			'span',
			{
				class: imojeBlockCheckoutHeaderClass,
			},
			imoje_wt_js_object.settings_wt.title,
			imoje_wt_js_object.logo_wt && !isEnabled(imoje_wt_js_object.settings_wt.hide_brand) &&
			React.createElement('img', {
				src: imoje_wt_js_object.logo_wt,
				alt: imoje_wt_js_object.name_wt,
			}),
		),
		ariaLabel:      imoje_wt_js_object.name_wt,
		edit:           ImojeWtPaymentElement,
		content:        ImojeWtPaymentElement,
		canMakePayment: imojeCanMakePayment(imoje_wt_js_object.settings_wt),
	});
}

registerImojeWtPaymentMethod();
