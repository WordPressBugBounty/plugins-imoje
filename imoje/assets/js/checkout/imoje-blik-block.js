const imojeBlik = 'blik';
function registerImojeBlikPaymentMethod() {
	const ImojeBlikPaymentComponent = (props) => {
		const [selectedMethod, setSelectedMethod] = React.useState(imojeBlik);
		const [blikCode, setBlikCode] = React.useState('');
		const [tooltip, setTooltip] = React.useState(null);

		React.useEffect(() => {
			const unsubscribe = props.eventRegistration.onPaymentSetup(async (processingProps) => {
				if (!processingProps.paymentData) {
					processingProps.paymentData = {};
				}

				if (selectedMethod === imojeBlik && (!blikCode || blikCode.length !== 6) && isEnabled(imoje_blik_js_object.settings_blik.view_field)) {
					return {type: props.emitResponse.responseTypes.ERROR, message: imoje_blik_js_object.blik_text};
				}

				return {
					type: props.emitResponse.responseTypes.SUCCESS,
					meta: {
						paymentMethodData: {
							'imoje-selected-channel': `${imojeBlik}-${imojeBlik}`,
							'imoje-blik-code':        blikCode,
						},
					},
				};
			});

			return () => unsubscribe();
		}, [selectedMethod, blikCode, props.eventRegistration]);

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
				imoje_blik_js_object.settings_blik.description,
			),
			imoje_blik_js_object.settings_blik.view_field === 'no' &&
			React.createElement(
				'div',
				{
					class: `${imojePaymentMethodContainerClass}__wrapper`,
				},
				imoje_blik_js_object.payment_methods_blik.map((method) =>
					React.createElement(
						'div',
						{
							key: method.payment_method_code,
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
							React.createElement('img', {
								src: method.payment_method_code === imojeBlik
									     ? imoje_blik_js_object.logo_blik
									     : imoje_blik_js_object.logo_blik_oneclick,
								alt: method.description,
								class: `${imojePaymentMethodContainerClass}__img`,
							}),
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

			imoje_blik_js_object.settings_blik.view_field === 'yes' &&
			React.createElement('div', {
					class: `${imojePaymentMethodContainerClass}__blik-code-wrapper wc-block-components-text-input`,
				},
				React.createElement('input', {
					type:        'text',
					id:          'imoje-blik-input',
					class:       `${imojePaymentMethodContainerClass}__blik-code-input`,
					maxLength:   6,
					value:       blikCode,
					onChange:    (e) => setBlikCode(e.target.value.replace(/\D/g, '')),
					placeholder: imoje_blik_js_object.blik_text,
				}),
			),

			legalRC,
		);
	};

	wc.wcBlocksRegistry.registerPaymentMethod({
		name:           imoje_blik_js_object.name_blik,
		label:          React.createElement(
			'span',
			{
				class: imojeBlockCheckoutHeaderClass,
			},
			imoje_blik_js_object.settings_blik.title,
			imoje_blik_js_object.logo_blik && !isEnabled(imoje_blik_js_object.settings_blik.hide_brand) &&
			React.createElement('img', {
				src: imoje_blik_js_object.logo_blik,
				alt: imoje_blik_js_object.name_blik,
			}),
		),
		ariaLabel:      imoje_blik_js_object.name_blik,
		content:        React.createElement(ImojeBlikPaymentComponent, {}),
		edit:           React.createElement(ImojeBlikPaymentComponent, {}),
		canMakePayment: imojeCanMakePayment(imoje_blik_js_object.settings_blik),
	});
}

registerImojeBlikPaymentMethod();
