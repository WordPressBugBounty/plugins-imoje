(function () {

	const data = wc.wcSettings.getSetting('imoje_paylater_data');
	const {containerClass, headerClass, isEnabled, canMakePayment, buildLegalElement, paymentChannelsBlock} = window.imojeBlock;

	const Component = (props) => {
		const [selectedMethod, setSelectedMethod] = React.useState(null);
		const [tooltip, setTooltip] = React.useState(null);

		React.useEffect(() => {
			const unsubscribe = props.eventRegistration.onPaymentSetup(async (processingProps) => {
				if (!processingProps.paymentData) {
					processingProps.paymentData = {};
				}

				if (!selectedMethod) {
					return {
						type:    props.emitResponse.responseTypes.ERROR,
						message: data.choose_payment_method,
					};
				}

				return {
					type: props.emitResponse.responseTypes.SUCCESS,
					meta: {
						paymentMethodData: {
							'imoje-selected-channel': `imoje_paylater-${selectedMethod}`,
						},
					},
				};
			});

			return () => unsubscribe();
		}, [selectedMethod, props.eventRegistration]);

		return React.createElement(
			'div',
			{class: containerClass},
			React.createElement(
				'div',
				{class: `${containerClass}__title`},
				data.settings.description
			),
			paymentChannelsBlock(data.payment_methods, selectedMethod, setSelectedMethod, tooltip, setTooltip),
			buildLegalElement(data.legal_copy),
			selectedMethod === 'imoje_twisto' && data.twisto_legal &&
			React.createElement(
				'span',
				{
					class:                   `${containerClass}__twisto`,
					dangerouslySetInnerHTML: {__html: data.twisto_legal},
				}
			)
		);
	};

	wc.wcBlocksRegistry.registerPaymentMethod({
		name:           data.name,
		label:          React.createElement(
			'span',
			{class: headerClass},
			data.settings.title,
			data.logo && !isEnabled(data.settings.hide_brand) &&
			React.createElement('img', {src: data.logo, alt: data.name, width: 50})
		),
		ariaLabel:      data.name,
		content:        React.createElement(Component, {}),
		edit:           React.createElement(Component, {}),
		canMakePayment: canMakePayment(data),
	});
})();
