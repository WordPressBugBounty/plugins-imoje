( function () {

	const data = wc.wcSettings.getSetting( 'imoje_visa_data' );
	const { containerClass, headerClass, isEnabled, canMakePayment } = window.imojeBlock;

	const element = React.createElement(
		'div',
		{ class: containerClass },
		React.createElement(
			'div',
			{ class: `${ containerClass }__title` },
			data.settings.description
		)
	);

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
		edit:           element,
		content:        element,
		canMakePayment: canMakePayment( data ),
	} );
} )();
