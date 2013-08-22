<?php
/**
 * @package Verify Age
 */
?>

<div id="verify_age" class="wrapper">
	<div class="inner">
	
		<h1><?php printf( __( 'You must be at least %d to enter this site', 'verify_age' ), $this->min_age ); ?></h1>
		
		<div id="va_too_young"><?php echo apply_filters( 'va_denial_msg', __( 'Sorry. You are too young to access this site.', 'verify-age' ), false ); ?></div>
	
		<p><?php _e( 'Fill in your age below, please.', 'verify_age' ); ?></p>

		<div id="va_date_error" class="error"><?php _e( 'Please check the date you have entered below, it doesn\'t look like a valid date.', 'verify_age' ); ?></div>

		<form action="<?php verify_age_requested_permalink(); ?>" method="post" name="verify_age_form" id="verify_age_form">
			<input type="hidden" name="va_dob_form" value="1" />
			<p>
				<label for="va_dob_day">
					<?php _e( 'Day', 'verify_age' ); ?>
					<input 
						class="text text_2_chars" 
						id="va_dob_day" 
						maxlength="2" 
						name="va_dob_day" 
						size="2" 
						tabindex="1" 
						title="<?php _e( 'DD', 'verify_age' ); ?>" 
						type="text"
						value="<?php echo $day; ?>" 
							/>
				</label>
				<label for="va_dob_month">
					<?php _e( 'Month', 'verify_age' ); ?>
					<input 
						class="text text_2_chars" 
						id="va_dob_month" 
						maxlength="2" 
						name="va_dob_month" 
						size="2" 
						tabindex="2" 
						title="<?php _e( 'MM', 'verify_age' ); ?>" 
						type="text"
						value="<?php echo $month; ?>" 
							/>
				</label>
				<label for="va_dob_year">
					<?php _e( 'Year', 'verify_age' ); ?>
					<input 
						class="text text_4_chars" 
						id="va_dob_year" 
						maxlength="4" 
						name="va_dob_year" 
						size="4" 
						tabindex="3" 
						title="<?php _e( 'YYYY', 'verify_age' ); ?>" 
						type="text" 
						value="<?php echo $year; ?>" 
							/>
				</label>
			</p>
			<p>
				<input type="submit" value="<?php esc_attr_e( 'Submit', 'verify_age' ); ?>" name="Submit" tabindex="4" />
			</p>
		</form>

	</div><!-- .inner -->
</div><!-- #verify_age .wrapper -->