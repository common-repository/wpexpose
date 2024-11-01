<?php
/*
Plugin Name: WPExpose
Author: Jorge Hoya
Author URI: http://www.nosoynadie.net/
Plugin URI: http://wordpress.org/plugins/wpexpose/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Version: 0.1
Description: Protect your Wordpress from several security attacks (XSS, SQL Injection, etc) using an Expose's modified version (https://github.com/enygma/expose). 
*/

if ( ! defined( 'ABSPATH' ) ) exit;
   

define ( 'WPEX_folder', dirname(__FILE__));
define ( 'WPEX_threshold', 10);
define ( 'WPEX_instrusions', 'expose_intrusions');
define ( 'WPEX_ipban', 'expose_ipbanned');
require_once ( WPEX_folder . DIRECTORY_SEPARATOR . 'src'. DIRECTORY_SEPARATOR . 'wp-expose.php');
