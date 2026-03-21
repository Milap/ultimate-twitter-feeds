<?php
add_action( 'widgets_init', 'utfeed_load_widget' );
add_action( 'init', 'utfeed_register_block' );
add_action( 'wp_footer', 'utfeed_print_widgets_loader', 99 );
add_action( 'admin_init', 'utfeed_register_settings' );
add_action( 'admin_menu', 'utfeed_add_admin_menu' );
add_action( 'admin_post_utfeed_x_oauth_start', 'utfeed_handle_oauth_start' );
add_action( 'admin_post_utfeed_x_oauth_callback', 'utfeed_handle_oauth_callback' );
add_action( 'admin_post_utfeed_x_oauth_disconnect', 'utfeed_handle_oauth_disconnect' );
