function useInstallmentsScript(url, callback) {
	React.useEffect(() => {
		const existingScript = document.getElementById('imoje-installments__script');

		if (existingScript) {
			if (callback) {
				callback();
			}
			return;
		}

		const script = document.createElement('script');
		script.id = 'imoje-installments__script';
		script.src = url;
		script.async = true;
		script.onload = callback;
		script.onerror = () => console.error('Failed to load script:', url);

		document.body.appendChild(script);

		return () => {
			if (script.parentNode) {
				script.parentNode.removeChild(script);
			}
		};
	}, [url, callback]);
}

function createInstallmentsWrapper() {
	let wrapper = document.getElementById('imoje-installments__wrapper');

	wrapper = document.createElement('div');
	wrapper.id = 'imoje-installments__wrapper';
	Object.assign(wrapper.dataset, {
		installmentsAmount:     imoje_installments_js_object.calculator_data.amount,
		installmentsCurrency:   imoje_installments_js_object.calculator_data.currency,
		installmentsServiceId:  imoje_installments_js_object.calculator_data.serviceId,
		installmentsMerchantId: imoje_installments_js_object.calculator_data.merchantId,
		installmentsSignature:  imoje_installments_js_object.calculator_data.signature,
	});
	wrapper.style.cssText = 'padding: 10px; display: flex; justify-content: center; align-items: center;';

	const paymentContainer = document.getElementById('imoje-installments-container');
	if (paymentContainer) {
		paymentContainer.appendChild(wrapper);
	}

	return wrapper;
}

function initInstallmentsWidget() {
	const wrapper = createInstallmentsWrapper();
	if (wrapper && typeof wrapper.imojeInstallments === 'function') {
		wrapper.imojeInstallments({
			amount:     imoje_installments_js_object.calculator_data.amount,
			currency:   imoje_installments_js_object.calculator_data.currency,
			serviceId:  imoje_installments_js_object.calculator_data.serviceId,
			merchantId: imoje_installments_js_object.calculator_data.merchantId,
			signature:  imoje_installments_js_object.calculator_data.signature,
		});
	}
}

function registerImojeInstallmentsPaymentMethod() {
	const ImojeInstallmentsPaymentElement = (props) => {
		const [imojeInstallmentsChannel, setImojeInstallmentsChannel] = React.useState({channel: '', period: 0});

		useInstallmentsScript(imoje_installments_js_object.calculator_data.url, initInstallmentsWidget);

		React.useEffect(() => {
			const handleIframeMessage = (event) => {
				if (event.data?.channel && event.data?.period) {
					setImojeInstallmentsChannel({
						channel: event.data.channel,
						period:  event.data.period,
					});
				}
			};

			window.addEventListener('message', handleIframeMessage);

			return () => {
				window.removeEventListener('message', handleIframeMessage);
			};
		}, []);

		React.useEffect(() => {
			props.eventRegistration.onPaymentSetup(async (processingProps) => {
				return {
					type: props.emitResponse.responseTypes.SUCCESS,
					meta: {
						paymentMethodData: {
							'imoje-selected-channel-installments': `${imoje_installments_js_object.name_installments}-${imojeInstallmentsChannel.channel}`,
							'imoje-installments-period':           `${imojeInstallmentsChannel.period}`,
						},
					},
				};
			});
		}, [imojeInstallmentsChannel]);

		return React.createElement(
			'div',
			{
				class: imojePaymentMethodContainerClass,
			},
			React.createElement(
				'div',
				{
					id: 'imoje-installments-container',
				},
				'',
			),
			legalRC,
		);
	};

	wc.wcBlocksRegistry.registerPaymentMethod({
		name:           imoje_installments_js_object.name_installments,
		label:          React.createElement(
			'span',
			{
				class: imojeBlockCheckoutHeaderClass,
			},
			imoje_installments_js_object.settings_installments.description,
			imoje_installments_js_object.logo_installments && !isEnabled(imoje_installments_js_object.settings_installments.hide_brand) &&
			React.createElement('img', {src: imoje_installments_js_object.logo_installments, alt: imoje_installments_js_object.name_installments}),
		),
		ariaLabel:      imoje_installments_js_object.name_installments,
		edit:           React.createElement(ImojeInstallmentsPaymentElement),
		content:        React.createElement(ImojeInstallmentsPaymentElement),
		canMakePayment: imojeCanMakePayment(imoje_installments_js_object.settings_installments),
	});
}

registerImojeInstallmentsPaymentMethod();
