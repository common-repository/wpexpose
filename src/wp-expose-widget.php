<?php 
if ( ! defined( 'ABSPATH' ) || !defined('WPEX_folder')) exit;

/* 
 * http://www.wpexplorer.com/create-widget-plugin-wordpress/
 * http://code.tutsplus.com/series/introduction-to-creating-your-first-wordpress-widget--cms-721
 * https://codex.wordpress.org/Dashboard_Widgets_API
 */

class WP_Expose_widget extends WP_Widget {

	// constructor
	function WP_Expose_widget() {
		parent::WP_Widget(false, $name = __('WP Expose', 'wp_widget_plugin') );
	}

	// widget form creation
	function form($instance) {	
		echo __('Hello');
	}

	// widget update
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		// Fields
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['text'] = strip_tags($new_instance['text']);
		$instance['textarea'] = strip_tags($new_instance['textarea']);
		return $instance;
	}

	// widget display
	function widget($args, $instance) {
		extract( $args );
	   // these are the widget options
	   $title = apply_filters('widget_title', $instance['title']);
	   $text = $instance['text'];
	   $textarea = $instance['textarea'];
	   echo $before_widget;
	   // Display the widget
	   echo '<div class="widget-text wp_widget_plugin_box">';

	   // Check if title is set
	   if ( $title ) {
		  echo $before_title . $title . $after_title;
	   }

	   // Check if text is set
	   if( $text ) {
		  echo '<p class="wp_widget_plugin_text">'.$text.'</p>';
	   }
	   // Check if textarea is set
	   if( $textarea ) {
		 echo '<p class="wp_widget_plugin_textarea">'.$textarea.'</p>';
	   }
	   echo '</div>';
	   echo $after_widget;
	}
}

// register widget
add_action('widgets_init', create_function('', 'return register_widget("wp_expose_widget");'));