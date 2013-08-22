<?php 
/**
 * Template tags for the WP Plugin "Verify Age"
 *
 * @package VerifyAge
 */

/*  Copyright 2010 Simon Wheatley

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

/**
 * Conditional template tag used to work out if the submitted date of birth
 * looked valid or not. An error message could then be displayed.
 *
 * @return bool True if the date submitted looks invalid
 * @author Simon Wheatley
 **/
function va_date_error() {
	global $verify_age;
	return (bool) ( $verify_age->valid_date === false );
}

/**
 * Echoes the permalink for the current page (in a paranoid step to avoid problems
 * caused by other loops on the page resetting the global loop, we get the queried
 * object from the global $wp_query object).
 *
 * @return void
 * @author Simon Wheatley
 **/
function verify_age_requested_permalink() {
	global $wp_query;
	$post = $wp_query->get_queried_object();
	echo apply_filters( 'the_permalink', get_permalink( $post->ID ) );
}

?>