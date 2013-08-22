// v1.0
// Handy dandy JS clear on focus magic based on
// http://bassistance.de/2007/01/23/unobtrusive-clear-searchfield-on-focus/
// An anonymous function to wrap around the new methods to avoid conflict

( function( $ ){

	// Call the extend method to attach our new methods to jQuery
	$.fn.extend( {

		clearOnFocus: function( override_text ) {
			// Iterate over the current set of matched elements
			this.each( function() {
				// Set the hint text via JS or take it from the title attribute
				var defaultValue;
				if ( override_text )
					defaultValue = override_text;
				else
					defaultValue = $( this ).attr( 'title' );
				// Only add the hint text if there's nothing in the field currently
				if ( ! $.trim( $( this ).val() ) )
					$( this ).val( defaultValue ).addClass( 'hinted' );
				// Set the behaviours for the field
				$( this ).focus( function() {
					if( $( this ).val() == defaultValue )
						$( this ).val( '' )
							.removeClass( 'hinted' );
				} ).blur( function() {
					if( ! $( this ).val() )
						$( this ).val( defaultValue )
							.addClass( 'hinted' );
				} );
			} );
			return this;
		}

	}); // Close the extend method call

// Pass jQuery to the function, so that we will able to use any valid 
// Javascript variable name to replace "$" SIGN.
} )( jQuery );  
