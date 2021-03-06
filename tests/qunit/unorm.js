/* global jQuery */
jQuery( function() {
	module( 'unorm' );

	test( 'Basic NFC Normalizations', function() {
		var strs, i, str, result;

		strs = [
			'\u0303\u00d2\u055b',
			'r\u001c\u03af',
			'\u0f76\u0f81'
		];

		for ( i = 0; i < strs.length; i++ ) {
			str = strs[i];
			result = unfc_unorm.nfc( str );
			equal( result, str.normalize(), 'i=' + i );
		}
	});
});

