<?php 
if ( ! defined( 'ABSPATH' ) || !defined('WPEX_folder')) exit;

if( ! class_exists( 'Expose_List_Table' ) ) {
	require_once( WPEX_folder . DIRECTORY_SEPARATOR . 'classes'. DIRECTORY_SEPARATOR .'expose-list-table.php' );
}

$token_name = '_wpeconfig';
$token_action = 'wpe_configuration';


if(!function_exists('wpex_admin_page_config')){
	function wpex_admin_page_config() {
		global $token_name, $token_action;
		
		
		if(wpex_check_permission()) {
			if(isset($_POST) && !empty($_POST)){
				$nonce = $_POST[$token_name];
				if ( wp_verify_nonce( $nonce, $token_action ) ) {
					wpex_admin_conf_save_options();
				}
			}
		
			echo '<div class="wrap"><h2>' . __('WPExpose configuration', 'wp-expose') . '</h2></div>';
			wpex_admin_config_page( ( isset($_GET['wpe_tab']) ? sanitize_text_field($_GET['wpe_tab']) : 'general' )  );
			
		}
	}	 
 }


if(!function_exists('wpex_admin_config_page')){
	// http://www.smashingmagazine.com/2011/10/20/create-tabs-wordpress-settings-pages/
	function wpex_admin_config_page( $current = 'general' ) {

		if(!wpex_check_permission()) {return;}
		$tabs = array( 'general' => 'General', 
			//'ipban' => 'IP Banned' 
			);
		$tabs_func = array( 'general' => 'wpex_admin_conf_tab_general();', 
			//'ipban' => 'wpex_admin_conf_tab_ipban();' 
			);
		/*
		echo '<div id="icon-themes" class="icon32"><br></div>';
		echo '<h2 class="nav-tab-wrapper">';
		foreach( $tabs as $tab => $name ){
			$class = ( $tab == $current ) ? ' nav-tab-active' : '';
			echo "<a class='nav-tab$class' href='?page=wp-expose-admin-page-config.php&wpe_tab=$tab'>$name</a>";
		}
		echo '</h2>';
		/**/
		@eval($tabs_func[$current]);
	}
}

if(!function_exists('wpex_admin_conf_tab_general')){
	function wpex_admin_conf_tab_general() {
		global $token_name, $token_action;
		
		if(!wpex_check_permission()) {return;}
		
		$contact_email = get_option("wpe_contact_email", get_option('admin_email'));
		$threshold = get_option("wpe_threshold", WPEX_threshold);
		$ips_active = get_option("wpe_ips_active", false);
		$ipban_status = get_option("wpe_ipban", false);
		$mod_security = wpex_check_modsecurity();
		if($mod_security) {
			$ips_active = false;
		}
		
		echo '<h2>'. esc_html__('General settings', 'wp-expose').'</h2>';
		echo '<div class="poststuff">'.
			'<form name="frmOptions" method="post" id="post">' .
				'<ul class="wrap">'.
					'<li>' .
						'<input type="submit" name="btnClean" value="'.__('Clean WPExpose\'s table', 'wp-expose').'" class="add-new-h2">'.
					'</li>'.
				'</ul>'.
				'<table class="form-table">'.
					'<tr>'.
						'<th scope="row"><label for="wpe_contact_email">'.__('Contact email', 'wp-expose').'</label></th>'.
						'<td><input type="text" name="wpe_contact_email" id="wpe_contact_email" value="'.esc_textarea($contact_email).'" class="regular-text ltr">'.
						'<p class="description">'.
						__('WPExpose will send attack notifications to this email', 'wp-expose').'</p>'.
						'</td>'.
					'</tr>'.
					'<tr>'.
						'<th scope="row"><label for="wpe_ips_active">'.__('IPS active', 'wp-expose').'</label></th>'.
						'<td><input type="checkbox" name="wpe_ips_active" id="wpe_ips_active" value="'.esc_textarea($ips_active) . '"' . 
						((1 == $ips_active ) ? ' checked="checked"' : '') . 
						'>'.
						'<p class="description">'.
						__('By default, the attack will be recorded and the attacker will be redirect to home page. Check this box if you want stop the malicious request and not redirected it.', 'wp-expose');
					if($mod_security)
						echo '<br />' . __('ModSecurity is enabled so we had disabled IPS mode. If you want enable again check this box but could be performance problems.', 'wp-expose');
					echo '</p>'.
						'</td>'.
					'</tr>'.
					'<tr>'.
						'<th scope="row"><label for="wpe_threshold">'.__('Threshold', 'wp-expose').'</label></th>'.
						'<td><input type="text" name="wpe_threshold" id="wpe_threshold" value="'.esc_textarea($threshold).'">'.
						'<p class="description">'.
						__('Every attack has a dangerousness level. If this level overcomes threshold\'s value the attack is recorded and an email is sent to the "Contact email".', 'wp-expose').'</p>'.
						'</td>'.
					'</tr>'.
					'<tr>'.
						'<th scope="row"><label for="wpe_ipban">'.__('IPBan active', 'wp-expose').'</label></th>'.
						'<td><input type="checkbox" name="wpe_ipban" id="wpe_ipban" value="'.esc_textarea($ipban_status) . '"' . 
						((1 == $ipban_status ) ? ' checked="checked"' : '') . 
						'>'.
						'<p class="description">'.
						__('Ban an IP if it attacks more then 3 times on 1 minute or once in a minute during 10 minutes', 'wp-expose').'</p>'.
						'</td>'.
					'</tr>'.
				'</table>'.
				'<div class="publishing-action clear">'.
					'<input type="submit" name="btnSave" value="'.__('Save', 'wp-expose').'" class="button button-primary button-large">'.
				'</div>'.
				wp_nonce_field( $token_action, $token_name ) .
			'</form>'.
			'</div>';
	}
}
/**
 * 
 * @global string $token_name
 * @global string $token_action
 */
if(!function_exists('wpex_admin_conf_tab_ipban')){
	function wpex_admin_conf_tab_ipban() {
		global $token_name, $token_action;
		//if(!is_admin() || !is_user_logged_in() || !user_can_access_admin_page() ) { return; }
		if(!wpex_check_permission()) {return;}
		
		$ipban_status = get_option("wpe_ipban", false);
		
		echo '<h3>IP Banned</h3>';
		echo '<p>Acciones posibles: activar/desactivar el bloqueo de IPs por Expose; Gestionar las IPs bloqueadas.</p>';
		echo '<form name="frmOptions" method="post">'.
				'<div class="postbox-container">'.
					'<div class="meta-box-sortables ui-sortable">'.
						'<div id="contactemaildiv" class="postbox">'.
							'<div class="handlediv" title="Click to toggle"></div>'.
							'<h3 class="hndle ui-sortable-handle"><span>' .__('Options', 'wp-expose'). '</span></h3>'.
							'<div class="inside">'.
								'<label for="wpe_ipban" class="screen-render-text">' .__('IPBan active', 'wp-expose'). '</label> '.
								'<input type="checkbox" name="wpe_ipban" id="wpe_ipban" value="'.esc_textarea($ipban_status) . '"' . 
								((1 == $ipban_status ) ? ' checked="checked"' : '') . 
								'>'.
							'</div>'.        
						'</div>'.
					'</div>'.
				'</div>'.
				'<div class="publishing-action clear">'.
					'<input type="submit" name="btnSave" value="'.__('Save', 'wp-expose').'" class="button button-primary button-large">'.
				'</div>'.
				wp_nonce_field($token_action, $token_name ) .
				'</form>';
		
	}
}

/**
 * 
 * @global string $token_name
 * @global string $token_action
 */
if(!function_exists('wpex_admin_conf_save_options')){

	function wpex_admin_conf_save_options() {
		global $token_name, $token_action;
		
		//if(!is_admin() || !is_user_logged_in() || !user_can_access_admin_page() ) { return; }
		if(!wpex_check_permission()) {return;}

		// doble comprobación
		$nonce = $_POST[$token_name];
		if ( wp_verify_nonce( $nonce, $token_action ) ) {
			if (isset($_POST['btnClean'])) {
				// clean tables
				echo ( wpex_admin_conf_empty_tables() ) ? __('Events table has been emptied', 'wp-expose') : __('Events table has not been emptied', 'wp-expose');
			}
			else if (isset($_POST['btnSave'])) {
				if (isset($_POST['wpe_contact_email'])) {
					// wpe_contact_email
					$contact_email = get_option('admin_email');
					if (!empty($_POST['wpe_contact_email'])) {
						$contact_email = sanitize_text_field($_POST['wpe_contact_email']);
					}
					update_option('wpe_contact_email', sanitize_email($contact_email));
				}
				if (isset($_POST['wpe_threshold'])) {
					$threshold = (!empty($_POST['wpe_threshold'])) ? (int)sanitize_text_field($_POST['wpe_threshold']) : WPEX_threshold;
					update_option('wpe_threshold', $threshold);
				}
				$ids_active = false;
				if (isset($_POST['wpe_ips_active'])) {
					$ids_active = true;   
				}
				update_option('wpe_ips_active', $ids_active);
				
				// ip ban
				$ipban_active = false;
				if (isset($_POST['wpe_ipban'])) {
					$ipban_active = true;
				}
				update_option('wpe_ipban', $ipban_active);
			}
		}
	}
}

if(!function_exists('wpex_admin_conf_empty_tables')){

	function wpex_admin_conf_empty_tables() {
		global $wpdb;
		if(!is_admin() || !is_user_logged_in() || !user_can_access_admin_page() ) { return; }
		return $delete = $wpdb->query('TRUNCATE TABLE ' . $wpdb->prefix . WPEX_instrusions);
	}

}

if(!function_exists('wpex_check_modsecurity')){

	function wpex_check_modsecurity()
	{
		// http://stackoverflow.com/questions/3182500/detect-if-mod-security-is-installed-with-php
		return false;
	}
}