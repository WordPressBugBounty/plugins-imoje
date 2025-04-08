function registerImojePaylaterPaymentMethod() {
	const ImojePaylaterPaymentComponent = (props) => {
		const [selectedMethod, setSelectedMethod] = React.useState(null);
		const [tooltip, setTooltip] = React.useState(null);

		React.useEffect(() => {
			const unsubscribe = props.eventRegistration.onPaymentSetup(async (processingProps) => {
				if (!processingProps.paymentData) {
					processingProps.paymentData = {};
				}

				if (!selectedMethod) {
					return {type: props.emitResponse.responseTypes.ERROR, message: imoje_common_js_object.choose_payment_method};
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
			{
				class: imojePaymentMethodContainerClass,
			},
			React.createElement(
				'div',
				{
					class: `${imojePaymentMethodContainerClass}__title`,
				},
				imoje_paylater_js_object.settings_paylater.description,
			),
			React.createElement(
				'div',
				{
					class: `${imojePaymentMethodContainerClass}__wrapper`,
				},
				imoje_paylater_js_object.payment_methods_paylater.map(method =>
					React.createElement(
						'div',
						{
							key:   method.payment_method_code,
							class: `${imojePaymentMethodContainerClass}__method-wrapper`,
						},
						React.createElement(
							'label',
							{
								class: `${imojePaymentMethodContainerClass}__label${
									(selectedMethod === method.payment_method_code ? '--active' : '') +
									(method.is_available ? '' : '--not-available')
								}`,
								onClick:      () => {
									if (method.is_available) {
										setSelectedMethod(method.payment_method_code);
									}
								},
								onMouseEnter: () => {
									setTooltip({
										methodCode: method.payment_method_code,
										message:    method.tooltip,
									});
								},
								onMouseLeave: () => setTooltip(null),
							},
							React.createElement(
								'img',
								{
									src: method.logo,
									alt: method.description,
									class: `${imojePaymentMethodContainerClass}__img`,
								},
							),
						),
						!method.is_available && method?.tooltip && tooltip?.methodCode === method.payment_method_code &&
						React.createElement(
							'div',
							{
								class: `${imojePaymentMethodContainerClass}__tooltip`,
							},
							method.tooltip,
						),
					),
				),
			),
			legalRC,
			selectedMethod === 'imoje_twisto' &&
			React.createElement(
				'span',
				{
					class:                   `${imojePaymentMethodContainerClass}__twisto`,
					dangerouslySetInnerHTML: {__html: imoje_paylater_js_object.twisto_legal},
				},
			),
		);
	};

	wc.wcBlocksRegistry.registerPaymentMethod({
		name:           imoje_paylater_js_object.name_paylater,
		label:          React.createElement(
			'span',
			{
				class: imojeBlockCheckoutHeaderClass,
			},
			imoje_paylater_js_object.settings_paylater.title,
			imoje_paylater_js_object.logo_paylater && !isEnabled(imoje_paylater_js_object.settings_paylater.hide_brand) &&
			React.createElement('img', {
				src:   imoje_paylater_js_object.logo_paylater,
				alt:   imoje_paylater_js_object.name_paylater,
				width: 50,
			}),
		),
		ariaLabel:      imoje_paylater_js_object.name_paylater,
		content:        React.createElement(ImojePaylaterPaymentComponent, {}),
		edit:           React.createElement(ImojePaylaterPaymentComponent, {}),
		canMakePayment: imojeCanMakePayment(imoje_paylater_js_object.settings_paylater),
	});
}

registerImojePaylaterPaymentMethod();
