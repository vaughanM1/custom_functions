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
