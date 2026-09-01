(function () {

	const containerClass = 'imoje-payment-method-container';
	const headerClass = 'imoje-block-checkout__header';

	const isEnabled = (value) => value === 'yes';

	window.addEventListener('pageshow', function (event) {
		if (event.persisted) {
			window.location.reload();
		}
	});

	const canMakePayment = (data) => () => {
		const currentCurrency = wc.wcSettings.CURRENCY.code.toLowerCase();
		const currencies = data && data.settings && data.settings.currencies;

		return Array.isArray(currencies) && currencies.includes(currentCurrency);
	};

	const buildLegalElement = (legalCopy) => {

		if (!legalCopy || !legalCopy.consent_text) {
			return null;
		}

		const regulationLink = React.createElement(
			'a',
			{href: legalCopy.regulation_url, target: '_blank'},
			legalCopy.regulation_text
		);

		const iodoLink = React.createElement(
			'a',
			{href: legalCopy.iodo_url, target: '_blank'},
			legalCopy.iodo_text
		);

		const [beforeRegulation, afterRegulation = ''] = legalCopy.consent_text.split('{regulation}');
		const [beforeIodo = '', afterIodo = ''] = afterRegulation.split('{iodo}');

		const consentMessage = React.createElement(
			'span',
			null,
			beforeRegulation,
			regulationLink,
			beforeIodo,
			iodoLink,
			afterIodo
		);

		return React.createElement(
			'div',
			{class: `${containerClass}__legal`},
			consentMessage
		);
	};

	const paymentChannelsBlock = (paymentMethods, selectedMethod, setSelectedMethod, tooltip, setTooltip, setSelectedMethodCode) => {
		return React.createElement(
			'div',
			{class: `${containerClass}__wrapper`},
			!paymentMethods || Object.keys(paymentMethods).length === 0
				? React.createElement(
					'div',
					{class: `${containerClass}__empty`},
					'Brak kanałów płatności. Wybierz inną metodę płatności.' // TODO: i18n
				)
				: Object.values(paymentMethods).map((method) =>
					React.createElement(
						'div',
						{
							key:   method.payment_method_code,
							class: `${containerClass}__method-wrapper`,
						},
						React.createElement(
							'label',
							{
								class:        [
									`${containerClass}__label`,
									method.is_available
										? (selectedMethod === method.payment_method_code
											? `${containerClass}__label--active`
											: '')
										: `${containerClass}__label--not-available`,
								].filter(Boolean).join(' '),
								onClick:      () => {
									if (method.is_available) {
										setSelectedMethod(method.payment_method_code);
										if (setSelectedMethodCode) {
											setSelectedMethodCode(method.payment_method);
										}
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
								src:   method.logo,
								alt:   method.description,
								class: `${containerClass}__img`,
							})
						),
						!method.is_available && method?.tooltip && tooltip?.methodCode === method.payment_method_code &&
						React.createElement(
							'div',
							{class: `${containerClass}__tooltip`},
							method.tooltip
						)
					)
				)
		)
	}

	window.imojeBlock = {
		containerClass,
		headerClass,
		isEnabled,
		canMakePayment,
		buildLegalElement,
		paymentChannelsBlock
	};
})();
