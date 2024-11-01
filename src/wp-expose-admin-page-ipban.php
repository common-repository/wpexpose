<?php 
if ( ! defined( 'ABSPATH' ) || !defined('WPEX_folder') ) exit;

if( ! class_exists( 'Expose_Ipsbanned_List_Table' ) ) {
    require_once( WPEX_folder . DIRECTORY_SEPARATOR . 'classes'. DIRECTORY_SEPARATOR .'expose-ipsbanned-list-table.php' );
}
if( ! class_exists( 'Expose_IPBan' ) ) {
    require_once( WPEX_folder . DIRECTORY_SEPARATOR . 'src'. DIRECTORY_SEPARATOR .'wp-expose-ipban.php' );
}

if(!function_exists('wpex_admin_ipban')) {
	function wpex_admin_ipban() {
		global $nonce;
		
		if(!wpex_check_permission()) {return;}

		echo '<div class="wrap">'.
				'<h2>' . __('IP Banned', 'wp-expose') . 
					'<a href="'.admin_url( 'admin.php?page=wp-expose-admin-page-ipban.php&wpe_action=export', '' ).'" class="add-new-h2">' .__('Export', 'wp-expose'). '</a>'.
				'</h2>'.
				'<p>' . __('IPs banned by WP Expose', 'wp-expose') . '</p>'.
			'</div>';
		$action = (isset($_REQUEST['wpe_action'])) ? strtolower(sanitize_text_field($_REQUEST['wpe_action'])) : '';
		switch ($action) {
			case 'unblock':
				wpex_ipban_unblock();
				break;
			case 'block':
				wpex_ipban_block();
				break;
		}
		echo wpex_ipsbanned_table_with_wptable();

	}
}

/**
 * Create admin table with Expose's events using WP_List_Table (default WP tables)
 * @return void
 */
if(!function_exists('wpex_ipsbanned_table_with_wptable')) {
	function wpex_ipsbanned_table_with_wptable() {
		global $wpdb;

		if(!wpex_check_permission()) {return;}
		// http://www.paulund.co.uk/wordpress-tables-using-wp_list_table
		$table = new Expose_Ipsbanned_List_Table($wpdb);
		$table->prepare_items(10);
	?>
		
		<form method="post">
			<div>
				<label for="wpe_ban_ip4"><?php echo __('Block this Ip') ?>: </label>
				<input type="text" name="wpe_ban_ip4" id="wpe_ban_ip4" >
				<input type="submit" name="btnBlock" />
			</div>
			
			<?php wp_nonce_field(); ?>
			<input type="hidden" name="wpe_action" value="block" />
		</form>
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

if(!function_exists('wpex_ipban_unblock')) {
	function wpex_ipban_unblock() {
		global $wpdb;
		if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'wp_expose')) {
			// https://codex.wordpress.org/Class_Reference/wpdb#DELETE_Rows
			$ip = new Expose_IPBan($wpdb, $wpdb->prefix. WPEX_ipban, intval ( $_GET['id']));
			$ip->Delete();
			/*
			$a = new Expose_IPBan($wpdb, $wpdb->prefix. WPEX_ipban);
			$a->GetById((int) $_GET['id']);
			$a->Delete();
			/**/
		}
	}
}
if(!function_exists('wpex_ipban_block')) {
	function wpex_ipban_block() {
		global $wpdb;
		if (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'])) {
			$ipban = new Expose_IPBan($wpdb, $wpdb->prefix. WPEX_ipban);
			$ipban->GetByIp4(sanitize_text_field ( $_POST['wpe_ban_ip4'] ));
			if(is_null($ipban->GetID()) || $ipban->GetID() == 0 ) {
				$ipban->SetIp4(sanitize_text_field ( $_POST['wpe_ban_ip4'] ));
				$ipban->SetCreated ( date('y-m-j h:i:s') );
				$ipban->Save();
			}
		}
	}
}
