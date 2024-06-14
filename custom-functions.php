<?php
/* remove course grid options meta box from post_type */

add_filter('learndash_course_grid_excluded_post_types', function() {
    return [    'sfwd-transactions', 
                'sfwd-essays', 
                'sfwd-assignment',
                'sfwd-certificates',
                'attachment',
                'posts',
            ];
}, 99);

// Creates shortcode to display current users first & last name.
// usage: [full_name]
add_shortcode('full_name', function() {
    $user = wp_get_current_user();

    return '<span class="shortcode-fullname">' . $user->first_name . ' ' . $user->last_name . '</span>';
});

// Creates shortcode to display whether a user has completed a lesson or not.
add_shortcode( 'ld_lesson_completed', function( $attr ) {
    $args = shortcode_atts( array(
            'lesson_id' => 0,
            'course_id' => 0,
            'user_id'   => get_current_user_id(),
        ), $attr );
    
    if( isset( $args['lesson_id'] ) && 0 == $args['lesson_id'] ) {
        return '<span class="ld-shortcode-ld-no-lesson">No Lesson ID Defined</span>';
    }
    
    $completed = false;
    if( $args['lesson_id'] > 0 && $args['course_id'] > 0 ) {
        $completed = learndash_is_lesson_complete( $args['user_id'], $args['lesson_id'], $args['course_id'] );
    }
    if( $args['lesson_id'] > 0 && 0 == $args['course_id'] ) {
        $completed = learndash_is_lesson_complete( $args['user_id'], $args['lesson_id'] );
    }
    
    if( isset( $completed ) && $completed !== false ) {
        return '<span class="ld-shortcode-ld-complete">Completed</span>';
    }
    
    return '<span class="ld-shortcode-ld-incomplete">Not Completed</span>';
});

// Creates shortcode to display the End date of a Course.
// Usage: [ld_course_end_date course_id='123']
add_shortcode( 'ld_course_end_date', function( $attr ) {
    $args = shortcode_atts( array(
            'course_id' => get_the_ID(),
        ), $attr );
    
    if( isset( $args['course_id'] ) && 0 == $args['course_id'] ) {
        return '<span class="ld-shortcode-ld-no-course">No Course ID Defined</span>';
    }
    
    $settings = get_post_meta($args['course_id'], '_sfwd-courses');

	if( !empty( $settings )) {
        $date_format = get_option( 'date_format' );
        $time_format = get_option( 'time_format' );
        
        return __('Course Ends: ') . date( "{$date_format} {$time_format}", $settings[0]['course_end_date'] );
    }
});

/* Filter to redirect Course grid link to registration, instead of course page.
 * Also, if Course access is set to closed & button_url is configured it 
 * will redirect to the URL that is defined in the button_url field instead.
 */
add_filter( 'learndash_course_grid_custom_button_link', function($button_link, $post_id ) {
    $ld_registration_page_id = (int) LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Registration_Pages', 'registration' );

    $course_pricing = learndash_get_course_price( $post_id );

    if( 'closed' == $course_pricing['type'] ) {
        $post_settings = learndash_get_setting( $post_id );
        $button_link = $post_settings['custom_button_url'] ? $post_settings['custom_button_url'] : $button_link;
    }
    
    if( !empty( $ld_registration_page_id ) && ( 'closed' !== $course_pricing['type'] ) ) {
        if( ( is_user_logged_in() && !sfwd_lms_has_access( $post_id, get_current_user_id() ) ) || !is_user_logged_in() ) {
            $register_url = get_permalink( $ld_registration_page_id );
            $button_link = $register_url . '?ld_register_id=' . $post_id;
        }
    }
    
    return $button_link;
},10,2 );

// Allows Copy/paste on Courses, lessons, topics & quizzes when Prevent copying option is enabled in the LD Integrity plugin
add_action( 'wp_print_scripts', function() {
    $post_ids = array( 123,456,789 );
    $ld_post_types = array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' );
    $current_post_type = get_post_type( get_the_ID() );

    if( !in_array( $current_post_type, $ld_post_types ) && !in_array( get_the_ID(), $post_ids ) ) {
        wp_dequeue_script( 'prevent-content-copy', 100 );
    }
} );

// Creates shortcode to display total user count with access to a course.
add_shortcode( 'ld_course_total_users', function( $attr ) {
    $args = shortcode_atts( array(
            'course_id' => get_the_ID(),
        ), $attr );
    
    if( isset( $args['course_id'] ) ) {
        $course_access_users = learndash_get_course_users_access_from_meta( $args['course_id'] );
        
        return 'Total Users in Course: ' . count( $course_access_users );
    }
});

// Adds total enrolled users count to the infobar on course page.
add_action( 'learndash-course-infobar-status-cell-after', function( $post_type, $course_id, $user_id ) {
    $total_enrolled_users = count( learndash_get_course_users_access_from_meta( $course_id ) );
    
    echo '<span class="ld-course-status-total-enrolled">' . $total_enrolled_users . ' enrolled</span>';
},10, 3 );

// Remove the Discount code entry on the Stripe Payment page. LD Coupons do not use this at all.
add_filter( 'learndash_stripe_session_args', function( $args ) {
    unset( $args['allow_promotion_codes'] );
    
    return array_filter( $args );
}, 99);

// Little function I use when debugging!
if( !function_exists( 'write_log' ) ) {
    function write_log ( $log )  {
	if ( true === WP_DEBUG ) {
            if ( is_array( $log ) || is_object( $log ) ) {
		error_log( print_r( $log, true ) );
            } else {
		error_log( $log );
            }
	}
    }
}

