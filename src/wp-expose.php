<?php  
if ( ! defined( 'ABSPATH' ) || !defined('WPEX_folder')) exit;

require_once WPEX_folder . DIRECTORY_SEPARATOR . 'src'.DIRECTORY_SEPARATOR.'wp-expose-widget.php';
require_once WPEX_folder . DIRECTORY_SEPARATOR . 'src'.DIRECTORY_SEPARATOR.'wp-expose-dashboard.php';
require_once WPEX_folder . DIRECTORY_SEPARATOR . 'src'.DIRECTORY_SEPARATOR.'wp-expose-admin-page.php';
require_once WPEX_folder . DIRECTORY_SEPARATOR . 'src'.DIRECTORY_SEPARATOR.'wp-expose-admin-page-config.php';
require_once WPEX_folder . DIRECTORY_SEPARATOR . 'src'.DIRECTORY_SEPARATOR.'wp-expose-uninstall.php';
require_once WPEX_folder . DIRECTORY_SEPARATOR . 'src'.DIRECTORY_SEPARATOR.'wp-expose-admin-page-ipban.php';


if( !function_exists('wpex_dashboard_init')) {
	function wpex_dashboard_init() {
		load_plugin_textdomain( 'wp-expose', null, WPEX_folder. '/languages/' );
	}
}
add_action("init", "wpex_dashboard_init");

/* 
 * Administration pages
 */


if(!function_exists('wpex_expose_menu')) {
	function wpex_expose_menu() {
	   
		// new menu page
		// https://codex.wordpress.org/Function_Reference/add_menu_page
		add_menu_page(
			__('WP Expose attacks detected'), 
			__('WP Expose'), 
			'manage_options', 'wp-expose-admin-page.php', 'wpex_admin_page', '', 6);
		add_submenu_page( 'wp-expose-admin-page.php', 
				__('WP Expose Config'), 
				__('Configuration'), 
				'manage_options', 
				'wp-expose-admin-page-config.php', 'wpex_admin_page_config' );
		add_submenu_page( 'wp-expose-admin-page.php', 
				__('WP Expose IP Banned'), 
				__('IP Banned'), 
				'manage_options', 
				'wp-expose-admin-page-ipban.php', 'wpex_admin_ipban' );
	}	
}
add_action('admin_menu', 'wpex_expose_menu');




if(!function_exists('wpex_init_only_front_page')) {
	function wpex_init_only_front_page() {

		if (!is_admin()) {
			//timer_start();
			wpex_init();
			//timer_stop(true,8);
		}
	}
}
add_action( 'plugins_loaded', 'wpex_init_only_front_page', 1);

if(!function_exists('wpex_init')) {
	function wpex_init () {
		global $wpdb; // https://codex.wordpress.org/Class_Reference/wpdb   
		require_once( WPEX_folder . DIRECTORY_SEPARATOR . 'wp-expose-autoload.php');

		// variables
		$contact_email = '';
		$threshold = WPEX_threshold;
		$ips_active = FALSE;
		$ipban_active = FALSE;
		$ip_remote = ((isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
		// acabar la correcta deteccion!!
		// http://stackoverflow.com/questions/444966/working-with-ipv6-addresses-in-php
		// https://www.mikemackintosh.com/5-tips-for-working-with-ipv6-in-php/
		$ip_remote_v6 = '';
		
		set_include_path(
		   get_include_path()
		   . PATH_SEPARATOR
		   . WPEX_folder . '/vendor/'
		);
		
		
		// process begin here
		if (function_exists('get_option')) {
			$contact_email = get_option('wpe_contact_email', get_option('admin_email'));
			$threshold = get_option('wpe_threshold', WPEX_threshold);
			$ips_active = get_option("wpe_ips_active", FALSE);
			$ipban_active = get_option("wpe_ipban", FALSE);
			// meter el intervalo de tiempo.
			// http://dev.mysql.com/doc/refman/5.7/en/date-and-time-functions.html
		}
		
		
		if($ipban_active) {
			$already_banned = wp_cache_get(md5($ip_remote), 'wp_expose_ipban');
			if ( $already_banned === FALSE) {
				// hay que tener en cuenta el tiempo de banneo.
				$already_banned = $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $wpdb->prefix.WPEX_ipban . ' WHERE ip4 = "'. esc_sql($ip_remote).'"');
				if ( $already_banned > 0) {
					wp_cache_set(md5($ip_remote), $ip_remote, 'wp_expose_ipban');
					die( __('IP banned'));
				}
			}
			else {
				die( __('IP banned'));
			}
		}

		/**
		 * If request_order IS EMPTY -> repetimos el proceso con variables_order.
		 * Else:
		 * Si request_order matches 'GP' -> _REQUEST
		 * Si request_order matches 'G' -> añadimos _GET
		 * Si request_order matches 'P' -> añadimos _POST
		 */
		$data = wpex_getVariableToDetect();

		
		$cache = new Expose\Cache\WP();
		$filters = new \Expose\FilterCollection($cache);
		$filters->load();
		
		$pdo = new PDO('mysql:host=' .$wpdb->dbhost . ';dbname=' . $wpdb->dbname, $wpdb->dbuser, $wpdb->dbpassword);
		
		// logger
		$logger = null;
		// Events storage
		$storage = new \Expose\Storage\MysqlStorage($pdo, $wpdb->prefix . WPEX_instrusions );
		$manager = new \Expose\Manager($filters, $logger, null, $storage);
		$manager->setCache($cache);
		$manager->setPHPIDSConverter(true);
		$manager->setThreshold($threshold); 
		
		// Notify
		$notify = new \Expose\Notify\Email();
		$notify->setToAddress($contact_email);
		$notify->setFromAddress($contact_email);
		if(function_exists('get_option'))
		{
			$notify->setAdditionalInfo(' En la web '. get_option("blogname") .'');
		}
		$manager->setNotify($notify);
		
		// Run test
		$notify = true;
		$store = true;
		$stopExecution = false;
		$manager->run($data, false, $notify, $store, $stopExecution);
		unset($pdo);
		spl_autoload_unregister('wpex_autoload');
		
		if ($manager->getImpact() >= $threshold) {
			if($ipban_active)
			{
				$already_banned = $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $wpdb->prefix.WPEX_ipban . ' WHERE ip4 = "'.$ip_remote.'"');
				if ( $already_banned == 0)
				{
					/*
					$wpdb->insert( 
						$wpdb->prefix.WPEX_ipban,
						array( 
							'ip4' => $ip_remote, 
							'ip6' => $ip_remote_v6,
							'created' => date('y-m-j h:i:s')
						), 
						array( 
							'%s', 
							'%s',
							'%s'
						) 
					);
					wp_cache_set(md5($ip_remote), $ip_remote, 'wp_expose_ipban');
					/**/
				}
			}
			if ($ips_active) {
				wp_safe_redirect( home_url(), 302 ); 
				die('');
			}
		}
	}
}

if(!function_exists('wpex_getVariableToDetect')) {
	function wpex_getVariableToDetect($key = 'request_order') {
		// http://php.net/manual/es/ini.core.php#ini.variables-order
		$req = strtoupper ( @ini_get($key));
		$out = array();
		if (empty($req) || strlen($req) == 0)
			$out = wpex_getVariableToDetect('variables_order');
		else {
			if (!empty($_GET) && FALSE !== strripos($req, 'g') ) $out['GET'] = $_GET;
			if (!empty($_POST) && FALSE !== strripos($req, 'p')) $out['POST'] = $_POST;
		}
		return $out;
	}

}

/*
 * Remove version from meta generator
 */
if(!function_exists('wpex_remove_version')) {
	function wpex_remove_version() {
		return '<meta name="generator" content="WordPress" />';
	}
}
add_filter('the_generator', 'wpex_remove_version');

/*
 * Remove version from stylesheet and scripts urls
 */
if(!function_exists('wpex_remove_version_from_head')) {
	function wpex_remove_version_from_head($value) {
		global $nonce;	
		if ( isset($value->default_version)) {
			$value->default_version = '2.0.1';
			//$value->default_version = wp_create_nonce( 'wpex_remove_version' );
		}
		return $value;
	}
}

// adjust
add_filter('wp_default_styles', 'wpex_remove_version_from_head');
add_filter('safe_style_css', 'wpex_remove_version_from_head');
add_filter('wp_default_scripts', 'wpex_remove_version_from_head');
add_filter('wp_print_scripts', 'wpex_remove_version_from_head');
add_filter('print_head_scripts', 'wpex_remove_version_from_head');
add_filter('print_footer_scripts', 'wpex_remove_version_from_head');
add_filter('_wp_footer_scripts', 'wpex_remove_version_from_head');


if(!function_exists('wpex_remove_x_pingback')) {
	function wpex_remove_x_pingback($headers) {
		unset($headers['X-Pingback']);
		return $headers;
	}
}
add_filter('wp_headers', 'wpex_remove_x_pingback');

if(!function_exists('wpex_disable_json')) {
	function wpex_disable_json() {
		/*
		URL without permalinks: http://www.wordpress.nsn/?rest_route=/
		URL with permalinks: http://www.wordpress.nsn/wp-json/
		*/
		if ( preg_match_all('/(?:\?rest_route)|(?:\/wp-json)/i', add_query_arg(array()))) {
			die();
		}
	}
}
add_action( 'init', 'wpex_disable_json' );

if(!function_exists('wpex_check_permission')) {
	function wpex_check_permission()
	{
		if(!is_admin() || !is_user_logged_in() || !user_can_access_admin_page() ) { return false; }
		return true;
	}
}
?>