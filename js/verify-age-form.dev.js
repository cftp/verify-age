jQuery( document ).ready( verify_age_form );

function verify_age_form() {
	// Use the title attr as a hint, and clear the hint when the field is focussed
	jQuery( '#verify_age_form input:text' ).clearOnFocus();
	// Find everything that's not in our container, and remove it
	jQuery( 'body *' ).not( '#verify_age' ).not( '#verify_age *' ).remove();
}