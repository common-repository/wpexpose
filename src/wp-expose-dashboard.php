<?php 
if ( ! defined( 'ABSPATH' ) || !defined('WPEX_folder')) exit;


if(!function_exists('wpex_dashboard_setup')) {
	function wpex_dashboard_setup() {
		wp_add_dashboard_widget(
			'wp-expose-dashboard-widget',
			'Expose',
			'wpex_prefix_dashboard_widget',
			$control_callback = null
		);
	}
}
add_action( 'wp_dashboard_setup', 'wpex_dashboard_setup' );

if(!function_exists('wpex_prefix_dashboard_widget')) {
	// https://codex.wordpress.org/Function_Reference/wp_add_dashboard_widget
	function wpex_prefix_dashboard_widget()
	{
		# default output
		global $wpdb;
		$count = $wpdb->query('SELECT id FROM ' . $wpdb->prefix.WPEX_instrusions . ' LIMIT 1');
		$output = sprintf('<p style="text-align:center">%s</p>',  ( ($count > 0 ) ? __( 'You have security events', 'wp-expose' ) : __( 'You haven\'t security events', 'wp-expose' )) );
		
		if ($count > 0 ) {
			$output = sprintf('<p style="text-align:center"><strong>%s</strong></p>', __( 'You have security events', 'wp-expose' ));
		}
		else {
			$output = sprintf('<p style="text-align:center">%s</p>', __( 'You haven\'t security events', 'wp-expose' ));
		}
		
		echo '<div class="feature_post_class_wrap">
			<label style="background:#ccc;">'.$output.'</label></div>';
	}
}

if(!function_exists('wpex_prefix_dashboard_widget_handle')) {
	function wpex_prefix_dashboard_widget_handle()
	{
		
	}
}
