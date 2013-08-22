<?php 
/*
Plugin Name: Verify Age
Plugin URI: https://github.com/cftp/verify-age
Description: This plugin forces users to verify their age before entering the site, uses Javascript to ensure static page caching is still possible
Version: 1.2
Author: Code For The People Ltd
Author URI: http://www.codeforthepeople.com/
Text Domain:  verify-age
Domain Path:  /languages/
*/

/**
 * WordPress plugin which forces users to verify their age before entering the site
 *
 * @package VerifyAge
 */

/*  Copyright 2012 Simon Wheatley (Code For The People Ltd)
				_____________
			   /      ____   \
		 _____/       \   \   \
		/\    \        \___\   \
	   /  \    \                \
	  /   /    /          _______\
	 /   /    /          \       /
	/   /    /            \     /
	\   \    \ _____    ___\   /
	 \   \    /\    \  /       \
	  \   \  /  \____\/    _____\
	   \   \/        /    /    / \
		\           /____/    /___\
		 \                        /
		  \______________________/

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

require_once( 'template-tags.php' );

/**
 * WordPress plugin class which sets cookies to identify whether someone has verified their age
 *
 * @package default
 * @author Simon Wheatley
 **/
class VerifyAge {

	/**
	 * An int representing the minimum age required to view the site.
	 *
	 * @var int
	 **/
	protected $min_age;
	
	/**
	 * If a date has been submitted, was it valid. Could be NULL, so check with '==='.
	 *
	 * @var bool
	 **/
	public $valid_date;
	
	/**
	 * A flag to record whether a redirect to the requested page
	 * should be issued (to force the use of any cookies set during
	 * this request).
	 *
	 * @var bool
	 **/
	protected $do_redirect;
	
	/**
	 * A flag to record whether the age check was passed when the
	 * user submitted their DOB.
	 *
	 * @var bool
	 **/
	protected $age_check_passed;
	
	/**
	 * A flag to record whether the DOB form has been submitted.
	 *
	 * @var bool
	 **/
	protected $dob_submitted;

	/**
	 * Initiate!
	 *
	 * @return void
	 * @author Simon Wheatley
	 **/
	public function __construct() {
		// Setup
		$this->min_age = get_option( 'va_min_age' );
		$this->do_redirect = false;
		$this->dob_submitted = false;
		$this->age_check_passed = false;

		// Hook it all up
		register_activation_hook( __FILE__, array( & $this, 'activate' ) );
		if ( is_admin() ) {
			add_action( 'admin_init', array( & $this, 'admin_init' ) );
			add_action( 'save_post', array( $this, 'save_post' ), null, 2 );
		}
		add_action( 'init', array( & $this, 'init' ) );
		add_action( 'wp_head', array( & $this, 'wp_head' ) );
		add_action( 'template_redirect', array( & $this, 'check' ) );
	}
	
	// HOOKS AND ALL THAT
	// ==================
	
	/**
	 * Hooked to the plugin activation
	 *
	 * @return void
	 * @author Simon Wheatley
	 **/
	public function activate() {
		// Get the option with a default value
		$value = get_option( 'va_min_age', 18 );
		// Update the option (which will use the above default value if necessary)
		update_option( 'va_min_age', $value );
	}
	
	/**
	 * Hooks the admin_init action
	 *
	 * @return void
	 * @author Simon Wheatley
	 **/
	public function admin_init() {
		// Setup the config field for settings
		add_settings_field( 'va_min_age', __( 'Minimum age', 'verify_age' ), array( & $this, 'min_age_config' ), 'reading' );
		register_setting( 'reading', 'va_min_age', 'intval' );
		add_meta_box( 'va_meta_box', __('Verify Age', 'verify-age'), array( & $this, 'metabox' ), 'page', 'side', 'low');
		add_meta_box( 'va_meta_box', __('Verify Age', 'verify-age'), array( & $this, 'metabox' ), 'post', 'side', 'low');
	}
	
	/**
	 * Callback to display the minimum age config.
	 *
	 * @param  
	 * @return void
	 * @author Simon Wheatley
	 **/
	public function min_age_config() {
		$min_age = (int) get_option( 'va_min_age' );
		?>
		<input id="va_min_age" class="small-text" type="text" value="<?php esc_attr_e( $min_age ); ?>" name="va_min_age"/> <?php _e( 'years', 'autopaginate' ); ?><br />
		<?php _e( 'This is the minimum age someone must be to view your website, it is added by the Verify Age plugin. Disable the plugin to disable this restriction.', 'verify_age' ); ?>
		<?php
	}
	
	/**
	 * Hooks the WP save_post action, fired on all post and page saves
	 * (and possibly elsewhere, e.g. attachments, etc).
	 *
	 * @param int $post_ID The ID of the post being saved 
	 * @param object $post A WP post object
	 * @return void
	 * @author Simon Wheatley
	 **/
	public function save_post( $post_ID, $post ) {
		// Summat to do here? (Check for our nonce.)
		$do_something = (bool) @ $_POST[ '_va_nonce' ];
		if ( ! $do_something )
			return;
		// Check it's a page or post (not revision, etc)
		if ( $post->post_type != 'post' && $post->post_type != 'page' )
			return;
		// If they've ticked the box, we set false (do use age verification)
		// If not, we set true (DO skip verification)
		$restrict = (bool) @ $_POST[ 'va_restrict' ];
		if ( $restrict )
			delete_post_meta( $post_ID, 'va_skip_verification' );
		else
			update_post_meta( $post_ID, 'va_skip_verification', true );
	}
	
	/**
	 * Callback function to echo the HTML for the post/page edit metabox.
	 *
	 * @return void
	 * @author Simon Wheatley
	 **/
	public function metabox() {
		global $post_ID;
		$restrict = ! ( (bool) get_post_meta( $post_ID, 'va_skip_verification', true ) );
		?>
			<div>
				<?php wp_nonce_field( 'va_set_restriction', '_va_nonce' ); ?>
				<label for="va_restrict" class="selectit">
					<input 
						type="checkbox" 
						name="va_restrict" 
						id="va_restrict" 
						<?php checked( $restrict, true ); ?>
						/>
					<?php printf(__('Restrict page/post access to %s+ years', 'verify-age' ), $this->min_age ); ?>
				</label>
				<input type="hidden" name="va_ctrl_present" value="1" />
				<br />
			</div>
		<?php
	}
	
	/**
	 * Hooked to the init function in WordPress
	 *
	 * @return void
	 * @author Simon Wheatley
	 **/
	public function init() {
		$this->process_dob_submission();
		// $this->note_age();
	}
	
	/**
	 * Hook the WordPress wp_head action
	 *
	 * @return void
	 * @author Simon Wheatley
	 **/
	public function wp_head() {
		// Too young? Unverified?
		$this->wp_head_css();
	}
	
	/**
	 * Hooked on template_redirect action. Enqueues all the stuff we need for age verification.
	 *
	 * @return void
	 * @author Simon Wheatley / John Blackbourn
	 **/
	public function check() {
		// Maybe redirect, to force the use of cookies set in *this* request
		if ( $this->do_redirect ) {
			global $post;
			$redirect_to = get_permalink( $post->ID );
			wp_redirect( $redirect_to );
			exit;
		}
		// Are we checking age on this page? Are we on a 404 page?
		if ( $this->skip_verification() || is_404() )
			return;
		return $this->setup_verify_age();
	}

	/**
	 * Render the HTML for the age verification template.
	 *
	 * @return void
	 * @author Simon Wheatley / John Blackbourn
	 **/
	public function verification_form() {
		$vars = array();
		$vars[ 'year' ] = $vars[ 'month' ] = $vars[ 'day' ] = '';
		$this->render( 'verify-age-form.php', $vars );
	}


	// PLUGIN SPECIFIC
	// ===============
	
	/**
	 * Echo the CSS for the HEAD element
	 *
	 * @return void
	 * @author Simon Wheatley / John Blackbourn
	 **/
	protected function wp_head_css() {
		// Nothing to do if: we're skipping verification or this is a 404
		if ( $this->skip_verification() || is_404() )
			return;
		// Echo some CSS into the HEAD element
		echo "<style type='text/css' media='screen'>\n";
		$this->render( 'verify-age-css.php' );
		$this->render( 'verify-age-css-additional.php' );
		echo "\n</style>\n";
		// Echo some JS into the HEAD element
		echo "<script type='text/javascript'>\n";
		echo "/* <![CDATA[ */\n";
		$this->render( 'verify-age-js.php' );
		echo "\n/* ]]> */\n";
		echo "</script>\n";
	}
	
	/**
	 * Checks a post meta value to decide if we should 
	 * skip age verification for this page.
	 *
	 * @return bool Whether to skip age verification or not
	 * @author Simon Wheatley
	 **/
	protected function skip_verification() {
		global $post;
		return (bool) get_post_meta( $post->ID, 'va_skip_verification', true );
	}
	
	/**
	 * Check for the submission of the age verification form and
	 * and process it if it exists.
	 *
	 * @return void
	 * @author Simon Wheatley
	 **/
	protected function process_dob_submission() {
		// Check for the form
		$dob_form = (bool) @ $_POST[ 'va_dob_form' ];
		if ( ! $dob_form )
			return;
		// Record that we've had a form submission by setting dob_submitted to true
		$this->dob_submitted = true;
		// Process the form
		$day = zeroise( (int) @ $_POST[ 'va_dob_day' ], 2 );
		$month = zeroise( (int) @ $_POST[ 'va_dob_month' ], 2 );
		$year = zeroise( (int) @ $_POST[ 'va_dob_year' ], 4 );
		// Is the date valid (with freaky American date sequencing)
		if ( ! checkdate( $month, $day, $year ) )
			return $this->valid_date = false;
		// Set the cookie, and maybe store in user_meta
		$dob = "$year-$month-$day";
		// Set the flag to redirect to the current page, so we use the cookies we're
		// about to set.
		$this->do_redirect = true;
		// Cookie are set on both COOKIEPATH and SITECOOKIEPATH
		setcookie( 'va_dob', $dob, $this->cookie_timeout(), COOKIEPATH, COOKIE_DOMAIN, false, true );
		setcookie( 'va_dob', $dob, $this->cookie_timeout(), SITECOOKIEPATH, COOKIE_DOMAIN, false, true );
		$user = wp_get_current_user();
		if ( ! is_user_logged_in() ) 
			return;
		// User is logged in, set in usermeta
		$user = wp_get_current_user();
		update_user_meta( $user->ID, 'va_dob', $dob );
	}
	
	/**
	 * Sets up the various things we need to verify the age of the current user.
	 *
	 * @return void
	 * @author Simon Wheatley / John Blackbourn
	 **/
	protected function setup_verify_age() {
		// Suffix for enqueuing
		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
		// Add the form HTML to the footer of the page
		add_action( 'wp_footer', array( & $this, 'verification_form' ) );
		// jQuery plugin to allow form input textfield hinting
		wp_enqueue_script( 'jquery_clear_on_focus', $this->plugin_url( "/js/jquery.clear_on_focus$suffix.js" ), array( 'jquery' ), '1.2' );
		wp_enqueue_script( 'jquery.cookie', $this->plugin_url( "/js/jquery.cookie.js" ), array( 'jquery' ), '1.3' );
		// We inject some JS and the CSS to obscure the page into the HEAD 
		// element for stability and to ensure it's applied right away; hence 
		wp_enqueue_style( 'va_form', $this->plugin_url( "/css/verify-age-form$suffix.css" ), null, '1.2' );
	}
	
	/**
	 * Returns the cookie timeout datetime as a UNIX timestamp.
	 *
	 * @return int UNIX timestamp
	 * @author Simon Wheatley
	 **/
	protected function cookie_timeout() {
		// SWFIXME: Set something based on the values set in the plugin admin UI.
		return time() + ( 60 * 60 * 24 * 7 ); // Seven days hence
	}
	
	/**
	 * Uses either usermeta or cookie to determine the visitors stated age.
	 *
	 * @return int Age in years
	 * @author Simon Wheatley
	 **/
	protected function visitor_age() {
		$dob = $this->get_dob();
		// No DOB
		if ( ! $dob )
			return false;
		// Calculate the age
		// DOB should be in yyyy-mm-dd form
		$birthdate = date( 'Ymd', strtotime( $dob ) );
		// Work out the age from the years
		$age = date( 'Y' ) - substr( $birthdate, 0, 4 );
		// If we're not past their birthday in this year, subtract 1
		if ( date( 'md' ) < substr( $birthdate, 4, 4 ) )
			--$age;
		return $age;
	}
	
	
	/**
	 * Retrieve the DOB from either the cookie or usermeta
	 *
	 * @return string DOB in YYYY-MM-DD format
	 * @author Simon Wheatley
	 **/
	protected function get_dob() {
		// Try to get the DOB from usermeta
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			return get_user_meta( $user->ID, 'va_dob', true );
		}
		// Try the cookie
		if ( $cookie_dob = @ $_COOKIE[ 'va_dob' ] )
			return $cookie_dob;
		// Nothing available, return false
		return false;
	}
	

	// GENERIC PLUGIN UTILITIES
	// ========================

	/**
	 * Renders a template, looking first for the template file in the theme directory
	 * and afterwards in this plugin's /theme/ directory.
	 *
	 * @return void
	 * @author Simon Wheatley
	 **/
	protected function render( $template_file, $vars = null ) {
		// Maybe override the template with our own file
		$template_file = $this->locate_template( $template_file );
		
		// Ensure we have the same vars as regular WP templates
		global $posts, $post, $wp_did_header, $wp_did_template_redirect, $wp_query, $wp_rewrite, $wpdb, $wp_version, $wp, $id, $comment, $user_ID;

		if ( is_array($wp_query->query_vars) )
			extract($wp_query->query_vars, EXTR_SKIP);

		// Plus our specific template vars
		if ( is_array( $vars ) )
			extract( $vars );
		
		require_once( $template_file );
	}
	
	/**
	 * Takes a filename and attempts to find that in the designated plugin templates
	 * folder in the theme (defaults to main theme directory, but uses a custom filter
	 * to allow theme devs to specify a sub-folder for all plugin template files using
	 * this system).
	 * 
	 * Searches in the STYLESHEETPATH before TEMPLATEPATH to cope with themes which
	 * inherit from a parent theme by just overloading one file.
	 *
	 * @param string $template_file A template filename to search for 
	 * @return string The path to the template file to use
	 * @author Simon Wheatley
	 **/
	protected function locate_template( $template_file ) {
		$located = '';
		$sub_dir = apply_filters( 'sw_plugin_tpl_dir', '' );
		if ( $sub_dir )
			$sub_dir = trailingslashit( $sub_dir );
		// If there's a tpl in a (child theme or theme with no child)
		if ( file_exists( STYLESHEETPATH . "/$sub_dir" . $template_file ) )
			return STYLESHEETPATH . "/$sub_dir" . $template_file;
		// If there's a tpl in the parent of the current child theme
		else if ( file_exists( TEMPLATEPATH . "/$sub_dir" . $template_file ) )
			return TEMPLATEPATH . "/$sub_dir" . $template_file;
		// Fall back on the bundled plugin template (N.B. no filtered subfolder involved)
		else if ( file_exists( $this->plugin_path( "templates/$template_file" ) ) )
			return $this->plugin_path( "templates/$template_file" );
		// Oh dear. We can't find the template.
		$msg = sprintf( __( "This plugin template could not be found: %s", 'verify-age' ), $template_file );
		echo "<p style='background-color: #ffa; border: 1px solid red; color: #300; padding: 10px;'>$msg</p>";
	}
	
	/**
	 * Returns the URL for for a file/dir within this plugin.
	 *
	 * @param $path string The path within this plugin, e.g. '/js/clever-fx.js'
	 * @return string URL
	 * @author John Blackbourn
	 **/
	protected function plugin_url( $file = '' ) {
		return $this->plugin( 'url', $file );
	}

	/**
	 * Returns the filesystem path for a file/dir within this plugin.
	 *
	 * @param $path string The path within this plugin, e.g. '/js/clever-fx.js'
	 * @return string Filesystem path
	 * @author John Blackbourn
	 **/
	protected function plugin_path( $file = '' ) {
		return $this->plugin( 'path', $file );
	}

	/**
	 * Returns a version number for the given plugin file.
	 *
	 * @param $path string The path within this plugin, e.g. '/js/clever-fx.js'
	 * @return string Version
	 * @author John Blackbourn
	 **/
	protected function plugin_ver( $file ) {
		return filemtime( $this->plugin_path( $file ) );
	}

	/**
	 * Returns the current plugin's basename, eg. 'my_plugin/my_plugin.php'.
	 *
	 * @return string Basename
	 * @author John Blackbourn
	 **/
	protected function plugin_base() {
		return $this->plugin( 'base' );
	}

	function plugin( $item, $file = '' ) {
		if ( !isset( $this->plugin ) ) {
			$this->plugin = array(
				'url'  => plugin_dir_url( __FILE__ ),
				'path' => plugin_dir_path( __FILE__ ),
				'base' => plugin_basename( __FILE__ )
			);
		}
		return $this->plugin[$item] . ltrim( $file, '/' );
	}

} // END VerifyAge class 

$verify_age = new VerifyAge();

?>