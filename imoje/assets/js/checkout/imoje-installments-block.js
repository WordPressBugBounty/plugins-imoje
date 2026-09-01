( function () {

	const data = wc.wcSettings.getSetting( 'imoje_installments_data' );
	const { containerClass, headerClass, isEnabled, canMakePayment, buildLegalElement } = window.imojeBlock;

	const calculatorData = data.calculator_data || {};

	function useInstallmentsScript( url, callback ) {
		React.useEffect( () => {
			const existingScript = document.getElementById( 'imoje-installments__script' );

			if ( existingScript ) {
				if ( callback ) {
					callback();
				}
				return;
			}

			if ( ! url ) {
				return;
			}

			const script     = document.createElement( 'script' );
			script.id        = 'imoje-installments__script';
			script.src       = url;
			script.async     = true;
			script.onload    = callback;
			script.onerror   = () => console.error( 'Failed to load script:', url );

			document.body.appendChild( script );

			return () => {
				if ( script.parentNode ) {
					script.parentNode.removeChild( script );
				}
			};
		}, [ url, callback ] );
	}

	function createInstallmentsWrapper() {
		const wrapper = document.createElement( 'div' );
		wrapper.id = 'imoje-installments__wrapper';
		Object.assign( wrapper.dataset, {
			installmentsAmount:     calculatorData.amount,
			installmentsCurrency:   calculatorData.currency,
			installmentsServiceId:  calculatorData.serviceId,
			installmentsMerchantId: calculatorData.merchantId,
			installmentsSignature:  calculatorData.signature,
		} );
		wrapper.style.cssText = 'padding: 10px; display: flex; justify-content: center; align-items: center;'; //TODO: do styli

		const paymentContainer = document.getElementById( 'imoje-installments-container' );
		if ( paymentContainer ) {
			paymentContainer.appendChild( wrapper );
		}

		return wrapper;
	}

	function initInstallmentsWidget() {
		const wrapper = createInstallmentsWrapper();
		if ( wrapper && typeof wrapper.imojeInstallments === 'function' ) {
			wrapper.imojeInstallments( {
				amount:     calculatorData.amount,
				currency:   calculatorData.currency,
				serviceId:  calculatorData.serviceId,
				merchantId: calculatorData.merchantId,
				signature:  calculatorData.signature,
			} );
		}
	}

	const Component = ( props ) => {
		const [ channelData, setChannelData ] = React.useState( { channel: '', period: 0 } );

		useInstallmentsScript( calculatorData.url, initInstallmentsWidget );

		React.useEffect( () => {
			const handleIframeMessage = ( event ) => {
				if ( event.data?.channel && event.data?.period ) {
					setChannelData( {
						channel: event.data.channel,
						period:  event.data.period,
					} );
				}
			};

			window.addEventListener( 'message', handleIframeMessage );

			return () => {
				window.removeEventListener( 'message', handleIframeMessage );
			};
		}, [] );

		React.useEffect( () => {
			props.eventRegistration.onPaymentSetup( async () => {
				return {
					type: props.emitResponse.responseTypes.SUCCESS,
					meta: {
						paymentMethodData: {
							'imoje-selected-channel-installments': `imoje_installments-${ channelData.channel }`,
							'imoje-installments-period':           `${ channelData.period }`,
						},
					},
				};
			} );
		}, [ channelData ] );

		return React.createElement(
			'div',
			{ class: containerClass },
			React.createElement(
				'div',
				{ id: 'imoje-installments-container' },
				''
			),
			buildLegalElement( data.legal_copy )
		);
	};

	wc.wcBlocksRegistry.registerPaymentMethod( {
		name:           data.name,
		label:          React.createElement(
			'span',
			{ class: headerClass },
			data.settings.description,
			data.logo && ! isEnabled( data.settings.hide_brand ) &&
			React.createElement( 'img', { src: data.logo, alt: data.name } )
		),
		ariaLabel:      data.name,
		edit:           React.createElement( Component ),
		content:        React.createElement( Component ),
		canMakePayment: canMakePayment( data ),
	} );
} )();
