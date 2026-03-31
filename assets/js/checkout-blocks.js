( function () {
	'use strict';

	var wc = window.wc || {};
	var registry = wc.wcBlocksRegistry;
	var settingsApi = wc.wcSettings;
	var wpElement = window.wp && window.wp.element;
	var htmlEntities = window.wp && window.wp.htmlEntities;
	var i18n = window.wp && window.wp.i18n;

	if ( ! registry || ! settingsApi || ! wpElement ) {
		return;
	}

	var settings = settingsApi.getSetting( 'woompesa_tz_data', {} );
	var registerPaymentMethod = registry.registerPaymentMethod;
	var createElement = wpElement.createElement;
	var useEffect = wpElement.useEffect;
	var useState = wpElement.useState;
	var decodeEntities = htmlEntities && htmlEntities.decodeEntities ? htmlEntities.decodeEntities : function ( value ) { return value; };
	var __ = i18n && i18n.__ ? i18n.__ : function ( value ) { return value; };

	var Label = function () {
		return createElement( 'span', null, decodeEntities( settings.title || 'Mobile Money - TZ' ) );
	};

	var Content = function ( props ) {
		var eventRegistration = props.eventRegistration;
		var emitResponse = props.emitResponse;
		var _useState = useState( '' );
		var phone = _useState[0];
		var setPhone = _useState[1];

		useEffect( function () {
			if ( ! eventRegistration || ! eventRegistration.onPaymentSetup ) {
				return function () {};
			}

			var unsubscribe = eventRegistration.onPaymentSetup( function () {
				if ( ! phone ) {
					return {
						type: emitResponse.responseTypes.ERROR,
						message: __( 'Please enter the M-Pesa phone number.', 'woompesa' ),
					};
				}

				return {
					type: emitResponse.responseTypes.SUCCESS,
					meta: {
						paymentMethodData: {
							woompesa_phone: phone,
						},
					},
				};
			} );

			return unsubscribe;
		}, [ phone, eventRegistration, emitResponse ] );

		return createElement(
			'div',
			{ className: 'woompesa-blocks-field' },
			createElement( 'p', null, decodeEntities( settings.description || '' ) ),
			createElement( 'label', { htmlFor: 'wc-woompesa-phone' }, __( 'Enter your Tanzania phone number', 'woompesa' ) ),
			createElement( 'input', {
				id: 'wc-woompesa-phone',
				type: 'tel',
				value: phone,
				placeholder: __( 'e.g. 0744XXXXXX or 255744XXXXXX', 'woompesa' ),
				onChange: function ( event ) {
					setPhone( String( event.target.value || '' ).replace( /[^\d+]/g, '' ) );
				},
			} ),
			createElement(
				'p',
				{ className: 'woompesa-checkout-help' },
				__( 'After placing the order, confirm the payment on your phone. The order will be completed once our M-Pesa confirms the transaction.', 'woompesa' )
			)
		);
	};

	registerPaymentMethod( {
		name: 'woompesa_tz',
		label: createElement( Label, null ),
		content: createElement( Content, null ),
		edit: createElement( Content, null ),
		canMakePayment: function () {
			return true;
		},
		ariaLabel: decodeEntities( settings.title || 'Mobile Money - TZ' ),
		supports: {
			features: settings.supports ? settings.supports.features : [ 'products' ],
		},
	} );
}() );
