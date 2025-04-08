function registerImojePblPaymentMethod() {
	const ImojePblPaymentComponent = (props) => {
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
							'imoje-selected-channel': `pbl-${selectedMethod}`,
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
				imoje_pbl_js_object.settings_pbl.description,
			),
			React.createElement(
				'div',
				{
					class: `${imojePaymentMethodContainerClass}__wrapper`,
				},
				imoje_pbl_js_object.payment_methods_pbl.map(method =>
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
							tooltip.message,
						),
					),
				),
			),
			legalRC,
		);
	};

	wc.wcBlocksRegistry.registerPaymentMethod({
		name:           imoje_pbl_js_object.name_pbl,
		label:          React.createElement(
			'span',
			{
				class: imojeBlockCheckoutHeaderClass,
			},
			imoje_pbl_js_object.settings_pbl.title,
			imoje_pbl_js_object.logo_pbl && !isEnabled(imoje_pbl_js_object.settings_pbl.hide_brand) &&
			React.createElement('img', {
				src:   imoje_pbl_js_object.logo_pbl,
				alt:   imoje_pbl_js_object.name_pbl,
				width: 50,
			}),
		),
		ariaLabel:      imoje_pbl_js_object.name_pbl,
		content:        React.createElement(ImojePblPaymentComponent, {}),
		edit:           React.createElement(ImojePblPaymentComponent, {}),
		canMakePayment: imojeCanMakePayment(imoje_pbl_js_object.settings_pbl),
	});
}

registerImojePblPaymentMethod();
