<?php 
	// Hi!
	//
	// If you want to entirely replace the default JS provided for the verify
	// age form, then copy this file into your theme and add your own JS.
	// 
	// (NB: even if you do copy the file into your theme, do not remove
	// this file from the plugin.)
?>

jQuery(function($){

	var min_age     = <?php echo intval( $this->min_age ); ?>;
	var visitor_age = false;

	if ( dob = $.cookie( 'va_dob' ) ) {
		<?php /* this is a bit messy because we're back-compatting with the yyyymmdd cookie format */ ?>
		ymd = new Array(
			dob.substr(0,4),
			dob.substr(4,2),
			dob.substr(6,2)
		).join('-');
		visitor_age = va_get_age( ymd );
	}

	if ( visitor_age && ( visitor_age >= min_age ) )
		return;

	// Use the title attr as a hint, and clear the hint when the field is focused
	$( '#verify_age_form input:text' ).clearOnFocus();
	va_hide_content();

	$( '#verify_age_form' ).submit( function(e) {

		$( '#va_date_error,#va_too_young' ).hide();

		va_year  = parseInt( $('#va_dob_year').val() );
		va_month = parseInt( $('#va_dob_month').val() );
		va_day   = parseInt( $('#va_dob_day').val() );
		check    = va_check_date( va_year, va_month, va_day );

		if ( check ) {
			if ( va_get_age( check ) >= min_age ) {
				va_show_content();
				va_year  = check.getFullYear();
				va_month = check.getMonth()+1;
				va_day   = check.getDate();
				if ( va_month < 10 )
					va_month = '0' + va_month;
				if ( va_day < 10 )
					va_day = '0' + va_day;
				$.cookie( 'va_dob', '' + va_year + va_month + va_day, { expires : 7, path : '<?php echo COOKIEPATH; ?>' } );
				$.cookie( 'va_dob', '' + va_year + va_month + va_day, { expires : 7, path : '<?php echo SITECOOKIEPATH; ?>' } );
			} else {
				$( '#va_too_young' ).show();
			}
		} else {
			$( '#va_date_error' ).show();
		}

		e.preventDefault();

	} );

	function va_hide_content() {
		$( '#verify_age' ).show();
	}

	function va_show_content() {
		$( '#verify_age' ).fadeOut();
	}

	function va_get_age( dateString ) {
		var today = new Date();
		var birthDate = new Date( dateString );
		var age = today.getFullYear() - birthDate.getFullYear();
		var m = today.getMonth() - birthDate.getMonth();
		if ( ( m < 0 ) || ( m === 0 && today.getDate() < birthDate.getDate() ) )
			age--;
		return age;
	}

	function va_check_date( y, m, d ) {
		// More accurate and non-US-specific version of http://phpjs.org/functions/checkdate/
		obj = new Date( y, ( m - 1 ), d );
		if (
			( m > 0 ) &&
			( m <= 12 ) &&
			( y > 0 ) &&
			( y <= 32768 ) &&
			( d > 0 ) &&
			( d <= 31 ) &&
			( obj.getMonth ) &&
			( ( m - 1 ) == obj.getMonth() )
		)
			return obj;
		else
			return false;
	}

} );
