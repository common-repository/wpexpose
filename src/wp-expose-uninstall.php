<?php 
if ( ! defined( 'ABSPATH' ) || !defined('WPEX_folder') ) exit;

if(!function_exists('wpex_activate')) {
	function wpex_activate() {
		// Activation code here...
		wpex_create_tables();
	}
}
// https://codex.wordpress.org/Function_Reference/register_activation_hook
register_activation_hook( WPEX_folder . '/wp-expose.php', 'wpex_activate' );

if(!function_exists('wpex_deactivate')) {
	function wpex_deactivate() {
		// Deactivation code here...
		wpex_drop_tables();
		// clean menu options
		wpex_remove_menus();
		
		// scheduled tasks
		// https://developer.wordpress.org/reference/functions/wp_clear_scheduled_hook/
		//wp_clear_scheduled_hook ('wp_exploit_search_hook');
		
		wpex_remove_options();
		
	}
}
//https://codex.wordpress.org/Function_Reference/register_deactivation_hook
register_deactivation_hook( WPEX_folder . '/wp-expose.php', 'wpex_deactivate' );

if(!function_exists('wpex_deactivate')) {
	function wpex_uninstall() 
	{
		// Uninstall code here...
		// options, tables deleting   
	}
}
//https://codex.wordpress.org/Function_Reference/register_uninstall_hook
register_uninstall_hook( WPEX_folder . '/wp-expose.php', 'wpex_uninstall' );

if(!function_exists('wpex_create_tables')) {
	function wpex_create_tables() {
		// https://codex.wordpress.org/Creating_Tables_with_Plugins
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		// Table with compatibility with PHPIDS
		$sql = 'CREATE TABLE '.$wpdb->prefix.WPEX_instrusions.' ('.
				'id int(11) unsigned NOT NULL auto_increment,'.
				'name varchar(128) NOT NULL default "",'.
				'description varchar(254) NOT NULL default "",'.
				'value text NOT NULL,'.
				'page varchar(255) NOT NULL default "",'.
				'ip varchar(15) NOT NULL default "",'.
				'ip2 varchar(15) NOT NULL default "",'.
				'tags varchar(255) NOT NULL default "",'.
				'impact int(11) unsigned NOT NULL default "0",'.
				'origin varchar(15) NOT NULL default "",'.
				'created datetime NOT NULL,'.
				'PRIMARY KEY (id)) '.$charset_collate.' ENGINE=MyISAM;';
		/**/
		$sql .= ' CREATE TABLE IF NOT EXISTS '.$wpdb->prefix.WPEX_ipban.' ('.
			' id int(11) unsigned NOT NULL auto_increment,'.
			' ip4 varchar(15),'.
			' ip6 varchar(45),'.
			' created datetime NOT NULL,'.
			' PRIMARY KEY (id),'.
			' INDEX `ip4` (`ip4`),'.
			' INDEX `created` (`created`)'.
			')'.$charset_collate.' ENGINE=MyISAM;';
		/**/
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		
	}
}
if(!function_exists('wpex_drop_tables')) {
	function wpex_drop_tables() 
	{
		global $wpdb;
		$wpdb->query('DROP TABLE '.$wpdb->prefix.WPEX_instrusions);
		$wpdb->query('DROP TABLE '.$wpdb->prefix.WPEX_ipban);
	}
}

if(!function_exists(wpex_remove_menus)) {

	function wpex_remove_menus()
	{
		remove_menu_page( 'wp-expose-menu-page.php' );
		remove_menu_page( 'wp-expose-menu-page-config.php' );
		remove_menu_page( 'wp-expose-admin-page-ipban.php' );
	   
	}
}

if(!function_exists('wpex_remove_options')) {
	function wpex_remove_options() {
		// quitamos las opciones credas: wpe_contact_email, wpe_contact_email, wpe_threshold
		delete_option('wpe_contact_email');
		delete_option('wpe_ips_active');
		delete_option('wpe_threshold');
		delete_option('wpe_ipban');
	}
}