( function () {

	const data = wc.wcSettings.getSetting( 'imoje_blik_data' );
	const { containerClass, headerClass, isEnabled, canMakePayment, buildLegalElement, paymentChannelsBlock } = window.imojeBlock;

	const BLIK = 'blik';

	const Component = ( props ) => {
		const [ selectedMethod, setSelectedMethod ] = React.useState( BLIK );
		const [ blikCode, setBlikCode ]             = React.useState( '' );
		const [ tooltip, setTooltip ]               = React.useState( null );

		React.useEffect( () => {
			const unsubscribe = props.eventRegistration.onPaymentSetup( async ( processingProps ) => {
				if ( ! processingProps.paymentData ) {
					processingProps.paymentData = {};
				}

				if (
					selectedMethod === BLIK
					&& ( ! blikCode || blikCode.length !== 6 )
					&& isEnabled( data.settings.view_field )
				) {
					return {
						type:    props.emitResponse.responseTypes.ERROR,
						message: data.blik_text,
					};
				}

				return {
					type: props.emitResponse.responseTypes.SUCCESS,
					meta: {
						paymentMethodData: {
							'imoje-selected-channel': `${ BLIK }-${ BLIK }`,
							'imoje-blik-code':        blikCode,
						},
					},
				};
			} );

			return () => unsubscribe();
		}, [ selectedMethod, blikCode, props.eventRegistration ] );

		return React.createElement(
			'div',
			{ class: containerClass },
			React.createElement(
				'div',
				{ class: `${ containerClass }__title` },
				data.settings.description
			),
			data.settings.view_field === 'no' &&
			React.createElement(
				'div',
				{ class: `${ containerClass }__wrapper` },
				paymentChannelsBlock(data.payment_methods, selectedMethod, setSelectedMethod, tooltip, setTooltip)
			),

			data.settings.view_field === 'yes' &&
			React.createElement(
				'div',
				{ class: `${ containerClass }__blik-code-wrapper wc-block-components-text-input` },
				React.createElement( 'input', {
					type:        'text',
					id:          'imoje-blik-input',
					class:       `${ containerClass }__blik-code-input`,
					maxLength:   6,
					value:       blikCode,
					onChange:    ( e ) => setBlikCode( e.target.value.replace( /\D/g, '' ) ),
					placeholder: data.blik_text,
				} )
			),

			buildLegalElement( data.legal_copy )
		);
	};

	wc.wcBlocksRegistry.registerPaymentMethod( {
		name:           data.name,
		label:          React.createElement(
			'span',
			{ class: headerClass },
			data.settings.title,
			data.logo && ! isEnabled( data.settings.hide_brand ) &&
			React.createElement( 'img', { src: data.logo, alt: data.name } )
		),
		ariaLabel:      data.name,
		content:        React.createElement( Component, {} ),
		edit:           React.createElement( Component, {} ),
		canMakePayment: canMakePayment( data ),
	} );
} )();
