<?php 
if ( ! defined( 'ABSPATH' ) || !defined('WPEX_folder')) exit;

if( ! class_exists( 'Expose_Ipsbanned_List_Table' ) ) {
    require_once( WPEX_folder . DIRECTORY_SEPARATOR . 'classes'. DIRECTORY_SEPARATOR .'expose-list-table.php' );
}

if(!function_exists('wpex_admin_page')) {
	function wpex_admin_page() {
		if(!wpex_check_permission()) {return;}
		
		echo '<div class="wrap">'.
				'<h2>' . __('Event list', 'wp-expose') . 
					'<a href="'.admin_url( 'admin.php?page=wp-expose-admin-page.php&wpe_action=export', '' ).'" class="add-new-h2">' .__('Export', 'wp-expose'). '</a>'.
				'</h2>'.
				'<p>' . __('Security events detected by WP Expose', 'wp-expose') . '</p>'.
			'</div>';
		$action = (isset($_GET['wpe_action'])) ? strtolower(sanitize_text_field($_GET['wpe_action'])) : '';
		switch ($action) {
			case 'export':
				wpex_export_events();
				break;
		}
		echo wpex_admin_table_with_wptable();

	}
}


/**
 * Create admin table with Expose's events using WP_List_Table (default WP tables)
 * @return void
 */
if(!function_exists('wpex_admin_table_with_wptable')) {
	function wpex_admin_table_with_wptable() {
		global $wpdb;
		
		//if(!is_admin() || !is_user_logged_in() || !user_can_access_admin_page() ) { return; }
		if(!wpex_check_permission()) {return;}
		// http://www.paulund.co.uk/wordpress-tables-using-wp_list_table
		$table = new Expose_List_Table($wpdb);
		$table->prepare_items(10);
	?>
		<div class="wrap">
			<div id="icon-users" class="icon32"></div>
			<form method="post">
				<input type="hidden" name="page" value="example_list_table" />
				<?php $table->search_box('search', 'search_id'); ?>
			</form>
			<?php $table->display(); ?>
		</div>
	<?php
	}
}


if(!function_exists('wpex_export_events')) {
	function wpex_export_events() {
		$out = '';
		//if(is_admin() && is_user_logged_in() && user_can_access_admin_page() )
		if(wpex_check_permission())
		{
			$data = _wpex_expose_get();    
			$search = array(';');
			if ( is_array($data) && !empty($data)) {
				$out = 'Name;Description;Page;Value;IP;IP2;Tags;Impact;Origin;Created;' . "\n";
				foreach ($data as $event) {
					$out .= str_replace($search, '', $event->name). ';'
						. str_replace($search, '', $event->description) . ';'
						. str_replace($search, '', $event->page) . ';'
						. str_replace($search, '', $event->value) . ';'
						. str_replace($search, '', $event->ip) . ';'
						. str_replace($search, '', $event->ip2) . ';'
						. str_replace($search, '', $event->tags) . ';'
						. str_replace($search, '', $event->impact) . ';'
						. str_replace($search, '', $event->origin) . ';'
						. str_replace($search, '', $event->created) . ';'
						. "\n";
				}
			}
		}
		// filename for download
		$filename = "expose_events_" . date('Ymd') . ".csv";
		$upload_dir = wp_upload_dir();
		$filenamePath = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . $filename;
		if(is_file($filenamePath)) { @unlink($filenamePath);}
		if ( FALSE !== file_put_contents($filenamePath, $out) ){
			$filenameWeb = $upload_dir['baseurl'] . '/' . $filename;
			echo '<a href="'.$filenameWeb.'">'. __('Download events', 'wp-expose'). '</a>';
		}
	}
}
add_action( 'expose_export_reports', 'wpex_export_events' );


/**
 * 
 * @param type $plugins
 * @return type
 */
if(!function_exists('_wpex_expose_get')) {
	function _wpex_expose_get() {
		
		global $wpdb;
		//if(!is_admin() || !is_user_logged_in() || !user_can_access_admin_page() ) { return; }
		if(!wpex_check_permission()) {return;}
		$intrusions = array();
		$intrusions = wp_cache_get('expose_intrusions', 'plugins');
		if ($intrusions === FALSE || empty($intrusions)) {    
			$intrusions = $wpdb->get_results( 'SELECT * FROM ' .$wpdb->prefix . WPEX_instrusions . ' ORDER BY created DESC', OBJECT );
			wp_cache_set('expose_intrusions', $intrusions, 'plugins');
		}
		return $intrusions;
	}
}
